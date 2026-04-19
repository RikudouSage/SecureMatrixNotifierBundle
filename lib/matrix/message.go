package matrix

import (
	"fmt"
	"lib/types"

	"maunium.net/go/mautrix/event"
	"maunium.net/go/mautrix/format"
)

func createMessageContent(renderingType types.RenderingType, message string) (event.MessageEventContent, error) {
	switch renderingType {
	case types.RenderingTypeHtml:
		return format.HTMLToContent(message), nil
	case types.RenderingTypeMarkdown:
		return format.RenderMarkdown(message, true, true), nil
	case types.RenderingTypePlainText:
		return format.TextToContent(message), nil
	default:
		return event.MessageEventContent{}, fmt.Errorf("unsupported rendering type: %s", renderingType)
	}
}
