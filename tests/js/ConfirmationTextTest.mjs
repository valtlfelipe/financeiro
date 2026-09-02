import assert from 'node:assert/strict';
import test from 'node:test';
import { confirmationText } from '../../resources/js/lib/confirmation.ts';

test('confirmation separates the resource name without changing the description', () => {
    const description =
        '“Minha conta” será arquivada. Seu histórico permanece.';
    const parts = confirmationText(description, 'Minha conta');

    assert.deepEqual(parts, {
        before: '“',
        name: 'Minha conta',
        after: '” será arquivada. Seu histórico permanece.',
    });
    assert.equal(parts.before + parts.name + parts.after, description);
});

test('confirmation preserves missing names and treats resource names as literal text', () => {
    assert.deepEqual(confirmationText('Confirme a ação.'), {
        before: 'Confirme a ação.',
        name: '',
        after: '',
    });
    assert.equal(confirmationText('Confirme a ação.', 'Outra conta').name, '');
    assert.equal(confirmationText('Confirme a ação.', '').name, '');
    const name = '<script>alert("Olá")</script> [.*]';
    const description = `“${name}” será excluído.`;
    assert.deepEqual(confirmationText(description, name), {
        before: '“',
        name,
        after: '” será excluído.',
    });
});
