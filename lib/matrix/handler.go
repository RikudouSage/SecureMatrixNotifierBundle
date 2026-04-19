package matrix

import (
	"errors"
	"lib/db"
	"lib/types"

	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/id"
)

func SendMessage(
	messageType types.MessageType,
	renderingType types.RenderingType,
	message string,
	recipient string,
	databaseDsn string,
	accessToken string,
	recoveryKey string,
	pickleKey []byte,
	url string,
	deviceId id.DeviceID,
	clientFactory MautrixFactory,
) (messageId string, err error) {
	if recipient == "" {
		err = errors.New("recipient cannot be empty")
		return
	}

	databaseProvider := db.FindProvider(databaseDsn)
	if databaseProvider == nil {
		err = errors.New("databaseProvider is nil, the databaseProvider DSN is invalid")
		return
	}
	database, err := databaseProvider.Get(databaseDsn)
	if err != nil {
		return
	}

	if clientFactory == nil {
		clientFactory = func() (*mautrix.Client, error) {
			return mautrix.NewClient(url, "", accessToken)
		}
	}

	client, syncer, err := createClient(clientFactory, deviceId)
	if err != nil {
		return
	}

	crypto, err := initializeEncryption(client, pickleKey, database)
	if err != nil {
		return
	}

	roomId, err := resolveRecipient(client, recipient)
	if err != nil {
		return
	}

	errChan, readyChan := startSyncAndWaitForReady(client, syncer, crypto, recoveryKey)
	defer client.StopSync()

	err = waitUntilReady(readyChan, errChan)
	if err != nil {
		return
	}

	response, err := sendMessageWithSync(client, roomId, messageType, renderingType, message, errChan)
	if err != nil {
		return
	}
	messageId = string(response.EventID)

	return
}
