<?php

declare(strict_types=1);

namespace Rikudou\MatrixNotifier\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rikudou\MatrixNotifier\Bridge\GolangLibBridge;
use Rikudou\MatrixNotifier\Bridge\LoginResponse;
use Rikudou\MatrixNotifier\Command\InitializeCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[CoversClass(InitializeCommand::class)]
final class InitializeCommandTest extends TestCase
{
    public function testInvokeRequestsMissingDataAndDisplaysSuccess(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->once())
            ->method('login')
            ->with('https://matrix.example.com', 'john', 's3cret')
            ->willReturn(new LoginResponse('token123', 'DEVICE123'));

        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('ask')
            ->with('Username')
            ->willReturn('john');
        $style->expects($this->once())
            ->method('askHidden')
            ->with('Password')
            ->willReturn('s3cret');
        $style->expects($this->never())
            ->method('error');
        $style->expects($this->once())
            ->method('success')
            ->with($this->callback(function (string $message): bool {
                $this->assertStringContainsString('Access token: token123', $message);
                $this->assertStringContainsString('Device ID: DEVICE123', $message);
                $this->assertMatchesRegularExpression('/Pickle key: [0-9a-f]{64}/', $message);

                return true;
            }));

        $command = new InitializeCommand($bridge, null);

        $result = $command($style, null, null, 'matrix.example.com');

        $this->assertSame(Command::SUCCESS, $result);
    }

    public function testInvokeRejectsNonHttpsUrl(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->never())
            ->method('login');

        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('error')
            ->with('Only https URLs are supported.');
        $style->expects($this->never())
            ->method('ask');
        $style->expects($this->never())
            ->method('askHidden');

        $command = new InitializeCommand($bridge, null);

        $result = $command($style, 'user', 'pass', 'http://matrix.example.com');

        $this->assertSame(Command::FAILURE, $result);
    }

    public function testInvokeRejectsEmptyUsername(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->never())
            ->method('login');

        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('ask')
            ->with('Username')
            ->willReturn('');
        $style->expects($this->once())
            ->method('error')
            ->with('The username cannot be empty.');
        $style->expects($this->never())
            ->method('askHidden');

        $command = new InitializeCommand($bridge, null);

        $result = $command($style, null, 'pass', 'https://matrix.example.com');

        $this->assertSame(Command::FAILURE, $result);
    }

    public function testInvokeRejectsEmptyPassword(): void
    {
        $bridge = $this->createMock(GolangLibBridge::class);
        $bridge->expects($this->never())
            ->method('login');

        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->never())
            ->method('ask');
        $style->expects($this->once())
            ->method('askHidden')
            ->with('Password')
            ->willReturn('');
        $style->expects($this->once())
            ->method('error')
            ->with('The password cannot be empty.');

        $command = new InitializeCommand($bridge, null);

        $result = $command($style, 'user', null, 'https://matrix.example.com');

        $this->assertSame(Command::FAILURE, $result);
    }
}
