package matrix

import (
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"net/url"
	"strings"
	"testing"

	"github.com/rs/zerolog"
	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/id"
)

func newTestClient(t *testing.T, server *httptest.Server, userID string) *mautrix.Client {
	t.Helper()

	baseURL, err := url.Parse(server.URL)
	if err != nil {
		t.Fatalf("failed to parse server URL: %v", err)
	}

	httpClient := server.Client()

	client := &mautrix.Client{
		HomeserverURL: baseURL,
		Client:        httpClient,
		UserID:        id.UserID(userID),
		Log:           zerolog.Nop(),
	}

	return client
}

func TestResolveRecipientDirectMessageExistingRoom(t *testing.T) {
	accountDataCalls := 0
	joinedRoomsCalls := 0

	handler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		switch {
		case r.Method == http.MethodGet && strings.HasSuffix(r.URL.Path, "/account_data/m.direct"):
			accountDataCalls++
			writeJSON(t, w, map[string][]string{
				"@friend:example.com": {"!room:example.com"},
			})
		case r.Method == http.MethodGet && strings.HasSuffix(r.URL.Path, "/joined_rooms"):
			joinedRoomsCalls++
			writeJSON(t, w, map[string][]string{
				"joined_rooms": {"!room:example.com"},
			})
		default:
			t.Fatalf("unexpected request: %s %s", r.Method, r.URL.Path)
		}
	})

	server := httptest.NewServer(handler)
	defer server.Close()

	client := newTestClient(t, server, "@self:example.com")

	roomID, err := resolveRecipient(client, "@friend:example.com")
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if roomID != "!room:example.com" {
		t.Fatalf("expected room ID !room:example.com, got %s", roomID)
	}
	if accountDataCalls != 1 {
		t.Fatalf("expected 1 account data call, got %d", accountDataCalls)
	}
	if joinedRoomsCalls != 1 {
		t.Fatalf("expected 1 joined rooms call, got %d", joinedRoomsCalls)
	}
}

func TestResolveRecipientDirectMessageCreatesRoom(t *testing.T) {
	setAccountDataBody := map[string][]string{}
	setAccountDataCalls := 0
	createRoomCalls := 0

	handler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		switch {
		case r.Method == http.MethodGet && strings.HasSuffix(r.URL.Path, "/account_data/m.direct"):
			writeJSON(t, w, map[string][]string{})
		case r.Method == http.MethodGet && strings.HasSuffix(r.URL.Path, "/joined_rooms"):
			writeJSON(t, w, map[string][]string{
				"joined_rooms": []string{},
			})
		case r.Method == http.MethodPost && strings.HasSuffix(r.URL.Path, "/createRoom"):
			createRoomCalls++
			writeJSON(t, w, map[string]string{"room_id": "!new:example.com"})
		case r.Method == http.MethodPut && strings.HasSuffix(r.URL.Path, "/account_data/m.direct"):
			setAccountDataCalls++
			body, err := io.ReadAll(r.Body)
			if err != nil {
				t.Fatalf("failed to read body: %v", err)
			}
			if err := json.Unmarshal(body, &setAccountDataBody); err != nil {
				t.Fatalf("failed to unmarshal body: %v", err)
			}
			writeJSON(t, w, map[string]string{})
		default:
			t.Fatalf("unexpected request: %s %s", r.Method, r.URL.Path)
		}
	})

	server := httptest.NewServer(handler)
	defer server.Close()

	client := newTestClient(t, server, "@self:example.com")

	roomID, err := resolveRecipient(client, "@friend:example.com")
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if roomID != "!new:example.com" {
		t.Fatalf("expected room ID !new:example.com, got %s", roomID)
	}
	if createRoomCalls != 1 {
		t.Fatalf("expected create room to be called once, got %d", createRoomCalls)
	}
	if setAccountDataCalls != 1 {
		t.Fatalf("expected set account data to be called once, got %d", setAccountDataCalls)
	}

	rooms, ok := setAccountDataBody["@friend:example.com"]
	if !ok {
		t.Fatalf("expected account data to contain friend entry")
	}
	found := false
	for _, room := range rooms {
		if room == "!new:example.com" {
			found = true
			break
		}
	}
	if !found {
		t.Fatalf("expected account data to include new room")
	}
}

func TestResolveRecipientRoomAlias(t *testing.T) {
	handler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodGet || !strings.Contains(r.URL.Path, "/directory/room/") {
			t.Fatalf("unexpected request: %s %s", r.Method, r.URL.Path)
		}
		writeJSON(t, w, map[string]string{"room_id": "!alias:example.com"})
	})

	server := httptest.NewServer(handler)
	defer server.Close()

	client := newTestClient(t, server, "@self:example.com")

	roomID, err := resolveRecipient(client, "#general:example.com")
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if roomID != "!alias:example.com" {
		t.Fatalf("expected !alias:example.com, got %s", roomID)
	}
}

func TestResolveRecipientRoomID(t *testing.T) {
	roomID, err := resolveRecipient(nil, "!room:example.com")
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if roomID != "!room:example.com" {
		t.Fatalf("expected !room:example.com, got %s", roomID)
	}
}

func TestResolveRecipientUnknown(t *testing.T) {
	_, err := resolveRecipient(nil, "unknown")
	if err == nil {
		t.Fatalf("expected error for unknown recipient")
	}
}

func writeJSON(t *testing.T, w http.ResponseWriter, payload any) {
	t.Helper()

	w.Header().Set("Content-Type", "application/json")
	if err := json.NewEncoder(w).Encode(payload); err != nil {
		t.Fatalf("failed to encode payload: %v", err)
	}
}
