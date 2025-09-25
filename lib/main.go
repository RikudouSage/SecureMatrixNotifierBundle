package main

// #cgo LDFLAGS: -L${SRCDIR}/out -lolm -Wl,-rpath,'$ORIGIN'
import "C"
import (
	"lib/matrix"
	"lib/types"

	"maunium.net/go/mautrix/id"
)

//export SendMessage
func SendMessage(
	messageType *C.char,
	renderingType *C.char,
	message *C.char,
	recipient *C.char,
	databasePath *C.char,
	accessToken *C.char,
	recoveryKey *C.char,
	pickleKey *C.char,
	url *C.char,
	deviceId *C.char,
	err **C.char,
) *C.char {
	result, sendErr := matrix.SendMessage(
		types.MessageType(C.GoString(messageType)),
		types.RenderingType(C.GoString(renderingType)),
		C.GoString(message),
		C.GoString(recipient),
		C.GoString(databasePath),
		C.GoString(accessToken),
		C.GoString(recoveryKey),
		[]byte(C.GoString(pickleKey)),
		C.GoString(url),
		id.DeviceID(C.GoString(deviceId)),
		nil,
	)

	if sendErr != nil {
		*err = C.CString(sendErr.Error())
	}

	return C.CString(result)
}

//export Login
func Login(homeserver, username, password *C.char, err **C.char, deviceId **C.char, accessToken **C.char) {
	deviceIdStr, accessTokenStr, errLogin := matrix.Login(
		C.GoString(homeserver),
		C.GoString(username),
		C.GoString(password),
	)

	if errLogin != nil {
		*err = C.CString(errLogin.Error())
		return
	}

	*deviceId = C.CString(string(deviceIdStr))
	*accessToken = C.CString(accessTokenStr)
}

func main() {}
