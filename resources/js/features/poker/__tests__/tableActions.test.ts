import { describe, expect, it } from 'vitest';
import {
    actionActorLabel,
    actionFlowSummary,
    actionTimelineItems,
    actionToastTitle,
    actionToneLabel,
    actionVisualTone,
    latestActionAnimationKey,
    latestActionForSeat,
    multiSeatActionPillClasses,
    multiSeatLastActionLabel,
    normalizeActionLabel,
    premiumActionControlItems,
    shouldPulsePot,
    shouldShowMultiSeatActionPill,
} from '../utils/tableActions';

describe('tableActions poker helpers', () => {
    const state = {
        street: 'flop',
        pot: 220,
        actionHistory: [
            { actor: 'player', action: 'check' },
            { seatNumber: 2, action: 'raise', amount: 80 },
            { tablePlayerId: 30, type: 'all_in', amount: 300, nickname: 'Nina' },
        ],
    };

    it('normalizes actor and action labels for table history', () => {
        expect(actionActorLabel({ actor: 'player' })).toBe('Você');
        expect(actionActorLabel({ role: 'bot' })).toBe('Adversário');
        expect(actionActorLabel({ seatNumber: 3 })).toBe('Assento 3');

        expect(normalizeActionLabel({ action: 'folded' })).toBe('Fold');
        expect(normalizeActionLabel({ type: 'all_in' })).toBe('All-in');
    });

    it('builds latest-first action timeline items', () => {
        const items = actionTimelineItems(state);

        expect(items).toHaveLength(3);
        expect(items[0]).toMatchObject({
            actor: 'Nina',
            label: 'All-in',
            amount: 300,
        });
        expect(items[1]).toMatchObject({
            actor: 'Assento 2',
            label: 'Raise',
            amount: 80,
        });
    });

    it('derives animation keys and pot pulse state from the latest action', () => {
        expect(latestActionAnimationKey(state)).toBe('30-all_in-0-flop-220');
        expect(shouldPulsePot(state)).toBe(true);
        expect(latestActionAnimationKey({ street: 'turn', pot: 0, actionHistory: [] })).toBe('street-turn-0');
    });

    it('maps action tones to visual behavior and toast text', () => {
        expect(actionToneLabel('big raise')).toBe('Raise');
        expect(actionToneLabel('Call')).toBe('Call');
        expect(actionVisualTone('Call').effect).toBe('chips');
        expect(actionVisualTone('check').effect).toBe('check');
        expect(actionToastTitle({ actor: 'Nina', label: 'All-in', amount: 300 })).toBe('Nina foi all-in 300');
    });

    it('summarizes the current action flow', () => {
        expect(actionFlowSummary(state)).toEqual({
            title: 'Nina · All-in 300',
            description: 'Flop · mesa em andamento',
        });

        expect(actionFlowSummary({ isFinished: true, actionHistory: [] })).toEqual({
            title: 'Mesa aguardando primeira ação',
            description: 'Mão encerrada sem novas ações registradas.',
        });
    });

    it('resolves per-seat latest action and action pill labels', () => {
        expect(latestActionForSeat(state, { seatNumber: 2 })).toMatchObject({ action: 'raise' });
        expect(latestActionForSeat(state, { tablePlayerId: 30 })).toMatchObject({ type: 'all_in' });
        expect(multiSeatLastActionLabel(state, { seatNumber: 2 }, false, false)).toBe('Raise 80');
        expect(multiSeatLastActionLabel(state, { seatNumber: 4 }, false, true)).toBe('Pensando');
        expect(multiSeatLastActionLabel(state, { seatNumber: 4 }, true, false)).toBe('Fold');

        expect(multiSeatActionPillClasses('Call', false, false)).toContain('bg-emerald-300');
        expect(multiSeatActionPillClasses('Pensando', true, false)).toContain('bg-emerald-300');
        expect(shouldShowMultiSeatActionPill('Aguardando')).toBe(false);
        expect(shouldShowMultiSeatActionPill('Raise')).toBe(true);
    });

    it('builds premium action control metadata from state', () => {
        expect(premiumActionControlItems({
            canAct: true,
            callAmount: 40,
            minimumRaiseTo: 100,
            maximumRaiseTo: 500,
        })).toMatchObject([
            { key: 'fold', enabled: true },
            { key: 'call', helper: '40', enabled: true },
            { key: 'raise', helper: 'mín. 100', enabled: true },
            { key: 'all-in', helper: '500', enabled: true },
        ]);
    });
});
