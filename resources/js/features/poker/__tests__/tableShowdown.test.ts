import { describe, expect, it } from 'vitest';
import {
    isMultiSeatSplitPot,
    isMultiSeatWinner,
    multiSeatShowdownSummary,
    multiSeatWinnerBadgeLabel,
    multiSeatWinnerSeats,
    narrativeStageDescription,
    narrativeStageLabel,
    narrativeTimelineItems,
    narrativeWinnerNames,
    showdownCinematicBestHandLabel,
    showdownCinematicWinnerLabel,
} from '../utils/tableShowdown';

describe('tableShowdown poker helpers', () => {
    const multiSeatState = {
        isFinished: true,
        street: 'showdown',
        multiSeat: {
            enabled: true,
            winnerSeats: [2, '2', 4],
            players: [
                { seatNumber: 1, nickname: 'Ana', stack: 100 },
                { seatNumber: 2, nickname: 'Bia', stack: 200, bestHand: { name: 'Flush' } },
                { seatNumber: 4, displayName: 'Davi', stack: 80, bestHand: { name: 'Sequência' } },
            ],
        },
        conclusion: {
            winner: { seatNumber: 2 },
        },
    };

    it('normalizes winner seats and winner badges', () => {
        expect(multiSeatWinnerSeats(multiSeatState)).toEqual([2, 4]);
        expect(isMultiSeatWinner(multiSeatState, 2)).toBe(true);
        expect(isMultiSeatWinner(multiSeatState, 3)).toBe(false);
        expect(isMultiSeatSplitPot(multiSeatState)).toBe(true);
        expect(multiSeatWinnerBadgeLabel(multiSeatState, 2)).toBe('Empate');
    });

    it('summarizes multi-seat showdown state', () => {
        expect(multiSeatShowdownSummary(multiSeatState)).toBe('Pote dividido entre 2 jogadores.');
        expect(multiSeatShowdownSummary({ currentTurn: { message: 'Vez do assento 3' } })).toBe('Vez do assento 3');
    });

    it('builds narrative labels, descriptions and timeline items', () => {
        expect(narrativeStageLabel({ street: 'turn' })).toBe('Turn revelado');
        expect(narrativeStageLabel(multiSeatState)).toBe('Pote dividido');
        expect(narrativeStageDescription(multiSeatState, null)).toBe('O pote foi dividido entre 2 jogadores. Revise as mãos e inicie a próxima rodada.');
        expect(narrativeStageDescription({ currentTurn: { message: 'Sincronizando' } }, { nickname: 'Ana' })).toBe('Ana está com a decisão da rodada.');
        expect(narrativeWinnerNames(multiSeatState)).toBe('Bia · Davi');

        expect(narrativeTimelineItems({ street: 'river' })).toMatchObject([
            { key: 'pre_flop', done: true },
            { key: 'flop', done: true },
            { key: 'turn', done: true },
            { key: 'river', active: true },
            { key: 'showdown', done: false },
        ]);
    });

    it('formats cinematic winner and best-hand labels', () => {
        expect(showdownCinematicWinnerLabel(multiSeatState)).toBe('Pote dividido: Bia · Davi');
        expect(showdownCinematicBestHandLabel(multiSeatState)).toBe('Flush');

        expect(showdownCinematicWinnerLabel({
            isFinished: true,
            conclusion: { winner: { player: 'player' } },
        })).toBe('Você levou o pote');

        expect(showdownCinematicWinnerLabel({
            isFinished: true,
            conclusion: { winner: { player: 'opponent' } },
            playersContext: { opponents: [{ nickname: 'Bot Alfa' }] },
        })).toBe('Bot Alfa levou o pote');

        expect(showdownCinematicBestHandLabel({
            conclusion: { winner: { player: 'opponent' } },
            opponentBestHand: { name: 'Trinca' },
        })).toBe('Trinca');
    });
});
