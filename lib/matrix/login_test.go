package matrix

import (
	"errors"
	"io"
	"net/http"
	"strings"
	"testing"

	"github.com/rs/zerolog"
	"maunium.net/go/mautrix"
)

func TestLoginSuccess(t *testing.T) {
	homeserver := "https://example.org"
	username := "alice"
	password := "secret"

	homeserverURL, err := mautrix.ParseAndNormalizeBaseURL(homeserver)
	if err != nil {
		t.Fatalf("failed to parse homeserver URL: %v", err)
	}

	var capturedBody string

	stubTransport := roundTripFunc(func(req *http.Request) (*http.Response, error) {
		bodyBytes, err := io.ReadAll(req.Body)
		if err != nil {
			t.Fatalf("failed to read request body: %v", err)
		}
		capturedBody = string(bodyBytes)

		responseBody := `{"user_id":"@alice:example.org","access_token":"ACCESS","device_id":"DEVICE"}`
		return &http.Response{
			StatusCode: http.StatusOK,
			Body:       io.NopCloser(strings.NewReader(responseBody)),
			Header:     make(http.Header),
		}, nil
	})

	factoryCalled := false
	factory := func() (*mautrix.Client, error) {
		factoryCalled = true
		return &mautrix.Client{
			Log:           zerolog.Nop(),
			Client:        &http.Client{Transport: stubTransport},
			HomeserverURL: homeserverURL,
		}, nil
	}

	deviceID, accessToken, err := Login(homeserver, username, password, factory)
	if err != nil {
		t.Fatalf("Login returned error: %v", err)
	}

	if !factoryCalled {
		t.Fatalf("expected factory to be called")
	}

	if deviceID != "DEVICE" {
		t.Fatalf("expected device ID to be DEVICE, got %s", deviceID)
	}

	if accessToken != "ACCESS" {
		t.Fatalf("expected access token to be ACCESS, got %s", accessToken)
	}

	if !strings.Contains(capturedBody, `"user":"alice"`) {
		t.Fatalf("expected login request body to contain username, got: %s", capturedBody)
	}

	if !strings.Contains(capturedBody, `"password":"secret"`) {
		t.Fatalf("expected login request body to contain password, got: %s", capturedBody)
	}

	if !strings.Contains(capturedBody, `"initial_device_display_name":"Symfony Secure Matrix Notifier bridge"`) {
		t.Fatalf("expected login request body to contain device display name, got: %s", capturedBody)
	}
}

func TestLoginFactoryError(t *testing.T) {
	expectedErr := errors.New("factory failed")

	_, _, err := Login("https://example.org", "alice", "secret", func() (*mautrix.Client, error) {
		return nil, expectedErr
	})

	if !errors.Is(err, expectedErr) {
		t.Fatalf("expected error %v, got %v", expectedErr, err)
	}
}

func TestLoginRequestError(t *testing.T) {
	homeserver := "https://example.org"

	homeserverURL, err := mautrix.ParseAndNormalizeBaseURL(homeserver)
	if err != nil {
		t.Fatalf("failed to parse homeserver URL: %v", err)
	}

	requestErr := errors.New("network failure")

	factory := func() (*mautrix.Client, error) {
		return &mautrix.Client{
			Log: zerolog.Nop(),
			Client: &http.Client{Transport: roundTripFunc(func(req *http.Request) (*http.Response, error) {
				return nil, requestErr
			})},
			HomeserverURL: homeserverURL,
		}, nil
	}

	_, _, err = Login(homeserver, "alice", "secret", factory)
	if !errors.Is(err, requestErr) {
		t.Fatalf("expected error %v, got %v", requestErr, err)
	}
}
