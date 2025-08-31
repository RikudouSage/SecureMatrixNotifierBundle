build-linux-x64:
	GOOS=linux GOARCH=amd64 CGO_ENABLED=1 go build -buildmode c-shared -o out/libmatrix.linux.x86.64.so .

build-linux-aarch64:
	GOOS=linux GOARCH=arm64 CGO_ENABLED=1 go build -buildmode c-shared -o out/libmatrix.linux.arm.64.so .
