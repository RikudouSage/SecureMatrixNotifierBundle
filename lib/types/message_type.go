package types

type MessageType string

const (
	MessageTypeTextMessage MessageType = "m.text"
	MessageTypeNotice      MessageType = "m.notice"
)
