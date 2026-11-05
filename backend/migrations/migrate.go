package migrations

import (
	"context"
	"embed"
	"fmt"
	"io/fs"
	"strings"

	"github.com/jackc/pgx/v5/pgxpool"
)

//go:embed *.sql
var files embed.FS

// Apply serializes startup migrations and records each version in its transaction.
// The same ledger is written by Docker's initialization SQL files.
func Apply(ctx context.Context, pool *pgxpool.Pool) error {
	names, err := fs.Glob(files, "*.sql")
	if err != nil {
		return err
	}
	for _, name := range names {
		if err = applyOne(ctx, pool, name); err != nil {
			return fmt.Errorf("migration %s: %w", name, err)
		}
	}
	return nil
}

func applyOne(ctx context.Context, pool *pgxpool.Pool, name string) error {
	tx, err := pool.Begin(ctx)
	if err != nil {
		return err
	}
	defer tx.Rollback(ctx)
	if _, err = tx.Exec(ctx, `SELECT pg_advisory_xact_lock(718053427)`); err != nil {
		return err
	}
	if _, err = tx.Exec(ctx, `CREATE TABLE IF NOT EXISTS stockflow_schema_migrations(version TEXT PRIMARY KEY, applied_at TIMESTAMPTZ NOT NULL DEFAULT now())`); err != nil {
		return err
	}
	var exists bool
	if err = tx.QueryRow(ctx, `SELECT EXISTS(SELECT 1 FROM stockflow_schema_migrations WHERE version=$1)`, name).Scan(&exists); err != nil {
		return err
	}
	if !exists {
		data, readErr := files.ReadFile(name)
		if readErr != nil {
			return readErr
		}
		// SQL files remain usable directly through psql / docker-entrypoint-initdb.d.
		sql := strings.TrimSuffix(strings.TrimPrefix(strings.TrimSpace(string(data)), "BEGIN;"), "COMMIT;")
		if _, err = tx.Exec(ctx, sql); err != nil {
			return err
		}
		if _, err = tx.Exec(ctx, `INSERT INTO stockflow_schema_migrations(version) VALUES($1) ON CONFLICT DO NOTHING`, name); err != nil {
			return err
		}
	}
	return tx.Commit(ctx)
}
