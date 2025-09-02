<?php

namespace Rikudou\MatrixNotifier\Transport;

use LogicException;
use Rikudou\MatrixNotifier\Bridge\GolangLibBridge;
use Rikudou\MatrixNotifier\Exception\MatrixException;
use SensitiveParameter;
use Symfony\Component\Notifier\Bridge\Matrix\MatrixTransport as SymfonyMatrixTransport;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MatrixTransportFactory extends AbstractTransportFactory
{
    private const string SCHEME_NAME = 'matrix';
    private const string SCHEME_NAME_CUSTOM = 'smatrix';

    public function __construct(
        #[SensitiveParameter] private readonly ?string $pickleKey,
        private readonly ?string $deviceId,
        #[SensitiveParameter] private readonly ?string $accessToken,
        #[SensitiveParameter] private readonly ?string $recoveryKey,
        private readonly ?string $defaultRecipient,
        private readonly string $databasePath,
        private readonly GolangLibBridge $bridge,
        ?EventDispatcherInterface $dispatcher = null,
        ?HttpClientInterface $client = null,
    ) {
        parent::__construct($dispatcher, $client);
    }

    protected function getSupportedSchemes(): array
    {
        $result = [self::SCHEME_NAME_CUSTOM];
        if (!class_exists(SymfonyMatrixTransport::class)) {
            $result[] = self::SCHEME_NAME;
        }

        return $result;
    }

    public function create(Dsn $dsn): MatrixTransport
    {
        if (!$this->pickleKey) {
            throw new LogicException('The pickle key is not initialized, please configure it. You can run the rikudou:notifier:matrix:initialize-keys to help you generate it.');
        }
        if (!$this->deviceId) {
            throw new LogicException('The device ID is not initialized, please configure it. You can run the rikudou:notifier:matrix:initialize-keys to help you generate it.');
        }
        if (!$this->recoveryKey) {
            throw new LogicException('The recovery key is not initialized, please configure it.');
        }

        if (!in_array($dsn->getScheme(), $this->getSupportedSchemes(), true)) {
            throw new UnsupportedSchemeException($dsn, self::SCHEME_NAME, $this->getSupportedSchemes());
        }

        $token = $dsn->getOption('accessToken', $this->accessToken);
        $homeserver = $dsn->getHost();
        $port = $dsn->getPort();

        if (!$token) {
            throw new MatrixException("The access token must be provided either as part of DSN or as a configuration parameter.");
        }

        if (!is_dir(dirname($this->databasePath))) {
            mkdir(dirname($this->databasePath), 0777, true);
        }

        return new MatrixTransport(
            accessToken: $token,
            recoveryKey: $this->recoveryKey,
            pickleKey: $this->pickleKey,
            deviceId: $this->deviceId,
            dbPath: $this->databasePath,
            bridge: $this->bridge,
            defaultRecipient: $this->defaultRecipient,
            client: $this->client,
            dispatcher: $this->dispatcher,
        )->setHost($homeserver)->setPort($port);
    }
}
