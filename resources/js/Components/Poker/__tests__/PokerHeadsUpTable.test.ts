import { describe, expect, it } from 'vitest';
import { PokerHeadsUpTable } from '../PokerHeadsUpTable';

describe('PokerHeadsUpTable component', () => {
    it('exports the heads-up table with valid module imports', () => {
        expect(typeof PokerHeadsUpTable).toBe('function');
    });
});
