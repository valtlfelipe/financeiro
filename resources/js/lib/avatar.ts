import { Avatar, Style } from '@dicebear/core';
import botttsNeutral from '@dicebear/styles/bottts-neutral.json' with { type: 'json' };

const avatarStyle = new Style(botttsNeutral);

export function userAvatar(userId: number): string {
    return new Avatar(avatarStyle, {
        seed: `financeiro:user:${userId}`,
        size: 96,
        textureProbability: 0,
        backgroundColor: [
            '#148a62',
            '#3f67c7',
            '#a66c2b',
            '#7b61a8',
            '#287b87',
            '#af5866',
        ],
        eyesVariant: [
            'eva',
            'frame1',
            'frame2',
            'happy',
            'round',
            'roundFrame01',
            'roundFrame02',
        ],
        mouthVariant: ['smile01', 'smile02', 'diagram', 'square01'],
    }).toDataUri();
}
