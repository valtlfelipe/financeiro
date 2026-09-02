<?php

namespace App;

enum RecurrenceFrequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
