import { isMultiSeatShowdownResolved } from './tableState';

type PokerStateLike = Record<string, any> | null | undefined;
type PokerPlayerLike = Record<string, any> | null | undefined;

export function normalizeCardCollection(cards: unknown) {
    return Array.isArray(cards) ? cards.filter(Boolean) : [];
}

export function multiSeatShowdownCardsForPlayer(
    player: PokerPlayerLike,
    state: PokerStateLike,
    isCurrentUserSeat: boolean,
    opponentsCount = 0,
) {
    const seatNumber = Number(player?.seatNumber ?? 0);
    const playerCards = normalizeCardCollection(player?.cards);
    const playerShowdownCards = normalizeCardCollection(player?.showdownCards);
    const playerHoleCards = normalizeCardCollection(player?.holeCards);
    const playerHandCards = normalizeCardCollection(player?.handCards);
    const playerPrivateCards = normalizeCardCollection(player?.privateCards);
    const showdownCardsBySeat = state?.multiSeat?.showdownCardsBySeat ?? {};
    const publicSeatCards = normalizeCardCollection(
        showdownCardsBySeat?.[seatNumber]
            ?? showdownCardsBySeat?.[String(seatNumber)]
            ?? playerShowdownCards,
    );

    if (isMultiSeatShowdownResolved(state) && publicSeatCards.length > 0) {
        return publicSeatCards.slice(0, 2);
    }

    if (isCurrentUserSeat) {
        const currentCards = normalizeCardCollection(state?.playerCards);

        return currentCards.length > 0
            ? currentCards
            : [...playerCards, ...playerShowdownCards, ...playerHoleCards, ...playerHandCards, ...playerPrivateCards].slice(0, 2);
    }

    const directCards = [...playerCards, ...playerShowdownCards, ...playerHoleCards, ...playerHandCards, ...playerPrivateCards].slice(0, 2);

    if (directCards.length > 0) {
        return directCards;
    }

    const legacyOpponentCards = normalizeCardCollection(state?.opponentCards);

    if (isMultiSeatShowdownResolved(state) && opponentsCount === 1 && legacyOpponentCards.length > 0) {
        return legacyOpponentCards.slice(0, 2);
    }

    return [];
}

export function multiSeatCardVisibilityLabel(
    isCurrentUserSeat: boolean,
    isFinished: boolean,
    isRevealed: boolean,
    hasFolded = false,
) {
    if (hasFolded) {
        return 'Cartas descartadas';
    }

    if (isFinished) {
        return 'Cartas abertas no showdown';
    }

    if (!isCurrentUserSeat) {
        return 'Cartas protegidas';
    }

    return isRevealed ? 'Ocultar suas cartas' : 'Revelar suas cartas';
}
