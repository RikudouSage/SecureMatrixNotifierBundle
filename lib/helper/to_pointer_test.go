package helper

import "testing"

func TestToPointer(t *testing.T) {
	value := "example"
	ptr := ToPointer(value)

	if ptr == nil {
		t.Fatalf("expected pointer, got nil")
	}

	if *ptr != value {
		t.Fatalf("expected %q, got %q", value, *ptr)
	}

	if ptr == &value {
		t.Fatalf("expected new pointer, got original address")
	}
}
