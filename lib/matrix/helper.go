package matrix

func waitUntilReady(readyChan <-chan error, errChan <-chan error) error {
	select {
	case err := <-readyChan:
		return err
	case err := <-errChan:
		return err
	}
}
