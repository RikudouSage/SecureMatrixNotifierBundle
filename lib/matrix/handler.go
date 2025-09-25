package matrix

import (
	"context"
	"fmt"
	"lib/types"
	"sync"

	"maunium.net/go/mautrix"
	"maunium.net/go/mautrix/event"
	"maunium.net/go/mautrix/format"
	"maunium.net/go/mautrix/id"
)

func SendMessage(
	messageType types.MessageType,
	renderingType types.RenderingType,
	message string,
	recipient string,
	databasePath string,
	accessToken string,
	recoveryKey string,
	pickleKey []byte,
	url string,
	deviceId id.DeviceID,
	clientFactory MautrixFactory,
) (messageId string, err error) {
	if clientFactory == nil {
		clientFactory = func() (*mautrix.Client, error) {
			return mautrix.NewClient(url, "", accessToken)
		}
	}

	client, err := clientFactory()
	if err != nil {
		return
	}
	whoami, err := client.Whoami(context.Background())
	if err != nil {
		return
	}
	client.UserID = whoami.UserID

	syncer := mautrix.NewDefaultSyncer()

	client.DeviceID = deviceId
	client.Syncer = syncer

	crypto, err := initializeEncryption(client, pickleKey, databasePath)
	if err != nil {
		return
	}

	roomId, err := resolveRecipient(client, recipient)
	if err != nil {
		return
	}

	readyChan := make(chan error)
	var onceSetupEncryption sync.Once

	syncer.OnSync(func(ctx context.Context, resp *mautrix.RespSync, since string) bool {
		onceSetupEncryption.Do(func() {
			defer close(readyChan)

			machine := crypto.Machine()
			keyId, keyData, err := machine.SSSS.GetDefaultKeyData(ctx)
			if err != nil {
				readyChan <- err
				return
			}
			key, err := keyData.VerifyRecoveryKey(keyId, recoveryKey)
			if err != nil {
				readyChan <- err
				return
			}
			err = machine.FetchCrossSigningKeysFromSSSS(ctx, key)
			if err != nil {
				readyChan <- err
				return
			}
			err = machine.SignOwnDevice(ctx, machine.OwnIdentity())
			if err != nil {
				readyChan <- err
				return
			}
			err = machine.SignOwnMasterKey(ctx)
			if err != nil {
				readyChan <- err
				return
			}

			readyChan <- nil
		})

		return true
	})

	errChan := make(chan error)
	go func() {
		if err := client.Sync(); err != nil {
			errChan <- err
			close(errChan)
		}
	}()
	defer client.StopSync()

	err = <-readyChan
	if err != nil {
		return
	}

	respChan := make(chan *mautrix.RespSendEvent)
	go func() {
		defer close(respChan)

		var response *mautrix.RespSendEvent
		var err error

		_, err = client.State(context.Background(), roomId)
		if err != nil {
			errChan <- err
			close(errChan)
			return
		}

		switch messageType {
		case types.MessageTypeTextMessage:
			var content event.MessageEventContent
			switch renderingType {
			case types.RenderingTypeHtml:
				content = format.HTMLToContent(message)
				break
			case types.RenderingTypeMarkdown:
				content = format.RenderMarkdown(message, true, true)
				break
			case types.RenderingTypePlainText:
				content = format.TextToContent(message)
				break
			default:
				errChan <- fmt.Errorf("unsupported rendering type: %s", renderingType)
				close(errChan)
				return
			}

			response, err = client.SendMessageEvent(
				context.Background(),
				roomId,
				event.EventMessage,
				content,
			)
			break
		case types.MessageTypeNotice:
			response, err = client.SendNotice(context.Background(), roomId, message)
			break
		default:
			err = fmt.Errorf("unsupported message type: %s", messageType)
			break
		}

		if err != nil {
			errChan <- err
			close(errChan)
			return
		}

		respChan <- response
	}()

	select {
	case err = <-errChan:
		break
	case response := <-respChan:
		messageId = string(response.EventID)
		break
	}

	return
}
