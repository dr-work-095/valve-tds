<?php

namespace App\Enums;

enum DatasheetAccessory: string
{
    case SmartPositioner = 'Smart Positioner (4-20 mA HART)';
    case LimitSwitches = 'Limit Switches';
    case SolenoidValve = 'Solenoid Valve (SOV)';
    case AirFilterRegulator = 'Air Filter Regulator';
    case ManualOverride = 'Manual Override';
}
