<?php

declare(strict_types=1);

namespace Rikudou\MatrixNotifier\Tests\Transport;

use LogicException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rikudou\MatrixNotifier\Bridge\BridgeMessage;
use Rikudou\MatrixNotifier\Bridge\GolangLibBridge;
use Rikudou\MatrixNotifier\Enum\MessageType;
use Rikudou\MatrixNotifier\Enum\RenderingType;
use Rikudou\MatrixNotifier\Exception\MatrixException;
use Rikudou\MatrixNotifier\Options\MatrixOptions;
use Rikudou\MatrixNotifier\Transport\MatrixTransport;
use Symfony\Component\Notifier\Bridge\Matrix\MatrixOptions as SymfonyMatrixOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Message\MessageOptionsInterface;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(MatrixTransport::class)]
final class MatrixTransportTest extends TestCase
{
    public function testSendChatMessageWithMatrixOptions(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame(MessageType::Notice, $message->messageType);
                $this->assertSame(RenderingType::Html, $message->renderingType);
                $this->assertSame('Hello Matrix', $message->message);
                $this->assertSame('@john:example.com', $message->recipient);
                $this->assertSame('sqlite:///var/matrix.db', $message->databaseDsn);
                $this->assertSame('access-token', $message->accessToken);
                $this->assertSame('recovery-key', $message->recoveryKey);
                $this->assertSame('pickle-key', $message->pickleKey);
                $this->assertSame('DEVICEID', $message->deviceId);
                $this->assertSame('https://matrix.example.com:8448', $message->url);

                return true;
            }))
            ->willReturn('$123');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );
        $transport->setHost('matrix.example.com');
        $transport->setPort(8448);

        $message = new ChatMessage('Hello Matrix', new MatrixOptions(
            recipientId: '@john:example.com',
            messageType: MessageType::Notice,
            renderingType: RenderingType::Html,
        ));

        $sentMessage = $transport->send($message);

        $this->assertSame('$123', $sentMessage->getMessageId());
    }

    public function testSendConvertsSymfonyMatrixOptions(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame(MessageType::TextMessage, $message->messageType);
                $this->assertSame(RenderingType::Html, $message->renderingType);
                $this->assertSame('@alice:example.com', $message->recipient);

                return true;
            }))
            ->willReturn('event-id');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $message = new ChatMessage('Body', new SymfonyMatrixOptions([
            'recipient_id' => '@alice:example.com',
            'msgtype' => MessageType::TextMessage->value,
            'format' => 'org.matrix.custom.html',
        ]));

        $sentMessage = $transport->send($message);

        $this->assertSame('event-id', $sentMessage->getMessageId());
    }

    public function testSendConvertsSymfonyMatrixOptionsUsingDefaultValuesWhenMissing(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame(MessageType::TextMessage, $message->messageType);
                $this->assertSame(RenderingType::PlainText, $message->renderingType);
                $this->assertSame('@bob:example.com', $message->recipient);

                return true;
            }))
            ->willReturn('event-id');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $message = new ChatMessage('Body', new SymfonyMatrixOptions([
            'recipient_id' => '@bob:example.com',
        ]));

        $sentMessage = $transport->send($message);

        $this->assertSame('event-id', $sentMessage->getMessageId());
    }

    public function testSendThrowsOnUnsupportedMessageType(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->never())->method('send');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $message = new ChatMessage('Body', new SymfonyMatrixOptions([
            'recipient_id' => '@alice:example.com',
            'msgtype' => 'invalid-type',
        ]));

        $this->expectException(MatrixException::class);
        $this->expectExceptionMessage('Unsupported message type: invalid-type');

        $transport->send($message);
    }

    public function testSendChatMessageWithMatrixOptionsUsesDefaultValues(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame(MessageType::TextMessage, $message->messageType);
                $this->assertSame(RenderingType::PlainText, $message->renderingType);
                $this->assertSame('@john:example.com', $message->recipient);

                return true;
            }))
            ->willReturn('event-id');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $message = new ChatMessage('Hello Matrix', new MatrixOptions('@john:example.com'));

        $sentMessage = $transport->send($message);

        $this->assertSame('event-id', $sentMessage->getMessageId());
    }

    public function testSendUsesDefaultRecipientWhenMessageHasNoRecipient(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame('@default:example.com', $message->recipient);
                $this->assertSame(MessageType::TextMessage, $message->messageType);
                $this->assertSame(RenderingType::PlainText, $message->renderingType);

                return true;
            }))
            ->willReturn('event-id');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $message = new ChatMessage('Body');

        $sentMessage = $transport->send($message);

        $this->assertSame('event-id', $sentMessage->getMessageId());
    }

    public function testSendUsesDefaultRecipientWhenGenericOptionsDoNotProvideRecipient(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame('@default:example.com', $message->recipient);

                return true;
            }))
            ->willReturn('event-id');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $message = new ChatMessage('Body', new class() implements MessageOptionsInterface {
            public function toArray(): array
            {
                return [];
            }

            public function getRecipientId(): ?string
            {
                return null;
            }
        });

        $sentMessage = $transport->send($message);

        $this->assertSame('event-id', $sentMessage->getMessageId());
    }

    public function testSendUsesDefaultRecipientWhenSymfonyOptionsDoNotProvideRecipient(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame('@default:example.com', $message->recipient);
                $this->assertSame(MessageType::TextMessage, $message->messageType);
                $this->assertSame(RenderingType::PlainText, $message->renderingType);

                return true;
            }))
            ->willReturn('event-id');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $message = new ChatMessage('Body', new SymfonyMatrixOptions([]));

        $sentMessage = $transport->send($message);

        $this->assertSame('event-id', $sentMessage->getMessageId());
    }

    public function testSendPrefersOptionsRecipientOverMessageRecipientAndDefaultRecipient(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame('@options:example.com', $message->recipient);

                return true;
            }))
            ->willReturn('event-id');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $message = $this->getMockBuilder(ChatMessage::class)
            ->setConstructorArgs(['Body', new MatrixOptions('@options:example.com')])
            ->onlyMethods(['getRecipientId'])
            ->getMock();
        $message->method('getRecipientId')->willReturn('@message:example.com');

        $sentMessage = $transport->send($message);

        $this->assertSame('event-id', $sentMessage->getMessageId());
    }

    public function testSendPrefersMessageRecipientOverDefaultRecipientWhenOptionsDoNotProvideRecipient(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame('@message:example.com', $message->recipient);

                return true;
            }))
            ->willReturn('event-id');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $message = $this->getMockBuilder(ChatMessage::class)
            ->setConstructorArgs(['Body', new MatrixOptions()])
            ->onlyMethods(['getRecipientId'])
            ->getMock();
        $message->method('getRecipientId')->willReturn('@message:example.com');

        $sentMessage = $transport->send($message);

        $this->assertSame('event-id', $sentMessage->getMessageId());
    }

    public function testSendThrowsWhenRecipientMissing(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->never())->method('send');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: null,
        );

        $message = new ChatMessage('Body', new MatrixOptions());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Recipient id is required.');

        $transport->send($message);
    }

    public function testSendWithGenericOptions(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame(MessageType::TextMessage, $message->messageType);
                $this->assertSame(RenderingType::PlainText, $message->renderingType);
                $this->assertSame('@generic:example.com', $message->recipient);

                return true;
            }))
            ->willReturn('event-id');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $message = new ChatMessage('Body', new class('@generic:example.com') implements MessageOptionsInterface {
            public function __construct(
                private readonly string $recipientId,
            ) {
            }

            public function toArray(): array
            {
                return [
                    'recipient_id' => $this->recipientId,
                ];
            }

            public function getRecipientId(): ?string
            {
                return $this->recipientId;
            }
        });

        $sentMessage = $transport->send($message);

        $this->assertSame('event-id', $sentMessage->getMessageId());
    }

    public function testSendThrowsOnNonChatMessage(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->never())->method('send');

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $this->expectException(UnsupportedMessageTypeException::class);

        $transport->send(new SmsMessage('123', 'Body'));
    }

    public function testSupportsOnlyMatrixChatMessages(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);

        $transport = new MatrixTransport(
            accessToken: 'access-token',
            recoveryKey: 'recovery-key',
            pickleKey: 'pickle-key',
            deviceId: 'DEVICEID',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
            defaultRecipient: '@default:example.com',
        );

        $this->assertTrue($transport->supports(new ChatMessage('body', new MatrixOptions('@user:example.com'))));
        $this->assertTrue($transport->supports(new ChatMessage('body')));
        $this->assertTrue($transport->supports(new ChatMessage('body', new SymfonyMatrixOptions([]))));
        $this->assertFalse($transport->supports(new ChatMessage('body', new class() implements MessageOptionsInterface {
            public function toArray(): array
            {
                return [];
            }

            public function getRecipientId(): ?string
            {
                return null;
            }
        })));
        $this->assertFalse($transport->supports(new SmsMessage('123', 'body')));
    }
}
