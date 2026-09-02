type MoneySeparators = {
    decimal: string;
    group: string;
};

type ParsedMoneyInput = {
    decimalDigits: string;
    decimalIndex: number;
    integerDigits: string;
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

function countDigits(value: string): number {
    return value.replace(/\D/g, '').length;
}

function findDecimalIndex(value: string, separators: MoneySeparators): number {
    const dotIndex = value.lastIndexOf('.');
    const commaIndex = value.lastIndexOf(',');

    if (dotIndex >= 0 && commaIndex >= 0) {
        return Math.max(dotIndex, commaIndex);
    }

    const localeDecimalIndex = value.indexOf(separators.decimal);
    if (localeDecimalIndex >= 0) {
        return localeDecimalIndex;
    }

    const alternativeIndex = value.lastIndexOf(separators.group);
    if (alternativeIndex < 0) {
        return -1;
    }

    const separatorCount = value.split(separators.group).length - 1;
    const digitsAfterSeparator = countDigits(
        value.slice(alternativeIndex + separators.group.length),
    );

    return separatorCount === 1 && digitsAfterSeparator <= 2
        ? alternativeIndex
        : -1;
}

function parseMoneyInput(value: string, locale: string): ParsedMoneyInput {
    const separators = getMoneySeparators(locale);
    const decimalIndex = findDecimalIndex(value, separators);
    const integerSource =
        decimalIndex >= 0 ? value.slice(0, decimalIndex) : value;
    const decimalSource =
        decimalIndex >= 0
            ? value.slice(decimalIndex + separators.decimal.length)
            : '';
    const integerDigits = integerSource.replace(/\D/g, '');

    return {
        decimalDigits: decimalSource.replace(/\D/g, '').slice(0, 2),
        decimalIndex,
        integerDigits: integerDigits.replace(/^0+(?=\d)/, '') || '0',
    };
}

function groupInteger(value: string, separator: string): string {
    return value.replace(/\B(?=(\d{3})+(?!\d))/g, separator);
}

export function formatMoneyInput(value: string, locale: string): string {
    const separators = getMoneySeparators(locale);
    const parsed = parseMoneyInput(value, locale);

    if (countDigits(value) === 0 && parsed.decimalIndex < 0) {
        return '';
    }

    const integer = groupInteger(parsed.integerDigits, separators.group);

    return parsed.decimalIndex >= 0
        ? `${integer}${separators.decimal}${parsed.decimalDigits}`
        : integer;
}

export function formatMoneyInputOnBlur(value: string, locale: string): string {
    if (countDigits(value) === 0) {
        return '';
    }

    return formatMinorForInput(parseMoneyInputToMinor(value, locale), locale);
}

export function formatMinorForInput(minor: number, locale: string): string {
    const normalizedMinor = Math.max(0, Math.round(minor));
    const paddedMinor = normalizedMinor.toString().padStart(3, '0');
    const integerDigits = paddedMinor.slice(0, -2);
    const decimalDigits = paddedMinor.slice(-2);
    const separators = getMoneySeparators(locale);

    return `${groupInteger(integerDigits, separators.group)}${separators.decimal}${decimalDigits}`;
}

export function parseMoneyInputToMinor(value: string, locale: string): number {
    if (countDigits(value) === 0) {
        return 0;
    }

    const parsed = parseMoneyInput(value, locale);
    const decimalDigits = parsed.decimalDigits.padEnd(2, '0');

    return Number(parsed.integerDigits) * 100 + Number(decimalDigits);
}

export function formatMoneyInputWithCaret(
    value: string,
    caret: number,
    locale: string,
): FormattedMoneyInput {
    const formattedValue = formatMoneyInput(value, locale);
    const parsed = parseMoneyInput(value, locale);
    const separators = getMoneySeparators(locale);
    const safeCaret = Math.max(0, Math.min(caret, value.length));

    if (parsed.decimalIndex >= 0 && safeCaret > parsed.decimalIndex) {
        const decimalDigitsBeforeCaret = countDigits(
            value.slice(parsed.decimalIndex + 1, safeCaret),
        );
        const formattedDecimalIndex = formattedValue.indexOf(
            separators.decimal,
        );

        return {
            caret:
                formattedDecimalIndex +
                separators.decimal.length +
                Math.min(decimalDigitsBeforeCaret, 2),
            value: formattedValue,
        };
    }

    const digitsBeforeCaret = countDigits(value.slice(0, safeCaret));
    if (digitsBeforeCaret === 0) {
        return { caret: 0, value: formattedValue };
    }

    let seenDigits = 0;
    let formattedCaret = formattedValue.length;

    for (let index = 0; index < formattedValue.length; index += 1) {
        const character = formattedValue[index] ?? '';

        if (/\d/.test(character)) {
            seenDigits += 1;
        }

        if (seenDigits === digitsBeforeCaret) {
            formattedCaret = index + 1;
            break;
        }
    }

    if (
        value.slice(safeCaret - separators.group.length, safeCaret) ===
            separators.group &&
        formattedValue.slice(
            formattedCaret,
            formattedCaret + separators.group.length,
        ) === separators.group
    ) {
        formattedCaret += separators.group.length;
    }

    return { caret: formattedCaret, value: formattedValue };
}
