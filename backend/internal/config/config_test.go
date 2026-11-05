package config

import (
	"os"
	"path/filepath"
	"testing"
)

func writeEnv(t *testing.T, body string) string {
	t.Helper()
	path := filepath.Join(t.TempDir(), ".env")
	if err := os.WriteFile(path, []byte(body), 0600); err != nil {
		t.Fatal(err)
	}
	return path
}
func TestLoadEnvFile(t *testing.T) {
	path := writeEnv(t, "# local configuration\nDATABASE_URL='postgres://test:password@localhost:55432/test'\nPORT=18081\nHOST=127.0.0.1\nAUTO_MIGRATE=false\n")
	cfg, err := Load(path)
	if err != nil {
		t.Fatal(err)
	}
	if cfg.Address != "127.0.0.1:18081" || cfg.AutoMigrate || cfg.DatabaseURL != "postgres://test:password@localhost:55432/test" {
		t.Fatalf("unexpected config: %+v", cfg)
	}
}
func TestEnvironmentOverridesFile(t *testing.T) {
	t.Setenv("PORT", "19000")
	t.Setenv("HOST", "127.0.0.1")
	cfg, err := Load(writeEnv(t, "PORT=18080\n"))
	if err != nil {
		t.Fatal(err)
	}
	if cfg.Address != "127.0.0.1:19000" {
		t.Fatalf("environment ignored: %s", cfg.Address)
	}
}
func TestInvalidConfig(t *testing.T) {
	for _, body := range []string{"PORT=0", "PORT=abc", "PORT=65536", "AUTO_MIGRATE=maybe", "DATABASE_URL=", "INVALID LINE"} {
		t.Run(body, func(t *testing.T) {
			if _, err := Load(writeEnv(t, body)); err == nil {
				t.Fatal("expected config error")
			}
		})
	}
}
func TestMissingFileUsesDefaults(t *testing.T) {
	cfg, err := Load(filepath.Join(t.TempDir(), "missing.env"))
	if err != nil {
		t.Fatal(err)
	}
	if cfg.Address != "0.0.0.0:18080" || !cfg.AutoMigrate {
		t.Fatalf("unexpected defaults: %+v", cfg)
	}
}
