<?php

namespace App\Enums;

enum RouterConnectionStatus: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case UNKNOWN = 'unknown';
}
