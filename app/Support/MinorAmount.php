<?php

namespace App\Support;

use InvalidArgumentException;

final class MinorAmount
{
    public static function normalize(int|string $value): string
    {
        $value = (string) $value;

        if (preg_match('/^-?\d+$/', $value) !== 1) {
            throw new InvalidArgumentException('A minor amount must be an integer.');
        }

        $negative = str_starts_with($value, '-');
        $magnitude = ltrim($negative ? substr($value, 1) : $value, '0');

        if ($magnitude === '') {
            return '0';
        }

        return $negative ? '-'.$magnitude : $magnitude;
    }

    public static function add(int|string ...$values): string
    {
        $result = '0';

        foreach ($values as $value) {
            $result = self::addPair($result, self::normalize($value));
        }

        return $result;
    }

    public static function subtract(int|string $left, int|string $right): string
    {
        return self::add($left, self::negate($right));
    }

    public static function compare(int|string $left, int|string $right): int
    {
        $difference = self::subtract($left, $right);

        return $difference === '0' ? 0 : (str_starts_with($difference, '-') ? -1 : 1);
    }

    private static function negate(int|string $value): string
    {
        $value = self::normalize($value);

        if ($value === '0') {
            return $value;
        }

        return str_starts_with($value, '-') ? substr($value, 1) : '-'.$value;
    }

    private static function addPair(string $left, string $right): string
    {
        $leftNegative = str_starts_with($left, '-');
        $rightNegative = str_starts_with($right, '-');
        $leftMagnitude = $leftNegative ? substr($left, 1) : $left;
        $rightMagnitude = $rightNegative ? substr($right, 1) : $right;

        if ($leftNegative === $rightNegative) {
            $sum = self::addMagnitudes($leftMagnitude, $rightMagnitude);

            return $leftNegative ? '-'.$sum : $sum;
        }

        $comparison = self::compareMagnitudes($leftMagnitude, $rightMagnitude);

        if ($comparison === 0) {
            return '0';
        }

        if ($comparison > 0) {
            $difference = self::subtractMagnitudes($leftMagnitude, $rightMagnitude);

            return $leftNegative ? '-'.$difference : $difference;
        }

        $difference = self::subtractMagnitudes($rightMagnitude, $leftMagnitude);

        return $rightNegative ? '-'.$difference : $difference;
    }

    private static function addMagnitudes(string $left, string $right): string
    {
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;
        $carry = 0;
        $result = '';

        while ($leftIndex >= 0 || $rightIndex >= 0 || $carry > 0) {
            $sum = ($leftIndex >= 0 ? (int) $left[$leftIndex--] : 0)
                + ($rightIndex >= 0 ? (int) $right[$rightIndex--] : 0)
                + $carry;
            $result = (string) ($sum % 10).$result;
            $carry = intdiv($sum, 10);
        }

        return $result;
    }

    private static function subtractMagnitudes(string $larger, string $smaller): string
    {
        $largerIndex = strlen($larger) - 1;
        $smallerIndex = strlen($smaller) - 1;
        $borrow = 0;
        $result = '';

        while ($largerIndex >= 0) {
            $difference = (int) $larger[$largerIndex--]
                - ($smallerIndex >= 0 ? (int) $smaller[$smallerIndex--] : 0)
                - $borrow;

            if ($difference < 0) {
                $difference += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }

            $result = (string) $difference.$result;
        }

        return ltrim($result, '0') ?: '0';
    }

    private static function compareMagnitudes(string $left, string $right): int
    {
        return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
    }
}
