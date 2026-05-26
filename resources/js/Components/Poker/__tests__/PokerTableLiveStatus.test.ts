import { describe, expect, it } from 'vitest';
import {
    LiveTurnTimerBadge,
    MobileTableStickyStatus,
    TournamentLiveHud,
    YourTurnOverlay,
} from '../PokerTableLiveStatus';

describe('PokerTableLiveStatus components', () => {
    it('exports live HUD components with valid module imports', () => {
        expect(typeof TournamentLiveHud).toBe('function');
        expect(typeof LiveTurnTimerBadge).toBe('function');
        expect(typeof YourTurnOverlay).toBe('function');
        expect(typeof MobileTableStickyStatus).toBe('function');
    });
});
