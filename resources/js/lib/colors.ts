const colors = [
    '#147D59',
    '#3563B8',
    '#AA4857',
    '#7955A2',
    '#A36219',
    '#167580',
    '#486D38',
    '#AB4530',
    '#9A3975',
    '#526176',
] as const;

export function randomColor(previous?: string): string {
    const options = colors.filter((color) => color !== previous?.toUpperCase());

    return options[Math.floor(Math.random() * options.length)];
}
