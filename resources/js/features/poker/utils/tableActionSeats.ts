import {
    isAllInActionLabel,
    normalizeActionLabel,
} from './tableActionLabels';
import type { PokerPlayerLike, PokerStateLike } from './tableActionTypes';

export function latestActionForSeat(state: PokerStateLike, player: PokerPlayerLike) {
    const seatNumber = Number(player?.seatNumber ?? 0);
    const tablePlayerId = Number(player?.tablePlayerId ?? player?.id ?? 0);
    const history = Array.isArray(state?.actionHistory) ? state.actionHistory : [];

    const action = [...history].reverse().find((item) => {
        const itemSeat = Number(item?.seatNumber ?? 0);
        const itemPlayerId = Number(item?.tablePlayerId ?? item?.table_player_id ?? 0);

        return (seatNumber > 0 && itemSeat === seatNumber)
            || (tablePlayerId > 0 && itemPlayerId === tablePlayerId);
    });

    if (action) {
        return action;
    }

    const lastAction = state?.lastAction;

    if (Number(lastAction?.seatNumber ?? 0) === seatNumber) {
        return lastAction;
    }

    return null;
}

export function multiSeatLastActionLabel(
    state: PokerStateLike,
    player: PokerPlayerLike,
    hasFolded: boolean,
    isCurrentTurn: boolean,
) {
    if (hasFolded) {
        return 'Fold';
    }

    const action = latestActionForSeat(state, player);
    const label = normalizeActionLabel(action);

    if (label) {
        const amount = Number(action?.amount ?? 0);

        return amount > 0 ? `${label} ${amount}` : label;
    }

    if (isCurrentTurn) {
        return 'Pensando';
    }

    return 'Aguardando';
}

export function multiSeatActionPillClasses(actionLabel: unknown, isCurrentTurn: boolean, hasFolded: boolean) {
    const normalized = String(actionLabel ?? '').toLowerCase();

    if (hasFolded || normalized.includes('fold')) {
        return 'border-slate-400/30 bg-slate-950/80 text-slate-200';
    }

    if (isAllInActionLabel(actionLabel)) {
        return 'border-rose-100/60 bg-rose-400 text-rose-950 shadow-rose-950/20';
    }

    if (normalized.includes('raise') || normalized.includes('bet')) {
        return 'border-amber-100/60 bg-amber-300 text-amber-950 shadow-amber-950/20';
    }

    if (normalized.includes('call')) {
        return 'border-emerald-100/60 bg-emerald-300 text-emerald-950 shadow-emerald-950/20';
    }

    if (normalized.includes('check')) {
        return 'border-sky-100/60 bg-sky-300 text-sky-950 shadow-sky-950/20';
    }

    if (isCurrentTurn || normalized.includes('pensando')) {
        return 'border-emerald-100/45 bg-emerald-300 text-emerald-950 shadow-emerald-950/20';
    }

    return 'border-violet-100/40 bg-violet-300 text-violet-950 shadow-violet-950/20';
}

export function shouldShowMultiSeatActionPill(actionLabel: unknown) {
    return Boolean(actionLabel) && String(actionLabel).toLowerCase() !== 'aguardando';
}
