<?php

declare(strict_types=1);

namespace Rikudou\MatrixNotifier\Tests\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rikudou\MatrixNotifier\Enum\MessageType;
use Rikudou\MatrixNotifier\Enum\RenderingType;
use Rikudou\MatrixNotifier\Options\MatrixOptions;

#[CoversClass(MatrixOptions::class)]
final class MatrixOptionsTest extends TestCase
{
    public function testToArrayContainsAllValues(): void
    {
        $options = new MatrixOptions(
            recipientId: '@john:example.com',
            messageType: MessageType::Notice,
            renderingType: RenderingType::Markdown,
        );

        $this->assertSame(
            [
                'recipientId' => '@john:example.com',
                'messageType' => MessageType::Notice->value,
                'renderingType' => RenderingType::Markdown->value,
            ],
            $options->toArray(),
        );
    }

    public function testDefaultValuesAreUsedWhenNotExplicitlyProvided(): void
    {
        $options = new MatrixOptions();

        $this->assertNull($options->getRecipientId());
        $this->assertSame(MessageType::TextMessage, $options->messageType);
        $this->assertSame(RenderingType::PlainText, $options->renderingType);

        $this->assertSame(
            [
                'recipientId' => null,
                'messageType' => MessageType::TextMessage->value,
                'renderingType' => RenderingType::PlainText->value,
            ],
            $options->toArray(),
        );
    }

    public function testGetRecipientIdReturnsConfiguredValue(): void
    {
        $options = new MatrixOptions(recipientId: '@alice:example.com');

        $this->assertSame('@alice:example.com', $options->getRecipientId());
    }
}
