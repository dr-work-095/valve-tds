<?php

namespace App\Enums;

enum DatasheetCompliance: string
{
    case NaceMr0175 = 'NACE MR0175 (Sour Service)';
    case Api607 = 'API 607 (Fire Safe)';
    case Iso15848 = 'ISO 15848-1 (Fugitive Emissions)';
    case SilCertified = 'SIL Certified';
}
