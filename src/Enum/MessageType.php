<?php

namespace Rikudou\MatrixNotifier\Enum;

enum MessageType: string
{
    case TextMessage = 'm.text';
    case Notice = 'm.notice';
}
