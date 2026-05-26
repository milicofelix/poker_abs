import { describe, expect, it } from 'vitest';
import {
    ShowdownCinematicRibbon,
    ShowdownPremiumPanel,
} from '../PokerShowdownDisplay';

describe('PokerShowdownDisplay components', () => {
    it('exports showdown display components with valid module imports', () => {
        expect(typeof ShowdownCinematicRibbon).toBe('function');
        expect(typeof ShowdownPremiumPanel).toBe('function');
    });
});
