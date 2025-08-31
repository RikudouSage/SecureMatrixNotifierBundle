<?php

namespace Rikudou\MatrixNotifier\Bridge;

use FFI;
use Rikudou\MatrixNotifier\Exception\MatrixException;

/**
 * @internal
 */
final readonly class GolangLibBridge
{
    private FFI $ffi;

    public function __construct()
    {
        $libDir = __DIR__ . '/../../lib/out';

        $basePath = $this->getBaseFileName();
        $headerPath = "{$libDir}/{$basePath}.h";
        $soPath = "{$libDir}/{$basePath}.so";

        if (!file_exists($headerPath)) {
            throw new MatrixException(sprintf(
                "Cannot find header file for your OS (%s) and architecture (%s), you'll probably have to build the library yourself, see the documentation",
                PHP_OS_FAMILY,
                php_uname('m'),
            ));
        }

        $this->ffi = FFI::cdef(
            file_get_contents($headerPath) ?: throw new MatrixException("Failed to read the {$headerPath} file"),
            $soPath,
        );
    }

    public function send(BridgeMessage $bridgeMessage): string
    {
        try {
            $err = $this->ffi->new('char*');
            $result = $this->ffi->SendMessage(
                $bridgeMessage->messageType->value,
                $bridgeMessage->renderingType->value,
                $bridgeMessage->message,
                $bridgeMessage->recipient,
                $bridgeMessage->databasePath,
                $bridgeMessage->accessToken,
                $bridgeMessage->recoveryKey,
                $bridgeMessage->pickleKey,
                $bridgeMessage->url,
                $bridgeMessage->deviceId,
                FFI::addr($err),
            );

            if (!FFI::isNull($err)) {
                throw new MatrixException(FFI::string($err));
            }

            return FFI::string($result);
        } finally {
            if (isset($result)) {
                FFI::free($result);
            }
        }
    }

    public function login(string $homeserver, string $username, string $password): LoginResponse
    {
        $err = $this->ffi->new('char*');
        $deviceId = $this->ffi->new('char*');
        $accessToken = $this->ffi->new('char*');

        $this->ffi->Login(
            $homeserver,
            $username,
            $password,
            FFI::addr($err),
            FFI::addr($deviceId),
            FFI::addr($accessToken),
        );

        if (!FFI::isNull($err)) {
            throw new MatrixException(FFI::string($err));
        }

        return new LoginResponse(
            accessToken: FFI::string($accessToken),
            deviceId: FFI::string($deviceId),
        );
    }

    private function getBaseFileName(): string
    {
        $uname = php_uname('m');

        $parts = ['libmatrix'];
        $parts[] = strtolower(PHP_OS_FAMILY);

        if (str_contains($uname, 'arm') || str_contains($uname, 'aarch')) {
            $parts[] = 'arm';
        } else {
            $parts[] = 'x86';
        }

        $parts[] = PHP_INT_SIZE === 4 ? '32' : '64';

        return implode('.', $parts);
    }
}
