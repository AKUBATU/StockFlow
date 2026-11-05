package config

import (
	"bufio"
	"fmt"
	"os"
	"strconv"
	"strings"
)

type Config struct {
	DatabaseURL string
	Address     string
	AutoMigrate bool
}

// Load keeps environment variables authoritative over the optional local .env.
func Load(path string) (Config, error) {
	file, err := os.Open(path)
	if err != nil && !os.IsNotExist(err) {
		return Config{}, fmt.Errorf("read config: %w", err)
	}
	values := map[string]string{}
	if file != nil {
		defer file.Close()
		scanner := bufio.NewScanner(file)
		line := 0
		for scanner.Scan() {
			line++
			text := strings.TrimSpace(scanner.Text())
			if text == "" || strings.HasPrefix(text, "#") {
				continue
			}
			key, value, ok := strings.Cut(strings.TrimPrefix(text, "export "), "=")
			key, value = strings.TrimSpace(key), strings.TrimSpace(value)
			if !ok || key == "" {
				return Config{}, fmt.Errorf("invalid .env entry on line %d", line)
			}
			if len(value) >= 2 && ((value[0] == '"' && value[len(value)-1] == '"') || (value[0] == '\'' && value[len(value)-1] == '\'')) {
				value = value[1 : len(value)-1]
			}
			values[key] = value
		}
		if err = scanner.Err(); err != nil {
			return Config{}, fmt.Errorf("read config: %w", err)
		}
	}
	get := func(key, fallback string) string {
		if v, ok := os.LookupEnv(key); ok {
			return v
		}
		if v, ok := values[key]; ok {
			return v
		}
		return fallback
	}
	port, err := strconv.Atoi(get("PORT", "18080"))
	if err != nil || port < 1 || port > 65535 {
		return Config{}, fmt.Errorf("PORT must be an integer between 1 and 65535")
	}
	auto, err := strconv.ParseBool(get("AUTO_MIGRATE", "true"))
	if err != nil {
		return Config{}, fmt.Errorf("AUTO_MIGRATE must be true or false")
	}
	url := get("DATABASE_URL", "postgres://stockflow:stockflow@localhost:55432/stockflow?sslmode=disable")
	if strings.TrimSpace(url) == "" {
		return Config{}, fmt.Errorf("DATABASE_URL cannot be empty")
	}
	return Config{DatabaseURL: url, Address: fmt.Sprintf("%s:%d", get("HOST", "0.0.0.0"), port), AutoMigrate: auto}, nil
}
