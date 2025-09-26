package db

import "database/sql"

var providerList []Provider

type Provider interface {
	supports(dsn string) bool
	GetDb(dsn string) (*sql.DB, error)
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
