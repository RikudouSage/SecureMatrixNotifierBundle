package matrix

import "maunium.net/go/mautrix"

type MautrixFactory func() (*mautrix.Client, error)
