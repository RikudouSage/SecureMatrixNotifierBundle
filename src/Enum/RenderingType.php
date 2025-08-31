<?php

namespace Rikudou\MatrixNotifier\Enum;

enum RenderingType: string
{
    case PlainText = 'text';
    case Html = 'html';
    case Markdown = 'markdown';
}
