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

func prepareEncryptionMachine(ctx context.Context, crypto *cryptohelper.CryptoHelper, recoveryKey string) error {
	machine := crypto.Machine()
	keyId, keyData, err := machine.SSSS.GetDefaultKeyData(ctx)
	if err != nil {
		return err
	}

	key, err := keyData.VerifyRecoveryKey(keyId, recoveryKey)
	if err != nil {
		return err
	}

	if err = machine.FetchCrossSigningKeysFromSSSS(ctx, key); err != nil {
		return err
	}

	if err = machine.SignOwnDevice(ctx, machine.OwnIdentity()); err != nil {
		return err
	}

	if err = machine.SignOwnMasterKey(ctx); err != nil {
		return err
	}

	return nil
}
