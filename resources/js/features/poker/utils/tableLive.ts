import {
    currentUserSeatNumber,
    formatChipAmount,
    isMultiSeatLayout,
    multiSeatCurrentTurnSeat,
    multiSeatPlayers,
} from './tableState';

type PokerStateLike = Record<string, any> | null | undefined;
type PokerPlayerLike = Record<string, any> | null | undefined;
type PokerTimerLike = Record<string, any> | null | undefined;

export function multiSeatAssistedModeLabel(state: PokerStateLike, currentPlayer: PokerPlayerLike) {
    if (!state?.tournamentRuntime) {
        return null;
    }

    if (currentPlayer) {
        return null;
    }

    return 'Modo assistido — você foi eliminado, mas a mesa continua em acompanhamento.';
}

export function tournamentHudMetrics(state: PokerStateLike, currentPlayer: PokerPlayerLike = null) {
    const runtime = state?.tournamentRuntime;

    if (!runtime) {
        return null;
    }

    const players = multiSeatPlayers(state);
    const activePlayers = players.filter((player) => Number(player?.stack ?? 0) > 0 && !Boolean(player?.hasFolded));
    const stackPlayers = players.filter((player) => Number(player?.stack ?? 0) > 0);
    const currentSmallBlind = Number(runtime?.smallBlind ?? state?.smallBlind ?? 0);
    const currentBigBlind = Number(runtime?.bigBlind ?? state?.bigBlind ?? 0);
    const nextSmallBlind = Number(runtime?.nextSmallBlind ?? runtime?.nextLevelSmallBlind ?? state?.nextSmallBlind ?? 0)
        || (currentSmallBlind > 0 ? currentSmallBlind * 2 : 0);
    const nextBigBlind = Number(runtime?.nextBigBlind ?? runtime?.nextLevelBigBlind ?? state?.nextBigBlind ?? 0)
        || (currentBigBlind > 0 ? currentBigBlind * 2 : 0);
    const activeCount = Number(runtime?.activePlayers ?? 0)
        || activePlayers.length
        || stackPlayers.length
        || Number(runtime?.syncedPlayers ?? 0)
        || players.length;
    const totalStack = stackPlayers.reduce((sum, player) => sum + Number(player?.stack ?? 0), 0);
    const averageStack = activeCount > 0 ? Math.round(totalStack / activeCount) : 0;
    const averageStackInBb = currentBigBlind > 0 ? Math.max(1, Math.round(averageStack / currentBigBlind)) : 0;
    const currentSeat = Number(currentPlayer?.seatNumber ?? currentUserSeatNumber(state) ?? 0);
    const rankedSeats = [...stackPlayers]
        .sort((left, right) => Number(right?.stack ?? 0) - Number(left?.stack ?? 0))
        .map((player) => Number(player?.seatNumber ?? 0));
    const positionIndex = currentSeat > 0 ? rankedSeats.indexOf(currentSeat) : -1;
    const positionLabel = positionIndex >= 0 ? `#${positionIndex + 1}` : '—';
    const level = Number(runtime?.blindLevel ?? runtime?.level ?? 1) || 1;

    return {
        name: runtime?.name ?? 'Torneio real',
        status: runtime?.canStartNextHand ? 'próxima mão pronta' : runtime?.isHandFinished ? 'showdown' : 'ao vivo',
        level,
        blinds: `${formatChipAmount(currentSmallBlind)} / ${formatChipAmount(currentBigBlind)}`,
        nextBlinds: `${formatChipAmount(nextSmallBlind)} / ${formatChipAmount(nextBigBlind)}`,
        remaining: activeCount > 0 ? String(activeCount) : '—',
        averageStack: averageStackInBb > 0 ? `${averageStackInBb} BB` : (averageStack > 0 ? formatChipAmount(averageStack) : '—'),
        position: positionLabel,
    };
}

export function liveTurnTimerTone(timer: PokerTimerLike) {
    const percentage = Number(timer?.percentage ?? 0);

    if (timer?.isExpired || percentage < 20) {
        return {
            name: 'danger',
            text: 'text-rose-100',
            subtleText: 'text-rose-100/75',
            ring: 'border-rose-200/70 bg-rose-400/20 shadow-rose-950/45',
            fill: 'bg-rose-300',
            track: 'from-rose-500/85 via-rose-300/70 to-rose-100/60',
            glow: 'shadow-[0_0_46px_rgba(251,113,133,0.40)]',
            label: 'Decisão urgente',
        };
    }

    if (percentage <= 50) {
        return {
            name: 'warning',
            text: 'text-amber-100',
            subtleText: 'text-amber-100/75',
            ring: 'border-amber-200/70 bg-amber-300/20 shadow-amber-950/40',
            fill: 'bg-amber-300',
            track: 'from-amber-400/85 via-orange-300/70 to-amber-100/60',
            glow: 'shadow-[0_0_42px_rgba(251,191,36,0.34)]',
            label: 'Tempo em atenção',
        };
    }

    return {
        name: 'safe',
        text: 'text-emerald-100',
        subtleText: 'text-emerald-100/75',
        ring: 'border-emerald-200/65 bg-emerald-300/15 shadow-emerald-950/35',
        fill: 'bg-emerald-300',
        track: 'from-emerald-400/85 via-lime-300/70 to-emerald-100/60',
        glow: 'shadow-[0_0_38px_rgba(16,185,129,0.30)]',
        label: 'Tempo confortável',
    };
}

export function isCurrentUserTurn(state: PokerStateLike) {
    if (!state || state?.isFinished) {
        return false;
    }

    if (Boolean(state?.canAct) || Boolean(state?.currentTurn?.canAct)) {
        return true;
    }

    if (isMultiSeatLayout(state)) {
        const currentSeat = currentUserSeatNumber(state);
        const currentTurnSeat = multiSeatCurrentTurnSeat(state);
        const currentPlayer = multiSeatPlayers(state).find((player) => Number(player?.seatNumber ?? 0) === currentSeat);

        return currentSeat > 0
            && currentTurnSeat !== null
            && currentSeat === currentTurnSeat
            && !Boolean(currentPlayer?.isBot);
    }

    const role = String(state?.currentTurn?.actorRole ?? state?.currentTurn?.player ?? '').toLowerCase();
    const label = String(state?.currentTurn?.actorLabel ?? '').toLowerCase();

    return role === 'player' || label.includes('você') || label.includes('voce');
}

export function seatLiveStateLabel(player: PokerPlayerLike, isCurrentTurn: boolean, hasFolded: boolean, isAllIn: boolean) {
    if (hasFolded) {
        return 'Fold';
    }

    if (isAllIn) {
        return 'All-in';
    }

    if (isCurrentTurn) {
        return 'Ativo';
    }

    if (Boolean(player?.isDisconnected) || player?.status === 'disconnected') {
        return 'Desconectado';
    }

    return 'Aguardando';
}

export function seatLiveStateClasses(label: unknown) {
    const normalized = String(label ?? '').toLowerCase();

    if (normalized.includes('ativo')) {
        return 'border-emerald-100/55 bg-emerald-300 text-emerald-950 shadow-emerald-950/25';
    }

    if (normalized.includes('all')) {
        return 'border-rose-100/60 bg-rose-400 text-rose-950 shadow-rose-950/25';
    }

    if (normalized.includes('fold')) {
        return 'border-slate-400/25 bg-slate-950/80 text-slate-200';
    }

    if (normalized.includes('desconectado')) {
        return 'border-orange-100/45 bg-orange-300/20 text-orange-100';
    }

    return 'border-white/15 bg-white/[0.08] text-slate-200';
}
