# Vendored Go dependencies

The `vendor` directory is included so building and testing StockFlow does not require network access. It contains upstream library source, not application mocks or modified database drivers. Each module's license is retained in its directory.

| Module | Version | Source |
| --- | --- | --- |
| github.com/jackc/pgx/v5 | v5.5.5 | https://github.com/jackc/pgx/tree/v5.5.5 |
| github.com/jackc/pgpassfile | v1.0.0 | https://github.com/jackc/pgpassfile/tree/v1.0.0 |
| github.com/jackc/pgservicefile | v0.0.0-20221227161230-091c0ba34f0a | https://github.com/jackc/pgservicefile/tree/091c0ba34f0a |
| github.com/jackc/puddle/v2 | v2.2.1 | https://github.com/jackc/puddle/tree/v2.2.1 |
| golang.org/x/crypto | v0.18.0 | https://go.googlesource.com/crypto/+/refs/tags/v0.18.0 |
| golang.org/x/sync | v0.4.0 | https://go.googlesource.com/sync/+/refs/tags/v0.4.0 |
| golang.org/x/text | v0.14.0 | https://go.googlesource.com/text/+/refs/tags/v0.14.0 |

Jackc source files were retrieved from the exact upstream revisions using the connected GitHub reader. Google Go subrepository packages were copied from the existing local Go module cache. Unneeded upstream tests and examples are omitted. Application integration tests remain in `internal/api`.

Build normally with `go build ./cmd/server` or explicitly with `go build -mod=vendor ./cmd/server`. For dependency upgrades, use a network-enabled development environment, update `go.mod`, run `go mod tidy` and `go mod vendor`, then rerun the integration tests.
