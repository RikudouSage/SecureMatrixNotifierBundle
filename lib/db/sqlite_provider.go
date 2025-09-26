package db

import (
	"database/sql"
	"fmt"
	"net/url"
	"os"
	"path"
	"strings"

	_ "github.com/mattn/go-sqlite3"
	"go.mau.fi/util/dbutil"
)

type SqliteProvider struct {
}

func (receiver *SqliteProvider) supports(dsn string) bool {
	return strings.HasPrefix(dsn, "sqlite://") || strings.HasPrefix(dsn, "/")
}

func (receiver *SqliteProvider) Get(dsn string) (*dbutil.Database, error) {
	if strings.HasPrefix(dsn, "sqlite://") {
		dsn = dsn[len("sqlite://"):]
	}

	for strings.HasPrefix(dsn, "//") {
		dsn = dsn[1:]
	}

	dsn = fmt.Sprintf("file:%s", dsn)

	db, err := sql.Open("sqlite3", dsn)
	if err != nil {
		return nil, err
	}

	uri, err := url.Parse(dsn)
	if err != nil {
		return nil, err
	}

	parentDir := path.Dir(uri.Path)
	if _, err := os.Stat(parentDir); os.IsNotExist(err) {
		err = os.MkdirAll(parentDir, 0755)
		if err != nil {
			return nil, err
		}
	}

	return dbutil.NewWithDB(db, "sqlite3-fk-wal")
}

func init() {
	providerList = append(providerList, &SqliteProvider{})
}
