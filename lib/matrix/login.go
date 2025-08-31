package matrix

import (
	"context"

	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/id"
)

func Login(homeserver string, username string, password string) (deviceId id.DeviceID, accessToken string, err error) {
	client, err := mautrix.NewClient(homeserver, "", "")
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
