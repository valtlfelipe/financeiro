<?php

use App\Support\MinorAmount;

test('minor amounts add and subtract beyond javascript and php integer precision boundaries', function () {
    expect(MinorAmount::add('9007199254740991', '9007199254740991'))->toBe('18014398509481982')
        ->and(MinorAmount::add('-9007199254740991', '1'))->toBe('-9007199254740990')
        ->and(MinorAmount::subtract('10000000000000000000', '9999999999999999999'))->toBe('1')
        ->and(MinorAmount::compare('-2', '-1'))->toBe(-1)
        ->and(MinorAmount::normalize('-000'))->toBe('0');
});
