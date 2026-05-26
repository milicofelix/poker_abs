import { describe, expect, it } from 'vitest';
import {
    HeadsUpBestHandPanel,
    HeadsUpOpponentPanel,
    HeadsUpPlayerHandPanel,
    HeadsUpTurnStatusPanel,
} from '../PokerHeadsUpPanels';

describe('PokerHeadsUpPanels components', () => {
    it('exports heads-up panels with valid module imports', () => {
        expect(typeof HeadsUpBestHandPanel).toBe('function');
        expect(typeof HeadsUpOpponentPanel).toBe('function');
        expect(typeof HeadsUpPlayerHandPanel).toBe('function');
        expect(typeof HeadsUpTurnStatusPanel).toBe('function');
    });
});
