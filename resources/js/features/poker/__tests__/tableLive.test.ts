import { describe, expect, it } from 'vitest';
import {
    isCurrentUserTurn,
    liveTurnTimerTone,
    multiSeatAssistedModeLabel,
    seatLiveStateClasses,
    seatLiveStateLabel,
    tournamentHudMetrics,
} from '../utils/tableLive';

describe('tableLive poker helpers', () => {
    const tournamentState = {
        smallBlind: 10,
        bigBlind: 20,
        multiplayerPerspective: { seatNumber: 2 },
        tournamentRuntime: {
            name: 'Mesa Final',
            blindLevel: 3,
            smallBlind: 25,
            bigBlind: 50,
            nextSmallBlind: 50,
            nextBigBlind: 100,
        },
        multiSeat: {
            enabled: true,
            players: [
                { seatNumber: 1, nickname: 'Ana', stack: 300 },
                { seatNumber: 2, nickname: 'Bia', stack: 500 },
                { seatNumber: 3, nickname: 'Caio', stack: 0 },
                { seatNumber: 4, nickname: 'Davi', stack: 200, hasFolded: true },
            ],
        },
    };

    it('builds tournament HUD metrics from runtime and table state', () => {
        expect(tournamentHudMetrics(tournamentState)).toEqual({
            name: 'Mesa Final',
            status: 'ao vivo',
            level: 3,
            blinds: '25 / 50',
            nextBlinds: '50 / 100',
            remaining: '2',
            averageStack: '10 BB',
            position: '#1',
        });

        expect(tournamentHudMetrics({})).toBeNull();
    });

    it('shows assisted mode only for eliminated tournament observers', () => {
        expect(multiSeatAssistedModeLabel(tournamentState, null)).toBe('Modo assistido — você foi eliminado, mas a mesa continua em acompanhamento.');
        expect(multiSeatAssistedModeLabel(tournamentState, { seatNumber: 2 })).toBeNull();
        expect(multiSeatAssistedModeLabel({}, null)).toBeNull();
    });

    it('classifies live timer tone by urgency', () => {
        expect(liveTurnTimerTone({ isExpired: true, percentage: 80 }).name).toBe('danger');
        expect(liveTurnTimerTone({ percentage: 10 }).name).toBe('danger');
        expect(liveTurnTimerTone({ percentage: 50 }).name).toBe('warning');
        expect(liveTurnTimerTone({ percentage: 90 }).name).toBe('safe');
    });

    it('detects when the current user can act', () => {
        expect(isCurrentUserTurn({ canAct: true })).toBe(true);
        expect(isCurrentUserTurn({ isFinished: true, canAct: true })).toBe(false);
        expect(isCurrentUserTurn({
            multiplayerPerspective: { seatNumber: 2 },
            currentTurn: { seatNumber: 2 },
            multiSeat: {
                enabled: true,
                players: [
                    { seatNumber: 1, isBot: true },
                    { seatNumber: 2, isBot: false },
                ],
            },
        })).toBe(true);
        expect(isCurrentUserTurn({
            multiplayerPerspective: { seatNumber: 1 },
            currentTurn: { seatNumber: 1 },
            multiSeat: {
                enabled: true,
                players: [
                    { seatNumber: 1, isBot: true },
                    { seatNumber: 2, isBot: false },
                ],
            },
        })).toBe(false);
        expect(isCurrentUserTurn({ currentTurn: { actorLabel: 'Você decide' } })).toBe(true);
    });

    it('formats live seat labels and classes', () => {
        expect(seatLiveStateLabel({}, false, true, false)).toBe('Fold');
        expect(seatLiveStateLabel({}, false, false, true)).toBe('All-in');
        expect(seatLiveStateLabel({}, true, false, false)).toBe('Ativo');
        expect(seatLiveStateLabel({ status: 'disconnected' }, false, false, false)).toBe('Desconectado');
        expect(seatLiveStateLabel({}, false, false, false)).toBe('Aguardando');

        expect(seatLiveStateClasses('Ativo')).toContain('bg-emerald-300');
        expect(seatLiveStateClasses('All-in')).toContain('bg-rose-400');
        expect(seatLiveStateClasses('Fold')).toContain('bg-slate-950');
        expect(seatLiveStateClasses('Desconectado')).toContain('bg-orange-300');
    });
});
