import { describe, expect, it } from 'vitest';
import {
  createShowdownHighlightMatcher,
  extractShowdownHighlightCards,
} from '../hooks/useShowdownHighlights';

const kingOfHearts = { rank: 'K', suit: 'hearts' };
const kingOfSpades = { rank: 'K', suit: 'spades' };
const kingOfDiamonds = { rank: 'K', suit: 'diamonds' };
const eightOfClubs = { rank: '8', suit: 'clubs' };

describe('useShowdownHighlights helpers', () => {
  it('usa highlight_cards em vez de winning_cards para não destacar kickers', () => {
    const state = {
      conclusion: {
        winner: {
          highlight_cards: [kingOfHearts, kingOfSpades, kingOfDiamonds],
          winning_cards: [kingOfHearts, kingOfSpades, kingOfDiamonds, eightOfClubs],
        },
      },
    };

    const cards = extractShowdownHighlightCards(state);
    const matcher = createShowdownHighlightMatcher(cards);

    expect(cards).toHaveLength(3);
    expect(matcher(kingOfHearts)).toBe(true);
    expect(matcher(kingOfSpades)).toBe(true);
    expect(matcher(kingOfDiamonds)).toBe(true);
    expect(matcher(eightOfClubs)).toBe(false);
  });

  it('normaliza cartas por code/label para comparar formatos diferentes', () => {
    const matcher = createShowdownHighlightMatcher([
      { code: 'Kh' },
      { label: 'K♠' },
      { rank: 'K', suit: 'diamonds' },
    ]);

    expect(matcher({ rank: 'K', suit: 'hearts' })).toBe(true);
    expect(matcher({ rank: 'K', suit: 'spades' })).toBe(true);
    expect(matcher({ code: 'Kd' })).toBe(true);
    expect(matcher({ code: '8c' })).toBe(false);
  });
});
