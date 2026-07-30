<?php

namespace App\Enums;

enum DocumentKind: string
{
    case Photo = 'photo';
    case Receipt = 'receipt';
    case Invoice = 'invoice';
    case Manual = 'manual';
    case Warranty = 'warranty';
    case Other = 'other';
}
