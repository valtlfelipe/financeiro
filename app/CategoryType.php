<?php

namespace App;

enum CategoryType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Both = 'both';
}
