<?php

namespace Rikudou\MatrixNotifier\Options;

use Rikudou\MatrixNotifier\Enum\MessageType;
use Rikudou\MatrixNotifier\Enum\RenderingType;
use Symfony\Component\Notifier\Message\MessageOptionsInterface;

final readonly class MatrixOptions implements MessageOptionsInterface
{
    public function __construct(
        public ?string $recipientId = null,
        public MessageType $messageType = MessageType::TextMessage,
        public RenderingType $renderingType = RenderingType::PlainText,
    ) {
    }

    public function toArray(): array
    {
        return [
            'recipientId' => $this->recipientId,
            'messageType' => $this->messageType->value,
            'renderingType' => $this->renderingType->value,
        ];
    }

    public function getRecipientId(): ?string
    {
        return $this->recipientId;
    }
}
