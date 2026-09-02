import { botttsNeutral } from '@dicebear/collection';
import { createAvatar } from '@dicebear/core';

export function userAvatar(userId: number): string {
    return createAvatar(botttsNeutral, {
        seed: `financeiro:user:${userId}`,
        size: 96,
        backgroundColor: [
            '148a62',
            '3f67c7',
            'a66c2b',
            '7b61a8',
            '287b87',
            'af5866',
        ],
        eyes: [
            'eva',
            'frame1',
            'frame2',
            'happy',
            'round',
            'roundFrame01',
            'roundFrame02',
        ],
        mouth: ['smile01', 'smile02', 'diagram', 'square01'],
    }).toDataUri();
}
