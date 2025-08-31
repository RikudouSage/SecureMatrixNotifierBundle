<?php

namespace Rikudou\MatrixNotifier\Transport;

use LogicException;
use Rikudou\MatrixNotifier\Bridge\BridgeMessage;
use Rikudou\MatrixNotifier\Bridge\GolangLibBridge;
use Rikudou\MatrixNotifier\Enum\MessageType;
use Rikudou\MatrixNotifier\Enum\RenderingType;
use Rikudou\MatrixNotifier\Options\MatrixOptions;
use SensitiveParameter;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Exception\UnsupportedOptionsException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MatrixTransport extends AbstractTransport
{
    public function __construct(
        #[SensitiveParameter] private readonly string $accessToken,
        #[SensitiveParameter] private readonly string $recoveryKey,
        #[SensitiveParameter] private readonly string $pickleKey,
        private readonly string $deviceId,
        private readonly string $dbPath,
        private readonly GolangLibBridge $bridge,
        ?HttpClientInterface $client = null,
        ?EventDispatcherInterface $dispatcher = null,
    ) {
        parent::__construct($client, $dispatcher);
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof ChatMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, ChatMessage::class, $message);
        }

        if (($options = $message->getOptions()) && !$message->getOptions() instanceof MatrixOptions) {
            throw new UnsupportedOptionsException(__CLASS__, MatrixOptions::class, $options);
        }
        assert($options instanceof MatrixOptions || $options === null);

        if (!$message->getRecipientId()) {
            throw new LogicException('Recipient id is required.');
        }

        $bridgeMessage = new BridgeMessage(
            messageType: $options?->messageType ?? MessageType::TextMessage,
            renderingType: $options?->renderingType ?? RenderingType::PlainText,
            message: $message->getSubject(),
            recipient: $message->getRecipientId(),
            databasePath: $this->dbPath,
            accessToken: $this->accessToken,
            recoveryKey: $this->recoveryKey,
            pickleKey: $this->pickleKey,
            deviceId: $this->deviceId,
            url: "https://{$this->getEndpoint()}",
        );

        $result = $this->bridge->send($bridgeMessage);
        $sent = new SentMessage($message, (string) $this);
        $sent->setMessageId($result);

        return $sent;
    }

    public function __toString(): string
    {
        return "matrix://{$this->getEndpoint()}";
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof ChatMessage && ($message->getOptions() === null || $message->getOptions() instanceof MatrixOptions);
    }
}
