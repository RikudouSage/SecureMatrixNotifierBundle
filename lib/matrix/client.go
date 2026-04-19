package matrix

import (
	"context"

	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/id"
)

type MautrixFactory func() (*mautrix.Client, error)

func createClient(clientFactory MautrixFactory, deviceId id.DeviceID) (*mautrix.Client, *mautrix.DefaultSyncer, error) {
	client, err := clientFactory()
	if err != nil {
		return nil, nil, err
	}

	whoami, err := client.Whoami(context.Background())
	if err != nil {
		return nil, nil, err
	}

	syncer := mautrix.NewDefaultSyncer()
	client.UserID = whoami.UserID
	client.DeviceID = deviceId
	client.Syncer = syncer

	return client, syncer, nil
}
