<?php

declare(strict_types=1);

namespace Rikudou\MatrixNotifier\Tests\Transport;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rikudou\MatrixNotifier\Bridge\BridgeMessage;
use Rikudou\MatrixNotifier\Bridge\GolangLibBridge;
use Rikudou\MatrixNotifier\Enum\MessageType;
use Rikudou\MatrixNotifier\Enum\RenderingType;
use Rikudou\MatrixNotifier\Exception\MatrixException;
use Rikudou\MatrixNotifier\Options\MatrixOptions;
use Rikudou\MatrixNotifier\Transport\MatrixTransport;
use Rikudou\MatrixNotifier\Transport\MatrixTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Message\ChatMessage;

#[CoversClass(MatrixTransportFactory::class)]
final class MatrixTransportFactoryTest extends TestCase
{
    public function testCreateReturnsConfiguredTransport(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);

        $factory = new MatrixTransportFactory(
            pickleKey: 'pickle',
            deviceId: 'DEVICE',
            accessToken: 'config-token',
            recoveryKey: 'recovery',
            defaultRecipient: '@default:example.com',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
        );

        $dsn = new Dsn('smatrix://matrix.example.com:8448?accessToken=dsn-token');
        $transport = $factory->create($dsn);

        $this->assertInstanceOf(MatrixTransport::class, $transport);
        $this->assertSame('matrix://matrix.example.com:8448', (string) $transport);
    }

    public function testCreateUsesConfiguredAccessTokenWhenMissingFromDsn(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame('config-token', $message->accessToken);
                $this->assertSame(MessageType::TextMessage, $message->messageType);
                $this->assertSame(RenderingType::PlainText, $message->renderingType);

                return true;
            }))
            ->willReturn('bridge-result');

        $factory = new MatrixTransportFactory(
            pickleKey: 'pickle',
            deviceId: 'DEVICE',
            accessToken: 'config-token',
            recoveryKey: 'recovery',
            defaultRecipient: '@default:example.com',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
        );

        $transport = $factory->create(new Dsn('smatrix://matrix.example.com'));
        $message = new ChatMessage('Hello', new MatrixOptions('@john:example.com'));

        $sentMessage = $transport->send($message);

        $this->assertSame('bridge-result', $sentMessage->getMessageId());
    }

    public function testCreatePrefersAccessTokenFromDsnWhenProvided(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('send')
            ->with($this->callback(function (BridgeMessage $message): bool {
                $this->assertSame('dsn-token', $message->accessToken);
                $this->assertSame('@john:example.com', $message->recipient);

                return true;
            }))
            ->willReturn('event-id');

        $factory = new MatrixTransportFactory(
            pickleKey: 'pickle',
            deviceId: 'DEVICE',
            accessToken: 'config-token',
            recoveryKey: 'recovery',
            defaultRecipient: '@default:example.com',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $bridge,
        );

        $transport = $factory->create(new Dsn('smatrix://matrix.example.com?accessToken=dsn-token'));
        $message = new ChatMessage('Body', new MatrixOptions('@john:example.com'));

        $sentMessage = $transport->send($message);

        $this->assertSame('event-id', $sentMessage->getMessageId());
    }

    public function testCreateThrowsWhenMissingPickleKey(): void
    {
        $factory = new MatrixTransportFactory(
            pickleKey: null,
            deviceId: 'DEVICE',
            accessToken: 'config-token',
            recoveryKey: 'recovery',
            defaultRecipient: '@default:example.com',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $this->createMock(GolangLibBridge::class),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The pickle key is not initialized');

        $factory->create(new Dsn('smatrix://matrix.example.com'));
    }

    public function testCreateThrowsWhenMissingDeviceId(): void
    {
        $factory = new MatrixTransportFactory(
            pickleKey: 'pickle',
            deviceId: null,
            accessToken: 'config-token',
            recoveryKey: 'recovery',
            defaultRecipient: '@default:example.com',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $this->createMock(GolangLibBridge::class),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The device ID is not initialized');

        $factory->create(new Dsn('smatrix://matrix.example.com'));
    }

    public function testCreateThrowsWhenMissingRecoveryKey(): void
    {
        $factory = new MatrixTransportFactory(
            pickleKey: 'pickle',
            deviceId: 'DEVICE',
            accessToken: 'config-token',
            recoveryKey: null,
            defaultRecipient: '@default:example.com',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $this->createMock(GolangLibBridge::class),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The recovery key is not initialized');

        $factory->create(new Dsn('smatrix://matrix.example.com'));
    }

    public function testCreateThrowsWhenAccessTokenMissingEverywhere(): void
    {
        $factory = new MatrixTransportFactory(
            pickleKey: 'pickle',
            deviceId: 'DEVICE',
            accessToken: null,
            recoveryKey: 'recovery',
            defaultRecipient: '@default:example.com',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $this->createMock(GolangLibBridge::class),
        );

        $this->expectException(MatrixException::class);
        $this->expectExceptionMessage('The access token must be provided');

        $factory->create(new Dsn('smatrix://matrix.example.com'));
    }

    public function testCreateThrowsOnUnsupportedScheme(): void
    {
        $factory = new MatrixTransportFactory(
            pickleKey: 'pickle',
            deviceId: 'DEVICE',
            accessToken: 'config-token',
            recoveryKey: 'recovery',
            defaultRecipient: '@default:example.com',
            databaseDsn: 'sqlite:///var/matrix.db',
            bridge: $this->createMock(GolangLibBridge::class),
        );

        $this->expectException(UnsupportedSchemeException::class);

        $factory->create(new Dsn('other://matrix.example.com'));
    }
}
