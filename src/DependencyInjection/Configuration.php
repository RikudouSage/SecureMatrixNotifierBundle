<?php

namespace Rikudou\MatrixNotifier\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final readonly class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('rikudou_matrix_notifier');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->stringNode('database_path')
                    ->info('The path to the SQLite database which the Matrix maintains internally, it holds stuff such as room state, cryptography configuration and is needed for the bridge to function. If you lose this database, you must login again and get a new device ID.')
                    ->defaultValue('%kernel.project_dir%/var/matrix_notifier/matrix_internal.sqlite3')
                ->end()
                ->stringNode('pickle_key')
                    ->info('Should be a random string of 32 bytes (can be more, but the Matrix bridge truncates it internally), used for encrypting/decrypting local account data. You can use the rikudou:notifier:matrix:initialize-keys command to generate a secure random string.')
                ->end()
                ->stringNode('device_id')
                    ->info('A unique ID of the device, usually obtained by logging in. You can use the rikudou:notifier:matrix:initialize-keys command to login and generate a device ID.')
                ->end()
                ->stringNode('access_token')
                    ->info('An access token to use with the api, usually obtained by logging in. You can use the rikudou:notifier:matrix:initialize-keys command to login and generate an access token. Can be also set as part of the notifier DSN for compatibility purposes.')
                ->end()
                ->stringNode('recovery_key')
                    ->info('The recovery key for the bot account, the easiest way to get it is to login to the account using Element and copying it from there (or setting it up if you have not yet). Note that this is the most sensitive secret a Matrix account has (even more than your password), treat it with care.')
                ->end()
                ->stringNode('server_hostname')
                    ->info('The base server url (aka hostname, optionally a port, WITHOUT scheme). Only needed if you plan to use the rikudou:notifier:matrix:initialize-keys command. Can be called as rikudou.matrix_notifier.server_hostname parameter')
                ->end()
                ->stringNode('default_recipient')
                    ->info('The default recipient when no recipient is set directly')
                    ->defaultNull()
                ->end()
                ->arrayNode('lib')
                    ->addDefaultsIfNotSet()
                    ->info('You can customize the .so/.h library paths.')
                    ->children()
                        ->stringNode('library_path')
                            ->info('The path to the .so library, leave at null to use the bundled one.')
                            ->defaultNull()
                        ->end()
                        ->stringNode('headers_path')
                            ->info('Path to the library headers, leave at null to use the bundled one.')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
