import { describe, expect, it } from 'vitest';
import { MultiSeatPlayerSpot } from '../PokerSeatDisplay';

describe('PokerSeatDisplay components', () => {
    it('exports multi-seat player spot with valid module imports', () => {
        expect(typeof MultiSeatPlayerSpot).toBe('function');
    });
});
