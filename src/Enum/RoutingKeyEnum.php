<?php

declare(strict_types=1);

namespace App\Enum;

enum RoutingKeyEnum: string
{
    /** Добавление заданий на парсинг RSS */
    case RssParse = 'rss_parse';
}
