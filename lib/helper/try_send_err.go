package helper

func TrySendErr(errChan chan<- error, err error) {
	select {
	case errChan <- err:
	default:
	}
}
