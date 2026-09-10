export type SettlementReloadOptions = {
    only: string[];
};

export function settlementReloadOptions(
    reloadProps?: string[],
): SettlementReloadOptions | undefined {
    if (!reloadProps?.length) return;

    return { only: reloadProps };
}
