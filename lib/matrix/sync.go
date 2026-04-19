package matrix

import (
	"context"
	"lib/helper"
	"sync"

	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/crypto/cryptohelper"
)

func startSyncAndWaitForReady(client *mautrix.Client, syncer *mautrix.DefaultSyncer, crypto *cryptohelper.CryptoHelper, recoveryKey string) (<-chan error, <-chan error) {
	readyChan := make(chan error, 1)
	errChan := make(chan error, 1)
	var onceSetupEncryption sync.Once

	syncer.OnSync(func(ctx context.Context, resp *mautrix.RespSync, since string) bool {
		onceSetupEncryption.Do(func() {
			helper.TrySendErr(readyChan, prepareEncryptionMachine(ctx, crypto, recoveryKey))
		})

		return true
	})

	go func() {
		if err := client.Sync(); err != nil {
			helper.TrySendErr(errChan, err)
		}
	}()

	return errChan, readyChan
}
