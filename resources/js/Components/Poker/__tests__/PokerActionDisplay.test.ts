import { describe, expect, it } from 'vitest';
import {
    ActionEffectOverlay,
    LatestActionToast,
    LivePotDisplay,
    PokerActionReplayRail,
} from '../PokerActionDisplay';

describe('PokerActionDisplay barrel', () => {
    it('exports action display components with valid module imports', () => {
        expect(typeof ActionEffectOverlay).toBe('function');
        expect(typeof LatestActionToast).toBe('function');
        expect(typeof LivePotDisplay).toBe('function');
        expect(typeof PokerActionReplayRail).toBe('function');
    });
});
