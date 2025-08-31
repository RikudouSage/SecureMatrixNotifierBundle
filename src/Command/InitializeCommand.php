<?php

namespace Rikudou\MatrixNotifier\Command;

use Rikudou\MatrixNotifier\Bridge\GolangLibBridge;
use SensitiveParameter;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rikudou:notifier:matrix:initialize-keys',
    description: 'Initializes the various keys needed for the notifier to work correctly, namely access key, pickle key and device id.'
)]
final readonly class InitializeCommand
{
    public function __construct(
        private GolangLibBridge $bridge,
        private ?string $serverUrl,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'You can skip this argument and the command will ask you for your username interactively.')]
        ?string $username = null,
        #[Option(description: 'Prefer not providing the password using this option and let the command ask you interactively instead.')]
        #[SensitiveParameter]
        ?string $password = null,
        #[Option(description: 'Must be provided if not configured inside the config file')]
        ?string $serverUrl = null,
    ): int {
        $serverUrl ??= $this->serverUrl ?? $io->ask('Server URL');
        $scheme = parse_url($serverUrl, PHP_URL_SCHEME);
        if (!$scheme) {
            $serverUrl = "https://{$serverUrl}";
        } else if ($scheme !== 'https') {
            $io->error('Only https URLs are supported.');
            return Command::FAILURE;
        }

        $username ??= $io->ask('Username');
        if (!$username) {
            $io->error("The username cannot be empty.");
            return Command::FAILURE;
        }
        $password ??= $io->askHidden('Password');
        if (!$password) {
            $io->error("The password cannot be empty.");
            return Command::FAILURE;
        }

        $result = $this->bridge->login(
            homeserver: $serverUrl,
            username: $username,
            password: $password,
        );

        $pickleKey = bin2hex(random_bytes(32));
        $io->success("Access token: {$result->accessToken}\nDevice ID: {$result->deviceId}\nPickle key: {$pickleKey}");
        return Command::SUCCESS;
    }
}
