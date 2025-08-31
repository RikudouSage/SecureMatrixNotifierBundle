package matrix

import (
	"context"

	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/crypto/cryptohelper"
)

func initializeEncryption(client *mautrix.Client, pickleKey []byte, databasePath string) (*cryptohelper.CryptoHelper, error) {
	helper, err := cryptohelper.NewCryptoHelper(client, pickleKey, databasePath)
	if err != nil {
		return nil, err
	}

	err = helper.Init(context.Background())
	if err != nil {
		return nil, err
	}

	client.Crypto = helper

	return helper, nil
}
