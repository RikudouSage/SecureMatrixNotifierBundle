package matrix

import (
	"context"

	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/id"
)

func Login(
	homeserver string,
	username string,
	password string,
	mautrixFactory MautrixFactory,
) (deviceId id.DeviceID, accessToken string, err error) {
	if mautrixFactory == nil {
		mautrixFactory = func() (*mautrix.Client, error) {
			return mautrix.NewClient(homeserver, "", "")
		}
	}

	client, err := mautrixFactory()
	if err != nil {
		return
	}

	resp, err := client.Login(context.Background(), &mautrix.ReqLogin{
		Type: mautrix.AuthTypePassword,
		Identifier: mautrix.UserIdentifier{
			Type: mautrix.IdentifierTypeUser,
			User: username,
		},
		Password:                 password,
		InitialDeviceDisplayName: "Symfony Secure Matrix Notifier bridge",
	})
	if err != nil {
		return
	}

	return resp.DeviceID, resp.AccessToken, nil
}
