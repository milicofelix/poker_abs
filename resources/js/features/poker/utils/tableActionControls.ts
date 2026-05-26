import { formatChipAmount } from './tableState';
import type { PokerStateLike } from './tableActionTypes';

export function premiumActionControlItems(state: PokerStateLike) {
    const canAct = Boolean(state?.canAct) || Boolean(state?.currentTurn?.canAct);
    const callAmount = Number(state?.callAmount ?? state?.toCall ?? state?.currentBetToCall ?? 0);
    const minimumRaise = Number(state?.minimumRaiseTo ?? state?.minimumRaise ?? state?.minRaise ?? 0);
    const maximumRaise = Number(state?.maximumRaiseTo ?? state?.maximumRaise ?? state?.maxRaise ?? state?.playerStack ?? 0);
    const canCheck = Boolean(state?.canCheck) || callAmount <= 0;
    const canCall = Boolean(state?.canCall) || callAmount > 0;
    const canRaise = Boolean(state?.canRaise) || maximumRaise > minimumRaise;

    return [
        {
            key: 'fold',
            label: 'Fold',
            helper: 'Sair da mão',
            enabled: canAct,
            className: 'border-rose-200/35 bg-rose-400/10 text-rose-100',
        },
        {
            key: canCheck ? 'check' : 'call',
            label: canCheck ? 'Check' : 'Call',
            helper: canCheck ? 'Passar' : formatChipAmount(callAmount),
            enabled: canAct && (canCheck || canCall),
            className: 'border-emerald-200/35 bg-emerald-300/12 text-emerald-100',
        },
        {
            key: 'raise',
            label: 'Raise',
            helper: minimumRaise > 0 ? `mín. ${formatChipAmount(minimumRaise)}` : 'Aumentar',
            enabled: canAct && canRaise,
            className: 'border-amber-200/40 bg-amber-300/15 text-amber-100',
        },
        {
            key: 'all-in',
            label: 'All-in',
            helper: maximumRaise > 0 ? formatChipAmount(maximumRaise) : 'Tudo',
            enabled: canAct && maximumRaise > 0,
            className: 'border-fuchsia-200/35 bg-fuchsia-400/12 text-fuchsia-100',
        },
    ];
}
