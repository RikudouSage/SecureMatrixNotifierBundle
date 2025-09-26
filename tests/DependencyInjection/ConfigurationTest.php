<?php

declare(strict_types=1);

namespace Rikudou\MatrixNotifier\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rikudou\MatrixNotifier\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

#[CoversClass(Configuration::class)]
final class ConfigurationTest extends TestCase
{
    public function testDefaultConfiguration(): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration(), []);

        $this->assertSame('%kernel.project_dir%/var/matrix_notifier/matrix_internal.sqlite3', $config['database_dsn']);
        $this->assertArrayHasKey('lib', $config);
        $this->assertNull($config['lib']['library_path']);
        $this->assertNull($config['lib']['headers_path']);
        $this->assertArrayHasKey('default_recipient', $config);
        $this->assertNull($config['default_recipient']);
    }

    public function testCustomConfigurationOverridesDefaults(): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration(), [[
            'database_dsn' => 'sqlite:///custom.sqlite3',
            'pickle_key' => 'pickle',
            'device_id' => 'DEVICEID',
            'access_token' => 'access',
            'recovery_key' => 'recovery',
            'server_hostname' => 'matrix.example.com',
            'default_recipient' => '@bot:example.com',
            'lib' => [
                'library_path' => '/opt/libmatrix.so',
                'headers_path' => '/opt/libmatrix.h',
            ],
        ]]);

        $this->assertSame('sqlite:///custom.sqlite3', $config['database_dsn']);
        $this->assertSame('pickle', $config['pickle_key']);
        $this->assertSame('DEVICEID', $config['device_id']);
        $this->assertSame('access', $config['access_token']);
        $this->assertSame('recovery', $config['recovery_key']);
        $this->assertSame('matrix.example.com', $config['server_hostname']);
        $this->assertSame('@bot:example.com', $config['default_recipient']);
        $this->assertSame('/opt/libmatrix.so', $config['lib']['library_path']);
        $this->assertSame('/opt/libmatrix.h', $config['lib']['headers_path']);
    }
}
