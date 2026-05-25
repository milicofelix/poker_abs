import { describe, expect, it } from 'vitest';
import { playerInitials, stackPressureClasses, stackPressureLabel } from '../utils/tablePlayers';

describe('tablePlayers poker helpers', () => {
    it('builds compact initials from display names', () => {
        expect(playerInitials('Adriano Freitas')).toBe('AF');
        expect(playerInitials(' Dealer ')).toBe('D');
        expect(playerInitials('')).toBe('P');
        expect(playerInitials(null)).toBe('J');
    });

    it('labels stack pressure using the current big blind', () => {
        const state = { bigBlind: 20 };

        expect(stackPressureLabel({ stack: 0 }, state)).toBe('Sem fichas');
        expect(stackPressureLabel({ stack: 60 }, state)).toBe('Short stack');
        expect(stackPressureLabel({ stack: 160 }, state)).toBe('Pressão');
        expect(stackPressureLabel({ stack: 500 }, state)).toBe('Stack saudável');
    });

    it('falls back to multi-seat blind information', () => {
        expect(stackPressureLabel({ stack: 75 }, { multiSeat: { blinds: { bigBlind: 25 } } })).toBe('Short stack');
    });

    it('maps stack pressure labels to stable visual classes', () => {
        expect(stackPressureClasses({ stack: 0 }, { bigBlind: 20 })).toContain('bg-slate-950');
        expect(stackPressureClasses({ stack: 40 }, { bigBlind: 20 })).toContain('bg-rose-400');
        expect(stackPressureClasses({ stack: 120 }, { bigBlind: 20 })).toContain('bg-amber-300');
        expect(stackPressureClasses({ stack: 500 }, { bigBlind: 20 })).toContain('bg-emerald-300');
    });
});
