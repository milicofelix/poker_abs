import {
    isMultiSeatLayout,
    multiSeatPlayers,
    winnerPlayer,
} from './tableState';

type PokerStateLike = Record<string, any> | null | undefined;
type PokerPlayerLike = Record<string, any> | null | undefined;

export function multiSeatWinnerSeats(state: PokerStateLike) {
    const winnerSeats = Array.isArray(state?.multiSeat?.winnerSeats) ? state.multiSeat.winnerSeats : [];
    const normalizedWinnerSeats = winnerSeats
        .map(Number)
        .filter((seatNumber: number) => seatNumber > 0);
    const conclusionSeat = Number(state?.conclusion?.winner?.seatNumber ?? 0);

    if (normalizedWinnerSeats.length > 0) {
        return [...new Set(normalizedWinnerSeats)];
    }

    return conclusionSeat > 0 ? [conclusionSeat] : [];
}

export function isMultiSeatSplitPot(state: PokerStateLike) {
    return Boolean(state?.isFinished) && multiSeatWinnerSeats(state).length > 1;
}

export function isMultiSeatWinner(state: PokerStateLike, seatNumber: unknown) {
    return Boolean(state?.isFinished) && multiSeatWinnerSeats(state).includes(Number(seatNumber));
}

export function multiSeatWinnerBadgeLabel(state: PokerStateLike, seatNumber: unknown) {
    if (!isMultiSeatWinner(state, seatNumber)) {
        return null;
    }

    return isMultiSeatSplitPot(state) ? 'Empate' : 'Vencedor';
}

export function multiSeatShowdownSummary(state: PokerStateLike) {
    if (!state?.isFinished) {
        return state?.currentTurn?.message ?? 'Sincronizando mesa.';
    }

    if (isMultiSeatSplitPot(state)) {
        return `Pote dividido entre ${multiSeatWinnerSeats(state).length} jogadores.`;
    }

    return state?.currentTurn?.message ?? 'Showdown concluído. Inicie uma nova mão para liberar novas ações.';
}

export function narrativeStageLabel(state: PokerStateLike) {
    if (state?.isFinished) {
        return isMultiSeatSplitPot(state) ? 'Pote dividido' : 'Showdown decidido';
    }

    const street = String(state?.street ?? '').toLowerCase();
    const labels: Record<string, string> = {
        pre_flop: 'Pré-flop em andamento',
        preflop: 'Pré-flop em andamento',
        flop: 'Flop aberto',
        turn: 'Turn revelado',
        river: 'River revelado',
        showdown: 'Showdown',
        waiting: 'Aguardando próxima mão',
    };

    return labels[street] ?? 'Mão em andamento';
}

export function narrativeStageDescription(state: PokerStateLike, currentTurnPlayer: PokerPlayerLike) {
    if (state?.isFinished) {
        if (isMultiSeatSplitPot(state)) {
            return `O pote foi dividido entre ${multiSeatWinnerSeats(state).length} jogadores. Revise as mãos e inicie a próxima rodada.`;
        }

        const winner = multiSeatPlayers(state).find((player) => isMultiSeatWinner(state, player?.seatNumber));
        const winnerName = winner?.nickname ?? winner?.displayName ?? (winner?.seatNumber ? `Assento ${winner.seatNumber}` : null);

        return winnerName
            ? `${winnerName} levou o pote. A mesa está pronta para conferir o showdown antes da próxima mão.`
            : 'Showdown concluído. Confira o resultado e inicie uma nova mão quando estiver pronto.';
    }

    if (currentTurnPlayer?.nickname || currentTurnPlayer?.displayName) {
        return `${currentTurnPlayer.nickname ?? currentTurnPlayer.displayName} está com a decisão da rodada.`;
    }

    return state?.currentTurn?.message ?? 'A mesa está sincronizando a próxima ação.';
}

export function narrativeWinnerNames(state: PokerStateLike) {
    const winners = multiSeatWinnerSeats(state);

    if (winners.length === 0) {
        return 'A definir';
    }

    return multiSeatPlayers(state)
        .filter((player) => winners.includes(Number(player?.seatNumber ?? 0)))
        .map((player) => player?.nickname ?? player?.displayName ?? `Assento ${player.seatNumber}`)
        .join(' · ') || 'A definir';
}

export function narrativeTimelineItems(state: PokerStateLike) {
    const currentStreet = String(state?.street ?? '').toLowerCase();
    const order = ['pre_flop', 'flop', 'turn', 'river', 'showdown'];
    const safeStreet = currentStreet === 'preflop' ? 'pre_flop' : currentStreet;
    const currentIndex = Math.max(0, order.indexOf(safeStreet));

    return [
        { key: 'pre_flop', label: 'Pré-flop' },
        { key: 'flop', label: 'Flop' },
        { key: 'turn', label: 'Turn' },
        { key: 'river', label: 'River' },
        { key: 'showdown', label: 'Showdown' },
    ].map((item, index) => ({
        ...item,
        active: !state?.isFinished && item.key === safeStreet,
        done: Boolean(state?.isFinished) || index < currentIndex,
    }));
}

export function showdownCinematicWinnerLabel(state: PokerStateLike) {
    if (!state?.isFinished) {
        return 'Aguardando resultado';
    }

    if (isMultiSeatLayout(state)) {
        const winners = multiSeatPlayers(state).filter((player) => isMultiSeatWinner(state, player?.seatNumber));
        const winnerNames = winners
            .map((player) => player?.nickname ?? player?.displayName ?? `Assento ${player?.seatNumber}`)
            .filter(Boolean);

        if (isMultiSeatSplitPot(state)) {
            return winnerNames.length > 0 ? `Pote dividido: ${winnerNames.join(' · ')}` : 'Pote dividido';
        }

        return winnerNames[0] ? `${winnerNames[0]} levou o pote` : 'Vencedor definido';
    }

    const winner = winnerPlayer(state);

    if (winner === 'tie') {
        return 'Pote dividido no showdown';
    }

    if (winner === 'player') {
        return 'Você levou o pote';
    }

    if (winner === 'opponent') {
        const opponentName = state?.playersContext?.opponents?.[0]?.nickname ?? 'Adversário';
        return `${opponentName} levou o pote`;
    }

    return 'Resultado definido';
}

export function showdownCinematicBestHandLabel(state: PokerStateLike) {
    if (isMultiSeatLayout(state)) {
        const winners = multiSeatPlayers(state).filter((player) => isMultiSeatWinner(state, player?.seatNumber));
        const winnerBestHand = winners.find((player) => player?.bestHand?.name)?.bestHand?.name;

        return winnerBestHand ?? state?.bestHand?.name ?? 'Melhor combinação revelada';
    }

    if (winnerPlayer(state) === 'opponent') {
        return state?.opponentBestHand?.name ?? 'Melhor combinação revelada';
    }

    return state?.bestHand?.name ?? 'Melhor combinação revelada';
}
