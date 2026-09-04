export type MinorAmount = string;

export function minorIsNegative(value: MinorAmount): boolean {
    return BigInt(value) < 0n;
}

export function formatCurrencyMinor(
    value: MinorAmount,
    locale: string,
    currency: string,
): string {
    const minor = BigInt(value);
    const negative = minor < 0n;
    const magnitude = negative ? -minor : minor;
    const units = magnitude / 100n;
    const cents = (magnitude % 100n).toString().padStart(2, '0');
    const currencyFormatter = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    const parts = currencyFormatter.formatToParts(0);
    const numericTypes = new Set(['integer', 'group', 'decimal', 'fraction']);
    const firstNumericIndex = parts.findIndex((part) =>
        numericTypes.has(part.type),
    );
    const lastNumericIndex = parts.findLastIndex((part) =>
        numericTypes.has(part.type),
    );
    const prefix = parts
        .slice(0, firstNumericIndex)
        .map((part) => part.value)
        .join('');
    const suffix = parts
        .slice(lastNumericIndex + 1)
        .map((part) => part.value)
        .join('');
    const decimal = parts.find((part) => part.type === 'decimal')?.value ?? ',';
    const groupedUnits = new Intl.NumberFormat(locale, {
        maximumFractionDigits: 0,
    }).format(units);
    const minus = negative
        ? (new Intl.NumberFormat(locale)
              .formatToParts(-1)
              .find((part) => part.type === 'minusSign')?.value ?? '-')
        : '';

    return `${minus}${prefix}${groupedUnits}${decimal}${cents}${suffix}`;
}
