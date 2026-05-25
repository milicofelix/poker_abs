import { describe, expect, it } from 'vitest';
import {
    multiSeatCardVisibilityLabel,
    multiSeatShowdownCardsForPlayer,
    normalizeCardCollection,
} from '../utils/tableCards';

const aceSpades = { rank: 'A', suit: 'spades' };
const kingHearts = { rank: 'K', suit: 'hearts' };
const queenClubs = { rank: 'Q', suit: 'clubs' };
const jackDiamonds = { rank: 'J', suit: 'diamonds' };

describe('tableCards poker helpers', () => {
    it('normalizes card collections defensively', () => {
        expect(normalizeCardCollection(null)).toEqual([]);
        expect(normalizeCardCollection([aceSpades, null, undefined, kingHearts])).toEqual([aceSpades, kingHearts]);
    });

    it('prefers public showdown cards by seat when showdown is resolved', () => {
        const state = {
            isFinished: true,
            street: 'showdown',
            multiSeat: {
                showdownCardsBySeat: {
                    2: [aceSpades, kingHearts, queenClubs],
                },
            },
        };

        expect(multiSeatShowdownCardsForPlayer({ seatNumber: 2, cards: [queenClubs, jackDiamonds] }, state, false)).toEqual([aceSpades, kingHearts]);
    });

    it('uses current user private cards before showdown public cards are available', () => {
        const state = {
            playerCards: [queenClubs, jackDiamonds],
        };

        expect(multiSeatShowdownCardsForPlayer({ seatNumber: 1, cards: [aceSpades, kingHearts] }, state, true)).toEqual([queenClubs, jackDiamonds]);
    });

    it('falls back to legacy opponent cards for resolved heads-up showdown', () => {
        const state = {
            isFinished: true,
            street: 'showdown',
            opponentCards: [aceSpades, kingHearts],
            multiSeat: {
                showdownCardsBySeat: {},
            },
        };

        expect(multiSeatShowdownCardsForPlayer({ seatNumber: 2 }, state, false, 1)).toEqual([aceSpades, kingHearts]);
    });

    it('labels card visibility states for seat controls', () => {
        expect(multiSeatCardVisibilityLabel(true, false, false)).toBe('Revelar suas cartas');
        expect(multiSeatCardVisibilityLabel(true, false, true)).toBe('Ocultar suas cartas');
        expect(multiSeatCardVisibilityLabel(false, false, false)).toBe('Cartas protegidas');
        expect(multiSeatCardVisibilityLabel(true, true, false)).toBe('Cartas abertas no showdown');
        expect(multiSeatCardVisibilityLabel(true, true, false, true)).toBe('Cartas descartadas');
    });
});
