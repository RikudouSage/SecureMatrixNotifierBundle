package db

import (
	"go.mau.fi/util/dbutil"
)

var providerList []Provider

type Provider interface {
	supports(dsn string) bool
	Get(dsn string) (*dbutil.Database, error)
}

func init() {
	providerList = make([]Provider, 0)
}

func FindProvider(dsn string) Provider {
	for _, provider := range providerList {
		if provider.supports(dsn) {
			return provider
		}
	}

	return nil
}
