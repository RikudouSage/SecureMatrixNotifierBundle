package matrix

import (
	"context"
	"errors"
	"lib/helper"

	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/event"
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
	var out map[string][]id.RoomID
	err := client.GetAccountData(context.Background(), "m.direct", &out)
	if err != nil {
		return "", err
	}

	if data, ok := out[recipient]; ok && len(data) > 0 {
		return data[len(data)-1], nil
	}

	respCreate, err := client.CreateRoom(context.Background(), &mautrix.ReqCreateRoom{
		Preset:   "trusted_private_chat",
		IsDirect: true,
		Invite: []id.UserID{
			id.UserID(recipient),
		},
		InitialState: []*event.Event{
			{
				Type:     event.StateEncryption,
				StateKey: helper.ToPointer(""),
				Content: event.Content{
					Parsed: map[string]any{
						"algorithm": id.AlgorithmMegolmV1,
					},
				},
			},
		},
	})
	if err != nil {
		return "", err
	}

	out[recipient] = append(out[recipient], respCreate.RoomID)
	_ = client.SetAccountData(context.Background(), "m.direct", out)

	return respCreate.RoomID, nil
}

func resolveRoomAliasRecipient(client *mautrix.Client, recipient string) (id.RoomID, error) {
	resp, err := client.ResolveAlias(context.Background(), id.RoomAlias(recipient))
	if err != nil {
		return "", err
	}

	return resp.RoomID, nil
}
