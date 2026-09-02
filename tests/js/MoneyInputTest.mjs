import assert from 'node:assert/strict';
import test from 'node:test';
import {
    formatMinorForInput,
    formatMoneyInput,
    formatMoneyInputOnBlur,
    formatMoneyInputWithCaret,
    parseMoneyInputToMinor,
} from '../../resources/js/lib/money-input.ts';

function typeDigits(digits, locale = 'pt-BR') {
    let value = '0,00';

    for (const digit of digits) {
        value = formatMoneyInput(`${value}${digit}`, locale);
    }

    return value;
}

function backspace(value, locale = 'pt-BR') {
    return formatMoneyInput(value.slice(0, -1), locale);
}

test('shifts typed digits in from the cents like a finance amount field', () => {
    const cases = [
        ['', '0,00'],
        ['1', '0,01'],
        ['12', '0,12'],
        ['123', '1,23'],
        ['1234', '12,34'],
        ['12345', '123,45'],
        ['123450', '1.234,50'],
        ['1234500', '12.345,00'],
        ['18600', '186,00'],
        ['1860000', '18.600,00'],
        ['0,01', '0,01'],
        ['12.345,00', '12.345,00'],
        ['R$ 1.234,56', '1.234,56'],
        ['1234.56', '1.234,56'],
    ];

    for (const [input, expected] of cases) {
        assert.equal(formatMoneyInput(input, 'pt-BR'), expected);
    }
});

test('keeps two decimal places after each keystroke and on blur', () => {
    assert.equal(typeDigits('1'), '0,01');
    assert.equal(typeDigits('18'), '0,18');
    assert.equal(typeDigits('186'), '1,86');
    assert.equal(typeDigits('1860'), '18,60');
    assert.equal(typeDigits('18600'), '186,00');
    assert.equal(typeDigits('1234500'), '12.345,00');
    assert.equal(backspace('12.345,00'), '1.234,50');
    assert.equal(backspace('0,01'), '0,00');
    assert.equal(formatMoneyInputOnBlur('18.600,5', 'pt-BR'), '1.860,05');
    assert.equal(formatMoneyInputOnBlur('', 'pt-BR'), '0,00');
    assert.equal(formatMinorForInput(1860000, 'pt-BR'), '18.600,00');
    assert.equal(formatMinorForInput(5, 'pt-BR'), '0,05');
});

test('treats every digit as integer cents', () => {
    assert.equal(parseMoneyInputToMinor('0,01', 'pt-BR'), 1);
    assert.equal(parseMoneyInputToMinor('186,00', 'pt-BR'), 18600);
    assert.equal(parseMoneyInputToMinor('18.600,00', 'pt-BR'), 1860000);
    assert.equal(parseMoneyInputToMinor('1.234,56', 'pt-BR'), 123456);
    assert.equal(parseMoneyInputToMinor('', 'pt-BR'), 0);
});

test('keeps the caret at the end so the next digit becomes the new cents', () => {
    assert.deepEqual(formatMoneyInputWithCaret('1', 1, 'pt-BR'), {
        caret: 4,
        value: '0,01',
    });
    assert.deepEqual(formatMoneyInputWithCaret('1234500', 7, 'pt-BR'), {
        caret: 9,
        value: '12.345,00',
    });
    assert.deepEqual(formatMoneyInputWithCaret('12.345,000', 10, 'pt-BR'), {
        caret: 10,
        value: '123.450,00',
    });
});
