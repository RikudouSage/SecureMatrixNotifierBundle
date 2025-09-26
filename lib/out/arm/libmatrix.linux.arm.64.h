extern char* SendMessage(char* messageType, char* renderingType, char* message, char* recipient, char* databaseDsn, char* accessToken, char* recoveryKey, char* pickleKey, char* url, char* deviceId, char** err);
extern void Login(char* homeserver, char* username, char* password, char** err, char** deviceId, char** accessToken);
