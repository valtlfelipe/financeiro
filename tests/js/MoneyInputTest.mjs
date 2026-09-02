import assert from 'node:assert/strict';
import test from 'node:test';
import {
    formatMinorForInput,
    formatMoneyInput,
    formatMoneyInputOnBlur,
    formatMoneyInputWithCaret,
    parseMoneyInputToMinor,
} from '../../resources/js/lib/money-input.ts';

test('formats Brazilian money while the user types', () => {
    const cases = [
        ['', ''],
        ['1', '1'],
        ['1860', '1.860'],
        ['1.8600', '18.600'],
        ['18.600,', '18.600,'],
        ['18.600,5', '18.600,5'],
        ['R$ 1.234,56', '1.234,56'],
        ['1234.56', '1.234,56'],
        ['-00012,345', '12,34'],
    ];

    for (const [input, expected] of cases) {
        assert.equal(formatMoneyInput(input, 'pt-BR'), expected);
    }
});

test('formats complete values on blur and when editing', () => {
    assert.equal(formatMoneyInputOnBlur('18.600,5', 'pt-BR'), '18.600,50');
    assert.equal(formatMoneyInputOnBlur('', 'pt-BR'), '');
    assert.equal(formatMinorForInput(1860000, 'pt-BR'), '18.600,00');
    assert.equal(formatMinorForInput(5, 'pt-BR'), '0,05');
});

test('converts formatted values to integer cents', () => {
    assert.equal(parseMoneyInputToMinor('18.600', 'pt-BR'), 1860000);
    assert.equal(parseMoneyInputToMinor('18.600,5', 'pt-BR'), 1860050);
    assert.equal(parseMoneyInputToMinor('1.234,56', 'pt-BR'), 123456);
    assert.equal(parseMoneyInputToMinor('', 'pt-BR'), 0);
});

test('keeps the caret beside the digits being edited', () => {
    assert.deepEqual(formatMoneyInputWithCaret('1860', 4, 'pt-BR'), {
        caret: 5,
        value: '1.860',
    });
    assert.deepEqual(formatMoneyInputWithCaret('1234.56', 7, 'pt-BR'), {
        caret: 8,
        value: '1.234,56',
    });
    assert.deepEqual(formatMoneyInputWithCaret('1.2860', 3, 'pt-BR'), {
        caret: 2,
        value: '12.860',
    });
});
