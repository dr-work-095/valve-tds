<?php

namespace App\Enums;

enum ValveCategory: string
{
    case ManualIsolation = 'Manual Isolation';
    case AutomatedOnOff = 'Automated On/Off';
    case ControlModulating = 'Control / Modulating';
    case CheckNonReturn = 'Check / Non-Return';
    case SafetyPressureRelief = 'Safety / Pressure Relief';
}
