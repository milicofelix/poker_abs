import { describe, expect, it } from 'vitest';
import { PremiumActionControlPanel } from '../PokerActionControls';

describe('PokerActionControls components', () => {
    it('exports premium action controls with valid module imports', () => {
        expect(typeof PremiumActionControlPanel).toBe('function');
    });
});
