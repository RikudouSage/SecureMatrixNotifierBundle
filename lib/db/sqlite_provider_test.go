package db

import (
	"context"
	"fmt"
	"lib/helper"
	"os"
	"testing"

	"github.com/rs/zerolog/log"
	"go.mau.fi/util/dbutil"
	"maunium.net/go/mautrix/sqlstatestore"
)

func TestDsn(t *testing.T) {
	tempDir := os.TempDir() + "/matrix_notifier/sqlite_provider_test"

	if _, err := os.Stat(tempDir); err == nil {
		err := os.RemoveAll(tempDir)
		if err != nil {
			t.Fatalf("error removing temp dir: %v", err)
		}
	}

	if err := os.MkdirAll(tempDir, 0755); err != nil {
		t.Fatalf("error creating temp dir: %v", err)
	}

	dsns := map[string]*string{
		fmt.Sprintf("%s/db1.sqlite3", tempDir):                                   nil,
		fmt.Sprintf("sqlite://%s/db2.sqlite3", tempDir):                          nil,
		fmt.Sprintf("sqlite:////%s/db3.sqlite3", tempDir):                        nil,
		fmt.Sprintf("sqlite://%s/db4.sqlite3?_txlock=immediate", tempDir):        nil,
		fmt.Sprintf("sqlite://%s/nested/db5.sqlite3?_txlock=immediate", tempDir): helper.ToPointer(fmt.Sprintf("/%s/nested/db5.sqlite3", tempDir)),
	}

	i := 0
	for dsn, expectedFile := range dsns {
		i++
		if expectedFile == nil {
			expectedFile = helper.ToPointer(fmt.Sprintf("%s/db%d.sqlite3", tempDir, i))
		}
		provider := &SqliteProvider{}
		if !provider.supports(dsn) {
			t.Fatalf("provider does not support %s", dsn)
		}

		db, err := provider.Get(dsn)
		if err != nil {
			t.Fatalf("error getting db: %v", err)
		}
		if db == nil {
			t.Fatalf("db is nil")
		}
		store := sqlstatestore.NewSQLStateStore(db, dbutil.ZeroLogger(log.With().Str("db_section", "matrix_state").Logger()), false)
		err = store.Upgrade(context.Background())
		if err != nil {
			t.Fatalf("error upgrading db: %v", err)
		}

		err = db.Close()
		if err != nil {
			t.Fatalf("error closing db: %v", err)
		}

		if _, err := os.Stat(*expectedFile); os.IsNotExist(err) {
			t.Fatalf("file %s does not exist", *expectedFile)
		}
	}

	_ = os.RemoveAll(tempDir)
}
