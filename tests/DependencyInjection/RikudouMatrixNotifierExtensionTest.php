<?php

declare(strict_types=1);

namespace Rikudou\MatrixNotifier\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rikudou\MatrixNotifier\DependencyInjection\RikudouMatrixNotifierExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(RikudouMatrixNotifierExtension::class)]
final class RikudouMatrixNotifierExtensionTest extends TestCase
{
    public function testLoadRegistersDefaultParameters(): void
    {
        $container = new ContainerBuilder();

        $extension = new RikudouMatrixNotifierExtension();
        $extension->load([], $container);

        $this->assertSame(
            '%kernel.project_dir%/var/matrix_notifier/matrix_internal.sqlite3',
            $container->getParameter('rikudou.internal.matrix.database_dsn'),
        );
        $this->assertNull($container->getParameter('rikudou.internal.matrix.pickle_key'));
        $this->assertNull($container->getParameter('rikudou.internal.matrix.device_id'));
        $this->assertNull($container->getParameter('rikudou.internal.matrix.access_token'));
        $this->assertNull($container->getParameter('rikudou.internal.matrix.recovery_key'));
        $this->assertNull($container->getParameter('rikudou.matrix_notifier.server_hostname'));
        $this->assertNull($container->getParameter('rikudou.matrix_notifier.server_url'));
        $this->assertNull($container->getParameter('rikudou.internal.matrix.lib_path'));
        $this->assertNull($container->getParameter('rikudou.internal.matrix.headers_path'));
        $this->assertNull($container->getParameter('rikudou.internal.matrix.default_recipient'));
    }

    public function testLoadRegistersCustomParameters(): void
    {
        $container = new ContainerBuilder();

        $extension = new RikudouMatrixNotifierExtension();
        $extension->load([
            [
                'database_dsn' => 'sqlite:///custom.sqlite3',
                'pickle_key' => 'pickle',
                'device_id' => 'DEVICE',
                'access_token' => 'token',
                'recovery_key' => 'recovery',
                'server_hostname' => 'matrix.example.com',
                'default_recipient' => '@bot:example.com',
                'lib' => [
                    'library_path' => '/opt/libmatrix.so',
                    'headers_path' => '/opt/libmatrix.h',
                ],
            ],
        ], $container);

        $this->assertSame('sqlite:///custom.sqlite3', $container->getParameter('rikudou.internal.matrix.database_dsn'));
        $this->assertSame('pickle', $container->getParameter('rikudou.internal.matrix.pickle_key'));
        $this->assertSame('DEVICE', $container->getParameter('rikudou.internal.matrix.device_id'));
        $this->assertSame('token', $container->getParameter('rikudou.internal.matrix.access_token'));
        $this->assertSame('recovery', $container->getParameter('rikudou.internal.matrix.recovery_key'));
        $this->assertSame('matrix.example.com', $container->getParameter('rikudou.matrix_notifier.server_hostname'));
        $this->assertSame('https://matrix.example.com', $container->getParameter('rikudou.matrix_notifier.server_url'));
        $this->assertSame('/opt/libmatrix.so', $container->getParameter('rikudou.internal.matrix.lib_path'));
        $this->assertSame('/opt/libmatrix.h', $container->getParameter('rikudou.internal.matrix.headers_path'));
        $this->assertSame('@bot:example.com', $container->getParameter('rikudou.internal.matrix.default_recipient'));
    }
}
