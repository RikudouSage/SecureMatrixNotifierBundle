package matrix

import (
	"context"

	"go.mau.fi/util/dbutil"
	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/crypto/cryptohelper"
)

func initializeEncryption(client *mautrix.Client, pickleKey []byte, database *dbutil.Database) (*cryptohelper.CryptoHelper, error) {
	helper, err := cryptohelper.NewCryptoHelper(client, pickleKey, database)
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
