<?php

namespace Rikudou\MatrixNotifier\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class RikudouMatrixNotifierExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $configuration = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('rikudou.internal.matrix.database_path', $configuration['database_path']);
        $container->setParameter('rikudou.internal.matrix.pickle_key', $configuration['pickle_key'] ?? null);
        $container->setParameter('rikudou.internal.matrix.device_id', $configuration['device_id'] ?? null);
        $container->setParameter('rikudou.internal.matrix.access_token', $configuration['access_token'] ?? null);
        $container->setParameter('rikudou.internal.matrix.recovery_key', $configuration['recovery_key'] ?? null);
        $container->setParameter('rikudou.matrix_notifier.server_hostname', $configuration['server_hostname'] ?? null);
        $container->setParameter('rikudou.matrix_notifier.server_url', ($configuration['server_hostname'] ?? null) ? "https://{$configuration['server_hostname']}" : null);
        $container->setParameter('rikudou.internal.matrix.lib_path', $configuration['lib']['library_path'] ?? null);
        $container->setParameter('rikudou.internal.matrix.headers_path', $configuration['lib']['headers_path'] ?? null);
        $container->setParameter('rikudou.internal.matrix.default_recipient', $configuration['default_recipient'] ?? null);
    }
}
