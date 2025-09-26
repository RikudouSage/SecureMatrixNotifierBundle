<?php

namespace Rikudou\MatrixNotifier\Bridge;

use Rikudou\MatrixNotifier\Enum\MessageType;
use Rikudou\MatrixNotifier\Enum\RenderingType;
use SensitiveParameter;

/**
 * @internal
 */
final readonly class BridgeMessage
{
    public function __construct(
        public MessageType $messageType,
        public RenderingType $renderingType,
        public string $message,
        public string $recipient,
        public string $databaseDsn,
        #[SensitiveParameter] public string $accessToken,
        #[SensitiveParameter] public string $recoveryKey,
        #[SensitiveParameter] public string $pickleKey,
        public string $deviceId,
        public string $url,
    ) {
    }
}
