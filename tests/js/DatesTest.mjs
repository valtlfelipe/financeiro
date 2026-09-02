import assert from 'node:assert/strict';
import test from 'node:test';
import { formatDayMonth } from '../../resources/js/lib/dates.ts';

test('upcoming transactions show zero-padded day/month without timezone conversion', () => {
    assert.equal(formatDayMonth('2026-09-10'), '10/09');
    assert.equal(formatDayMonth('2026-01-01'), '01/01');
    assert.equal(formatDayMonth('2028-02-29'), '29/02');
    assert.equal(formatDayMonth('2026-12-31'), '31/12');
});
