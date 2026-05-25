type PokerStateLike = Record<string, any> | null | undefined;
type PokerPlayerLike = Record<string, any> | null | undefined;

export function playerInitials(name: unknown) {
    return String(name ?? 'Jogador')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'P';
}

export function stackPressureLabel(player: PokerPlayerLike, state: PokerStateLike) {
    const stack = Number(player?.stack ?? 0);
    const bigBlind = Number(state?.bigBlind ?? state?.multiSeat?.blinds?.bigBlind ?? 0);

    if (stack <= 0) {
        return 'Sem fichas';
    }

    if (bigBlind > 0 && stack <= bigBlind * 3) {
        return 'Short stack';
    }

    if (bigBlind > 0 && stack <= bigBlind * 8) {
        return 'Pressão';
    }

    return 'Stack saudável';
}

export function stackPressureClasses(player: PokerPlayerLike, state: PokerStateLike) {
    const label = stackPressureLabel(player, state);

    if (label === 'Sem fichas') {
        return 'border-slate-400/25 bg-slate-950/70 text-slate-200';
    }

    if (label === 'Short stack') {
        return 'border-rose-200/45 bg-rose-400/20 text-rose-100';
    }

    if (label === 'Pressão') {
        return 'border-amber-200/45 bg-amber-300/20 text-amber-100';
    }

    return 'border-emerald-200/35 bg-emerald-300/15 text-emerald-100';
}
