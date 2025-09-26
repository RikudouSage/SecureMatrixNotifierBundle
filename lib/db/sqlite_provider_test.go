package db

import (
	"context"
	"fmt"
	"lib/helper"
	"os"
	"path/filepath"
	"testing"

	"github.com/rs/zerolog/log"
	"go.mau.fi/util/dbutil"
	"maunium.net/go/mautrix/sqlstatestore"
)

func TestDsn(t *testing.T) {
	tempDir := filepath.Join(os.TempDir(), "matrix_notifier", "sqlite_provider_test")
	_ = os.RemoveAll(tempDir)
	if err := os.MkdirAll(tempDir, 0o755); err != nil {
		t.Fatalf("error creating temp dir: %v", err)
	}
	t.Cleanup(func() { _ = os.RemoveAll(tempDir) })

	type config struct {
		dsn          string
		expectedFile *string
	}

	dsns := []config{
		{dsn: fmt.Sprintf("%s/db1.sqlite3", tempDir)},
		{dsn: fmt.Sprintf("sqlite://%s/db2.sqlite3", tempDir)},
		{dsn: fmt.Sprintf("sqlite:////%s/db3.sqlite3", tempDir)},
		{dsn: fmt.Sprintf("sqlite://%s/db4.sqlite3?_txlock=immediate", tempDir)},
		{
			dsn:          fmt.Sprintf("sqlite://%s/nested/db5.sqlite3?_txlock=immediate", tempDir),
			expectedFile: helper.ToPointer(fmt.Sprintf("/%s/nested/db5.sqlite3", tempDir)),
		},
	}

	for i, cfg := range dsns {
		expectedFile := cfg.expectedFile
		dsn := cfg.dsn

		if expectedFile == nil {
			expectedFile = helper.ToPointer(fmt.Sprintf("%s/db%d.sqlite3", tempDir, i+1))
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
}
