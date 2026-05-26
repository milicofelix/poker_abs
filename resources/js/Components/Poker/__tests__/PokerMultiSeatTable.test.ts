import { describe, expect, it } from 'vitest';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { MultiSeatPokerTable } from '../PokerMultiSeatTable';

describe('PokerMultiSeatTable component', () => {
    it('exports the multi-seat table with valid module imports', () => {
        expect(typeof MultiSeatPokerTable).toBe('function');
    });

    it('passes showdown highlights to the current player seat', () => {
        const aceOfHearts = { rank: 'A', suit: 'hearts', label: 'A♥' };
        const state = {
            isFinished: true,
            street: 'showdown',
            pot: 120,
            smallBlind: 10,
            bigBlind: 20,
            playerCards: [aceOfHearts, { rank: '2', suit: 'clubs', label: '2♣' }],
            communityCards: [
                { rank: 'K', suit: 'spades', label: 'K♠' },
                { rank: 'Q', suit: 'diamonds', label: 'Q♦' },
                { rank: 'J', suit: 'clubs', label: 'J♣' },
                { rank: '9', suit: 'hearts', label: '9♥' },
                { rank: '7', suit: 'spades', label: '7♠' },
            ],
            bestHand: {
                name: 'Carta alta',
                rank: 1,
                highlightCards: [aceOfHearts],
            },
            conclusion: {
                winner: {
                    player: 'player',
                    seatNumber: 1,
                    handName: 'Carta alta',
                    highlightCards: [aceOfHearts],
                },
            },
            multiplayerPerspective: { seatNumber: 1 },
            tableCapacity: { maxPlayers: 2 },
            multiSeat: {
                enabled: true,
                maxPlayers: 2,
                winnerSeats: [1],
                showdownCardsRevealed: true,
                showdownCardsBySeat: {
                    1: [aceOfHearts, { rank: '2', suit: 'clubs', label: '2♣' }],
                    2: [{ rank: 'K', suit: 'hearts', label: 'K♥' }, { rank: '3', suit: 'diamonds', label: '3♦' }],
                },
                players: [
                    { seatNumber: 1, nickname: 'Adriano', stack: 980, bestHand: { name: 'Carta alta' } },
                    { seatNumber: 2, nickname: 'Bot', stack: 920, bestHand: { name: 'Carta alta' } },
                ],
            },
            actionHistory: [],
            canAct: false,
        };

        const markup = renderToStaticMarkup(React.createElement(MultiSeatPokerTable, {
            state,
            community: { visible: state.communityCards, hiddenCount: 0 },
            playerCardsRevealed: true,
            setPlayerCardsRevealed: () => null,
        }));

        expect(markup).toContain('data-showdown-highlight="true"');
        expect((markup.match(/data-showdown-highlight="true"/g) ?? [])).toHaveLength(1);
    });
});
