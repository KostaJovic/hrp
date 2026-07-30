<?php

namespace App\Enums;

enum LocationKind: string
{
    case Room = 'room';
    case Shelf = 'shelf';
    case Box = 'box';
    case Cabinet = 'cabinet';
    case Garage = 'garage';
    case Vehicle = 'vehicle';
    case Other = 'other';
}
