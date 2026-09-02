export function formatDayMonth(date: string): string {
    const [, month, day] = date.split('-');

    return `${day}/${month}`;
}
