package types

type RenderingType string

const (
	RenderingTypePlainText RenderingType = "text"
	RenderingTypeHtml      RenderingType = "html"
	RenderingTypeMarkdown  RenderingType = "markdown"
)
