package matrix

import (
	"os"
	"path/filepath"
	"testing"

	"github.com/rs/zerolog"
	"maunium.net/go/mautrix"
)

func TestInitializeEncryptionSuccess(t *testing.T) {
	tempDir := t.TempDir()
	databasePath := filepath.Join(tempDir, "crypto.db")

	client := &mautrix.Client{
		Syncer: mautrix.NewDefaultSyncer(),
		Log:    zerolog.Nop(),
	}

	helper, err := initializeEncryption(client, []byte("secret"), databasePath)
	if err != nil {
		t.Fatalf("initializeEncryption returned error: %v", err)
	}
	if helper == nil {
		t.Fatalf("expected helper to be non-nil")
	}
	if client.Crypto != helper {
		t.Fatalf("expected client.Crypto to be set to helper")
	}

	if _, err := os.Stat(databasePath); err != nil {
		t.Fatalf("expected database file to exist: %v", err)
	}
}

func TestInitializeEncryptionFailsWithoutSyncer(t *testing.T) {
	client := &mautrix.Client{
		Log: zerolog.Nop(),
	}

	_, err := initializeEncryption(client, []byte("secret"), filepath.Join(t.TempDir(), "crypto.db"))
	if err == nil {
		t.Fatalf("expected error when client syncer does not implement ExtensibleSyncer")
	}
}

func TestInitializeEncryptionFailsWithEmptyPickleKey(t *testing.T) {
	client := &mautrix.Client{
		Syncer: mautrix.NewDefaultSyncer(),
		Log:    zerolog.Nop(),
	}

	_, err := initializeEncryption(client, nil, filepath.Join(t.TempDir(), "crypto.db"))
	if err == nil {
		t.Fatalf("expected error when pickle key is empty")
	}
}
