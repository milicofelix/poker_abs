import { describe, expect, it } from 'vitest';
import {
    currentUserSeatNumber,
    isMultiSeatLayout,
    multiSeatBlindSummary,
    multiSeatPlayers,
    visibleCommunityCards,
    winnerBadgeLabel,
} from '../utils/tableState';

const board = [
    { rank: 'A', suit: 'spades' },
    { rank: 'K', suit: 'hearts' },
    { rank: 'Q', suit: 'clubs' },
    { rank: 'J', suit: 'diamonds' },
    { rank: '10', suit: 'spades' },
];

describe('tableState poker helpers', () => {
    it('reveals community cards according to the current street', () => {
        expect(visibleCommunityCards({ street: 'pre_flop', communityCards: board })).toEqual({
            visible: [],
            hiddenCount: 5,
        });

        expect(visibleCommunityCards({ street: 'flop', communityCards: board })).toEqual({
            visible: board.slice(0, 3),
            hiddenCount: 2,
        });

        expect(visibleCommunityCards({ street: 'river', communityCards: board })).toEqual({
            visible: board,
            hiddenCount: 0,
        });
    });

    it('reveals the full board on finished all-in runouts', () => {
        const result = visibleCommunityCards({
            street: 'turn',
            isFinished: true,
            communityCards: board,
            multiSeat: {
                players: [{ seatNumber: 1, stack: 0 }],
            },
        });

        expect(result.visible).toEqual(board);
        expect(result.hiddenCount).toBe(0);
    });

    it('enables multi-seat layout only with enough active players', () => {
        expect(isMultiSeatLayout({ multiSeat: { enabled: true, players: [{}, {}] } })).toBe(true);
        expect(isMultiSeatLayout({ multiSeat: { enabled: true, players: [{}] } })).toBe(false);
        expect(isMultiSeatLayout({ multiSeat: { enabled: false, players: [{}, {}] } })).toBe(false);
    });

    it('normalizes and orders multi-seat players by seat number', () => {
        const players = multiSeatPlayers({
            multiSeat: {
                players: [
                    { seatNumber: 4, nickname: 'D' },
                    { seatNumber: 0, nickname: 'Invalid' },
                    { seatNumber: 2, nickname: 'B' },
                    null,
                    { seatNumber: 1, nickname: 'A' },
                ],
            },
        });

        expect(players.map((player) => player.nickname)).toEqual(['A', 'B', 'D']);
    });

    it('prefers multiplayer perspective when resolving the current user seat', () => {
        expect(currentUserSeatNumber({
            multiplayerPerspective: { seatNumber: 3 },
            playersContext: { current: { seatNumber: 1 } },
        })).toBe(3);

        expect(currentUserSeatNumber({
            playersContext: { current: { seatNumber: 2 } },
        })).toBe(2);
    });

    it('formats blind and winner summaries for the table UI', () => {
        expect(multiSeatBlindSummary({
            multiSeat: {
                dealerSeat: 1,
                blinds: {
                    smallBlindSeat: 2,
                    bigBlindSeat: 3,
                },
            },
        })).toBe('Dealer: assento 1 • SB: assento 2 • BB: assento 3');

        expect(winnerBadgeLabel({ isFinished: true, conclusion: { winner: { player: 'tie' } } }, 'player')).toBe('Empate');
        expect(winnerBadgeLabel({ isFinished: true, conclusion: { winner: { player: 'player' } } }, 'player')).toBe('Vencedor');
        expect(winnerBadgeLabel({ isFinished: true, conclusion: { winner: { player: 'opponent' } } }, 'player')).toBe('Derrotado');
    });
});
