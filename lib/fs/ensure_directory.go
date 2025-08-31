package fs

import (
	"fmt"
	"os"
)

func EnsureDirectoryExists(directory string) error {
	info, err := os.Stat(directory)
	if os.IsNotExist(err) {
		return os.MkdirAll(directory, 0755)
	}

	if !info.IsDir() {
		return fmt.Errorf("%s is not a directory", directory)
	}

	return nil
}
