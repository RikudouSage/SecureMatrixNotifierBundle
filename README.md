Common errors:

- `failed to unmarshal response body: unexpected end of JSON input`: your matrix server url is wrong and the http
    client instead fetched a html page which the bridge then tried to parse as a json
- `olm account is not marked as shared, but there are keys on the server`: the internal bridge database
    is not in sync, meaning you most likely deleted it. You need to login again and provide a new device id
    and access token
