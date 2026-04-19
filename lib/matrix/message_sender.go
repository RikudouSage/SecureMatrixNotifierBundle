package matrix

import (
	"context"
	"fmt"
	"lib/helper"
	"lib/types"

	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/event"
	"maunium.net/go/mautrix/id"
)

type messageSender interface {
	Whoami(ctx context.Context) (*mautrix.RespWhoami, error)
	Sync() error
	StopSync()
	State(ctx context.Context, roomID id.RoomID) (mautrix.RoomStateMap, error)
	SendMessageEvent(ctx context.Context, roomID id.RoomID, eventType event.Type, contentJSON interface{}, extra ...mautrix.ReqSendEvent) (*mautrix.RespSendEvent, error)
	SendNotice(ctx context.Context, roomID id.RoomID, text string) (*mautrix.RespSendEvent, error)
}

// TODO: Refactor this channel/goroutine coordination into a simpler flow with fewer moving parts.
func sendMessageWithSync(client messageSender, roomId id.RoomID, messageType types.MessageType, renderingType types.RenderingType, message string, errChan <-chan error) (*mautrix.RespSendEvent, error) {
	respChan := make(chan *mautrix.RespSendEvent, 1)
	sendErrChan := make(chan error, 1)

	go func() {
		response, err := sendMessage(client, roomId, messageType, renderingType, message)
		if err != nil {
			helper.TrySendErr(sendErrChan, err)
			return
		}

		respChan <- response
	}()

	select {
	case err := <-errChan:
		return nil, err
	case err := <-sendErrChan:
		return nil, err
	case response := <-respChan:
		return response, nil
	}
}

func sendMessage(client messageSender, roomId id.RoomID, messageType types.MessageType, renderingType types.RenderingType, message string) (*mautrix.RespSendEvent, error) {
	_, err := client.State(context.Background(), roomId)
	if err != nil {
		return nil, err
	}

	switch messageType {
	case types.MessageTypeTextMessage:
		content, err := createMessageContent(renderingType, message)
		if err != nil {
			return nil, err
		}

		return client.SendMessageEvent(
			context.Background(),
			roomId,
			event.EventMessage,
			content,
		)
	case types.MessageTypeNotice:
		return client.SendNotice(context.Background(), roomId, message)
	default:
		return nil, fmt.Errorf("unsupported message type: %s", messageType)
	}
}
