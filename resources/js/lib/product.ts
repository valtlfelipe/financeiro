export const PRODUCT_NAME = 'Financeiro';

export function formatPageTitle(title: string): string {
    return title ? `${title} - ${PRODUCT_NAME}` : PRODUCT_NAME;
}
