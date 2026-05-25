type PokerTableStateLike = Record<string, any> | null | undefined;
type PokerPlayerLike = Record<string, any> | null | undefined;
type PokerCardLike = Record<string, any> | null | undefined;

export function visibleCommunityCards(state: PokerTableStateLike) {
    const amountByStreet: Record<string, number> = {
        pre_flop: 0,
        flop: 3,
        turn: 4,
        river: 5,
        showdown: 5,
    };

    const players = Array.isArray(state?.multiSeat?.players) ? state.multiSeat.players : [];
    const hasAllInPlayer = players.some((player: PokerPlayerLike) => Boolean(player?.isAllIn) || Number(player?.stack ?? 0) <= 0);
    const shouldRevealFinishedBoard = Boolean(state?.isFinished)
        && (state?.street === 'showdown' || Boolean(state?.multiSeat?.allInRunoutCompleted) || hasAllInPlayer);

    const amount = shouldRevealFinishedBoard ? 5 : (amountByStreet[state?.street] ?? 0);

    return {
        visible: (state?.communityCards ?? []).slice(0, amount),
        hiddenCount: Math.max(0, 5 - amount),
    };
}

export function opponentCardsTitle(state: PokerTableStateLike) {
    const opponent = state?.playersContext?.opponents?.[0];

    if (opponent?.nickname) {
        return `Cartas de ${opponent.nickname}`;
    }

    return 'Cartas do adversário';
}

export function currentPlayerTitle(state: PokerTableStateLike) {
    const currentName = state?.playersContext?.current?.nickname;

    return currentName ? `Suas cartas (${currentName})` : 'Suas cartas';
}

export function cardSignature(cards: PokerCardLike[] = []) {
    return cards
        .map((card) => `${card?.rank ?? card?.label ?? ''}-${card?.suit ?? ''}`)
        .join('|');
}

export function hiddenPlayerHandTitle(isRevealed: boolean) {
    return isRevealed ? 'Ocultar suas cartas' : 'Revelar suas cartas';
}

export function hiddenPlayerHandDescription(isRevealed: boolean, isFinished: boolean) {
    if (isFinished) {
        return 'Mão encerrada — cartas liberadas para conferência.';
    }

    return isRevealed
        ? 'Clique para esconder sua mão novamente.'
        : 'Clique para espiar sua mão quando quiser.';
}

export function currentTurnLabel(state: PokerTableStateLike) {
    return state?.currentTurn?.actorLabel ?? 'Jogador';
}

export function chipAmountParts(value: unknown) {
    const amount = Number(value ?? 0);

    if (amount <= 0) {
        return [1, 1, 1];
    }

    return [
        Math.max(1, Math.ceil(amount / 120)),
        Math.max(1, Math.ceil(amount / 220)),
        Math.max(1, Math.ceil(amount / 360)),
    ].slice(0, 3);
}

export function dealerAnimationLabel(state: PokerTableStateLike) {
    if (state?.isFinished) {
        return 'Showdown finalizado';
    }

    if ((state?.actionHistory ?? []).length > 0) {
        return 'Cartas na mesa';
    }

    return 'Dealer distribuindo';
}

export function currentTurnMessage(state: PokerTableStateLike) {
    if (state?.isFinished) {
        return 'Mão encerrada';
    }

    return state?.currentTurn?.message ?? 'Aguardando ação da mesa.';
}

export function winnerPlayer(state: PokerTableStateLike) {
    return state?.conclusion?.winner?.player ?? null;
}

export function isWinnerSeat(state: PokerTableStateLike, seat: string) {
    const winner = winnerPlayer(state);

    return state?.isFinished && (winner === seat || winner === 'tie');
}

export function isLosingSeat(state: PokerTableStateLike, seat: string) {
    const winner = winnerPlayer(state);

    return state?.isFinished && winner && winner !== 'tie' && winner !== seat;
}

export function winnerBadgeLabel(state: PokerTableStateLike, seat: string) {
    const winner = winnerPlayer(state);

    if (!state?.isFinished || !winner) {
        return null;
    }

    if (winner === 'tie') {
        return 'Empate';
    }

    return winner === seat ? 'Vencedor' : 'Derrotado';
}

export function seatFrameClasses(state: PokerTableStateLike, seat: string) {
    if (isWinnerSeat(state, seat)) {
        return 'poker-winner-seat border-amber-200/70 bg-amber-300/15 shadow-[0_0_42px_rgba(251,191,36,0.28)]';
    }

    if (isLosingSeat(state, seat)) {
        return 'border-slate-500/20 bg-black/25 opacity-70 grayscale-[0.25]';
    }

    return 'border-white/10 bg-black/20 shadow-inner shadow-black/40';
}

export function seatBadgeClasses(state: PokerTableStateLike, seat: string) {
    if (isWinnerSeat(state, seat)) {
        return 'border-amber-100/60 bg-amber-300 text-amber-950 shadow-lg shadow-amber-950/25';
    }

    return 'border-slate-400/30 bg-slate-950/80 text-slate-200';
}

export function isMultiSeatLayout(state: PokerTableStateLike) {
    if (!Boolean(state?.multiSeat?.enabled) || !Array.isArray(state?.multiSeat?.players)) {
        return false;
    }

    return state.multiSeat.players.length >= 2;
}

export function isMultiSeatShowdownResolved(state: PokerTableStateLike) {
    if (!Boolean(state?.isFinished)) {
        return false;
    }

    return state?.street === 'showdown'
        || Boolean(state?.multiSeat?.showdownCardsRevealed)
        || state?.multiSeat?.showdownResolutionPhase === '10.12';
}

export function multiSeatPlayers(state: PokerTableStateLike) {
    return [...(state?.multiSeat?.players ?? [])]
        .filter((player: PokerPlayerLike) => player && Number(player.seatNumber ?? 0) > 0)
        .sort((left: PokerPlayerLike, right: PokerPlayerLike) => Number(left?.seatNumber ?? 0) - Number(right?.seatNumber ?? 0));
}

export function currentUserSeatNumber(state: PokerTableStateLike) {
    return Number(state?.multiplayerPerspective?.seatNumber ?? state?.playersContext?.current?.seatNumber ?? 0);
}

export function multiSeatCurrentTurnSeat(state: PokerTableStateLike) {
    const seat = state?.currentTurn?.seatNumber ?? state?.multiSeat?.currentSeat;

    return seat === null || seat === undefined ? null : Number(seat);
}

export function multiSeatSeatPositionNumbers(state: PokerTableStateLike) {
    return {
        dealerSeat: Number(state?.multiSeat?.dealerSeat ?? state?.multiSeat?.blinds?.dealerSeat ?? 0),
        smallBlindSeat: Number(state?.multiSeat?.smallBlindSeat ?? state?.multiSeat?.blinds?.smallBlindSeat ?? 0),
        bigBlindSeat: Number(state?.multiSeat?.bigBlindSeat ?? state?.multiSeat?.blinds?.bigBlindSeat ?? 0),
    };
}

export function multiSeatPositionBadges(player: PokerPlayerLike, state: PokerTableStateLike) {
    const seatNumber = Number(player?.seatNumber ?? 0);
    const positions = multiSeatSeatPositionNumbers(state);

    return [
        {
            key: 'dealer',
            shortLabel: 'D',
            label: 'Dealer',
            active: Boolean(player?.isDealer) || (positions.dealerSeat > 0 && seatNumber === positions.dealerSeat),
            className: 'border-amber-100/70 bg-amber-300 text-amber-950 shadow-amber-950/20',
        },
        {
            key: 'small-blind',
            shortLabel: 'SB',
            label: 'Small Blind',
            active: Boolean(player?.isSmallBlind) || (positions.smallBlindSeat > 0 && seatNumber === positions.smallBlindSeat),
            className: 'border-sky-100/60 bg-sky-300 text-sky-950 shadow-sky-950/20',
        },
        {
            key: 'big-blind',
            shortLabel: 'BB',
            label: 'Big Blind',
            active: Boolean(player?.isBigBlind) || (positions.bigBlindSeat > 0 && seatNumber === positions.bigBlindSeat),
            className: 'border-fuchsia-100/60 bg-fuchsia-300 text-fuchsia-950 shadow-fuchsia-950/20',
        },
    ].filter((badge) => badge.active);
}

export function multiSeatBlindSummary(state: PokerTableStateLike) {
    const positions = multiSeatSeatPositionNumbers(state);
    const parts = [
        positions.dealerSeat > 0 ? `Dealer: assento ${positions.dealerSeat}` : null,
        positions.smallBlindSeat > 0 ? `SB: assento ${positions.smallBlindSeat}` : null,
        positions.bigBlindSeat > 0 ? `BB: assento ${positions.bigBlindSeat}` : null,
    ].filter(Boolean);

    return parts.length > 0 ? parts.join(' • ') : 'Dealer/SB/BB aguardando nova mão';
}

export function formatChipAmount(value: unknown) {
    return Number(value ?? 0).toLocaleString('pt-BR');
}
