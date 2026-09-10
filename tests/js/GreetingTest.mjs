import assert from 'node:assert/strict';
import test from 'node:test';
import { greetingPeriodForHour } from '../../resources/js/lib/greeting.ts';

test('uses the greeting for the user local time of day', () => {
    assert.equal(greetingPeriodForHour(4), 'evening');
    assert.equal(greetingPeriodForHour(5), 'morning');
    assert.equal(greetingPeriodForHour(11), 'morning');
    assert.equal(greetingPeriodForHour(12), 'afternoon');
    assert.equal(greetingPeriodForHour(17), 'afternoon');
    assert.equal(greetingPeriodForHour(18), 'evening');
    assert.equal(greetingPeriodForHour(23), 'evening');
});
