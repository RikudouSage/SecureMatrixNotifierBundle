package matrix

import (
	"io"
	"lib/db"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/rs/zerolog"
	"maunium.net/go/mautrix"
)

type roundTripFunc func(*http.Request) (*http.Response, error)

func (f roundTripFunc) RoundTrip(req *http.Request) (*http.Response, error) {
	return f(req)
}

func newLoggedInTestClient(t *testing.T) *mautrix.Client {
	t.Helper()

	homeserverURL, err := mautrix.ParseAndNormalizeBaseURL("https://example.org")
	if err != nil {
		t.Fatalf("failed to parse homeserver URL: %v", err)
	}

	stubTransport := roundTripFunc(func(req *http.Request) (*http.Response, error) {
		body := `{"device_keys":{}}`
		return &http.Response{
			StatusCode: http.StatusOK,
			Body:       io.NopCloser(strings.NewReader(body)),
			Header:     make(http.Header),
		}, nil
	})

	return &mautrix.Client{
		Syncer:        mautrix.NewDefaultSyncer(),
		Log:           zerolog.Nop(),
		UserID:        "@tester:example.org",
		DeviceID:      "TESTDEVICE",
		Client:        &http.Client{Transport: stubTransport},
		HomeserverURL: homeserverURL,
	}
}

func TestInitializeEncryptionSuccess(t *testing.T) {
	tempDir := t.TempDir()
	databasePath := filepath.Join(tempDir, "crypto.db")
	database, _ := (&db.SqliteProvider{}).Get(databasePath)

	client := newLoggedInTestClient(t)

	helper, err := initializeEncryption(client, []byte("secret"), database)
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

	database, _ := (&db.SqliteProvider{}).Get(filepath.Join(t.TempDir(), "crypto.db"))

	_, err := initializeEncryption(client, []byte("secret"), database)
	if err == nil {
		t.Fatalf("expected error when client syncer does not implement ExtensibleSyncer")
	}
}

func TestInitializeEncryptionFailsWithEmptyPickleKey(t *testing.T) {
	client := newLoggedInTestClient(t)

	database, _ := (&db.SqliteProvider{}).Get(filepath.Join(t.TempDir(), "crypto.db"))

	_, err := initializeEncryption(client, nil, database)
	if err == nil {
		t.Fatalf("expected error when pickle key is empty")
	}
}
