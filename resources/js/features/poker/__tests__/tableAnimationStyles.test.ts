import { describe, expect, it } from 'vitest';
import {
    POKER_TABLE_ACTION_EFFECT_STYLES,
    POKER_TABLE_ANIMATION_STYLES,
    POKER_TABLE_BASE_MOTION_STYLES,
    POKER_TABLE_REDUCED_MOTION_STYLES,
    POKER_TABLE_SHOWDOWN_MOTION_STYLES,
} from '../styles/tableAnimationStyles';

describe('tableAnimationStyles poker helpers', () => {
    it('keeps the animation groups assembled for the table style injector', () => {
        expect(POKER_TABLE_ANIMATION_STYLES).toContain(POKER_TABLE_BASE_MOTION_STYLES);
        expect(POKER_TABLE_ANIMATION_STYLES).toContain(POKER_TABLE_ACTION_EFFECT_STYLES);
        expect(POKER_TABLE_ANIMATION_STYLES).toContain(POKER_TABLE_SHOWDOWN_MOTION_STYLES);
        expect(POKER_TABLE_ANIMATION_STYLES).toContain(POKER_TABLE_REDUCED_MOTION_STYLES);
    });

    it('keeps selectors used by table components available', () => {
        expect(POKER_TABLE_ANIMATION_STYLES).toContain('.poker-action-flash');
        expect(POKER_TABLE_ANIMATION_STYLES).toContain('.poker-pot-receive');
        expect(POKER_TABLE_ANIMATION_STYLES).toContain('.poker-live-border-sweep::before');
        expect(POKER_TABLE_ANIMATION_STYLES).toContain('.poker-action-chip-flight');
        expect(POKER_TABLE_ANIMATION_STYLES).toContain('.poker-live-action-toast');
        expect(POKER_TABLE_ANIMATION_STYLES).toContain('.poker-showdown-winning-card');
        expect(POKER_TABLE_ANIMATION_STYLES).toContain('@media (prefers-reduced-motion: reduce)');
    });
});
