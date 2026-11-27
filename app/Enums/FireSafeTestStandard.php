<?php

namespace App\Enums;

enum FireSafeTestStandard: string
{
    case Api607 = 'API 607';
    case Api6fa = 'API 6FA';
    case Iso10497 = 'ISO 10497';
    case Other = 'Other';

    public const LEGACY_SPECIAL_COMPLIANCE_VALUE = 'API 607 (Fire Safe)';
}
