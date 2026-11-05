FROM golang:1.23-alpine AS build
WORKDIR /app
COPY go.mod ./
COPY . .
RUN CGO_ENABLED=0 go build -mod=vendor -o /stockflow ./cmd/server
FROM alpine:3.21
RUN adduser -D app
USER app
COPY --from=build /stockflow /stockflow
EXPOSE 8080
ENTRYPOINT ["/stockflow"]
