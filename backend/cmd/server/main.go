package main

import (
	"context"
	"log"
	"net/http"
	"os"
	"os/signal"
	"stockflow/internal/api"
	"stockflow/internal/config"
	"stockflow/migrations"
	"syscall"
	"time"

	"github.com/jackc/pgx/v5/pgxpool"
)

func main() {
	ctx, stop := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer stop()
	cfg, err := config.Load(".env")
	if err != nil {
		log.Fatal(err)
	}
	startup, cancelStartup := context.WithTimeout(ctx, 30*time.Second)
	defer cancelStartup()
	pool, err := pgxpool.New(startup, cfg.DatabaseURL)
	if err != nil {
		log.Fatal(err)
	}
	defer pool.Close()
	if err = pool.Ping(startup); err != nil {
		log.Fatalf("PostgreSQL tidak dapat dihubungi: %v. Periksa DATABASE_URL atau jalankan scripts/dev-backend.ps1", err)
	}
	if cfg.AutoMigrate {
		if err = migrations.Apply(startup, pool); err != nil {
			log.Fatalf("Inisialisasi database gagal: %v", err)
		}
	}
	server := &http.Server{Addr: cfg.Address, Handler: api.New(pool), ReadHeaderTimeout: 5 * time.Second, ReadTimeout: 15 * time.Second, WriteTimeout: 20 * time.Second, IdleTimeout: 60 * time.Second}
	go func() {
		<-ctx.Done()
		shutdown, cancel := context.WithTimeout(context.Background(), 10*time.Second)
		defer cancel()
		_ = server.Shutdown(shutdown)
	}()
	log.Printf("StockFlow API listening on %s", cfg.Address)
	if err := server.ListenAndServe(); err != nil && err != http.ErrServerClosed {
		log.Fatal(err)
	}
}
