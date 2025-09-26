package db

import (
	"database/sql"
	"strings"

	_ "github.com/mattn/go-sqlite3"
)

type SqliteProvider struct {
}

func (receiver *SqliteProvider) supports(dsn string) bool {
	return strings.HasPrefix(dsn, "sqlite://") || strings.HasPrefix(dsn, "/")
}

func (receiver *SqliteProvider) GetDb(dsn string) (*sql.DB, error) {
	if strings.HasPrefix(dsn, "sqlite://") {
		dsn = dsn[len("sqlite://"):]
	}

	for strings.HasPrefix(dsn, "//") {
		dsn = dsn[1:]
	}

	return sql.Open("sqlite3", dsn)
}

func init() {
	providerList = append(providerList, &SqliteProvider{})
}
