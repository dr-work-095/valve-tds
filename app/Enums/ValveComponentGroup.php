<?php

namespace App\Enums;

enum ValveComponentGroup: string
{
    case BodyBonnet = 'Body / Bonnet';
    case StemShaft = 'Stem / Shaft';
    case Trim = 'Trim (API Trim No.)';
    case DiscBallPlugWedge = 'Disc / Ball / Plug / Wedge';
    case SeatSeatRing = 'Seat / Seat Ring';
    case SealsGaskets = 'Seals / Gaskets';
    case Bolting = 'Bolting (Studs & Nuts)';
    case Other = 'Other';
}
