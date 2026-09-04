import { usePage } from '@inertiajs/vue3';
import { formatCurrencyMinor } from '@/lib/minor-amount';
import type { MinorAmount } from '@/lib/minor-amount';

export function useFinanceFormat() {
    const page = usePage();

    const formatMoney = (minor: MinorAmount): string =>
        formatCurrencyMinor(
            minor,
            page.props.locale,
            page.props.workspace?.currency ?? 'BRL',
        );

    const formatDate = (
        date: string,
        options: Intl.DateTimeFormatOptions = {},
    ): string => {
        const dateOptions: Intl.DateTimeFormatOptions =
            options.dateStyle || options.timeStyle
                ? options
                : { day: '2-digit', month: 'short', ...options };

        return new Intl.DateTimeFormat(page.props.locale, {
            timeZone: page.props.workspace?.timezone ?? 'America/Sao_Paulo',
            ...dateOptions,
        }).format(new Date(`${date}T12:00:00`));
    };

    const formatMonth = (month: string): string =>
        new Intl.DateTimeFormat(page.props.locale, {
            timeZone: page.props.workspace?.timezone ?? 'America/Sao_Paulo',
            month: 'long',
            year: 'numeric',
        }).format(new Date(`${month}-01T12:00:00`));

    return { formatMoney, formatDate, formatMonth };
}
