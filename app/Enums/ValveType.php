<?php

namespace App\Enums;

enum ValveType: string
{
    case Ball = 'Ball';
    case Globe = 'Globe';
    case Gate = 'Gate';
    case Butterfly = 'Butterfly';
    case Plug = 'Plug';
    case Needle = 'Needle';
    case Diaphragm = 'Diaphragm';
    case Check = 'Check';
}
