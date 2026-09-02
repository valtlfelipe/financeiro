const MAX_MONEY_DIGITS = 15;

type MoneySeparators = {
    decimal: string;
    group: string;
};

export type FormattedMoneyInput = {
    caret: number;
    value: string;
};

function getMoneySeparators(locale: string): MoneySeparators {
    const parts = new Intl.NumberFormat(locale).formatToParts(12345.6);

    return {
        decimal: parts.find((part) => part.type === 'decimal')?.value ?? ',',
        group: parts.find((part) => part.type === 'group')?.value ?? '.',
    };
}

function groupInteger(value: string, separator: string): string {
    return value.replace(/\B(?=(\d{3})+(?!\d))/g, separator);
}

function digitsAsMinor(value: string): number {
    const digits = value.replace(/\D/g, '').replace(/^0+/, '') || '0';

    return Number(digits.slice(0, MAX_MONEY_DIGITS));
}

export function formatMinorForInput(minor: number, locale: string): string {
    const normalizedMinor = Math.max(0, Math.round(minor));
    const paddedMinor = normalizedMinor.toString().padStart(3, '0');
    const integerDigits = paddedMinor.slice(0, -2);
    const decimalDigits = paddedMinor.slice(-2);
    const separators = getMoneySeparators(locale);

    return `${groupInteger(integerDigits, separators.group)}${separators.decimal}${decimalDigits}`;
}

export function parseMoneyInputToMinor(value: string, _locale: string): number {
    return digitsAsMinor(value);
}

export function formatMoneyInput(value: string, locale: string): string {
    return formatMinorForInput(parseMoneyInputToMinor(value, locale), locale);
}

export function formatMoneyInputOnBlur(value: string, locale: string): string {
    return formatMoneyInput(value, locale);
}

export function formatMoneyInputWithCaret(
    value: string,
    _caret: number,
    locale: string,
): FormattedMoneyInput {
    const formattedValue = formatMoneyInput(value, locale);

    return {
        caret: formattedValue.length,
        value: formattedValue,
    };
}
