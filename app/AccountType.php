<?php

namespace App;

enum AccountType: string
{
    case Checking = 'checking';
    case Savings = 'savings';
    case Cash = 'cash';
    case Other = 'other';
}
