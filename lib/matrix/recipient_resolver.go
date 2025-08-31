package matrix

import (
	"context"
	"errors"

	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/id"
)

func resolveRecipient(client *mautrix.Client, recipient string) (id.RoomID, error) {
	first := recipient[0]

	if first == '!' {
		return id.RoomID(recipient), nil
	}

	if first == '@' {
		return resolveDirectMessageRecipient(client, recipient)
	}

	if first == '#' {
		return resolveRoomAliasRecipient(client, recipient)
	}

	return "", errors.New("unknown recipient: " + recipient)
}

func resolveDirectMessageRecipient(client *mautrix.Client, recipient string) (id.RoomID, error) {
	resp, err := client.Whoami(context.Background())
	if err != nil {
		return "", err
	}

	_ = resp.UserID
	var out any
	err = client.GetAccountData(context.Background(), "m.direct", &out)
	if err != nil {
		return "", err
	}

	return "", errors.New("not implemented yet")
}

func resolveRoomAliasRecipient(client *mautrix.Client, recipient string) (id.RoomID, error) {
	resp, err := client.ResolveAlias(context.Background(), id.RoomAlias(recipient))
	if err != nil {
		return "", err
	}

	return resp.RoomID, nil
}
