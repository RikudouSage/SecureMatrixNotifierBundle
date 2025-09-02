package helper

func ToPointer[T any](input T) *T {
	return &input
}
