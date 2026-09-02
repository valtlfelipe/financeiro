<?php

namespace App;

enum SeriesKind: string
{
    case Recurring = 'recurring';
    case Installment = 'installment';
}
