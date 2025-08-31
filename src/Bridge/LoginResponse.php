<?php

namespace Rikudou\MatrixNotifier\Bridge;

final readonly class LoginResponse
{
    public function __construct(
        public string $accessToken,
        public string $deviceId,
    ) {
    }
}
