import { useMemo } from 'react';
import type { CardMatcher, PokerCard, PokerTableState, PokerWinnerPayload } from '../types/poker';
import { pokerCardSignature, uniquePokerCards } from '../utils/cards';

function cardListFromWinner(winner: PokerWinnerPayload | null | undefined): PokerCard[] {
  return uniquePokerCards([
    ...(winner?.highlight_cards ?? []),
    ...(winner?.highlightCards ?? []),
  ]);
}

export function extractShowdownHighlightCards(state: PokerTableState | null | undefined): PokerCard[] {
  const winner = state?.conclusion?.winner ?? state?.multiSeat?.winner ?? state?.multiSeat?.showdown?.winner;
  const directCards = uniquePokerCards([
    ...(state?.highlight_cards ?? []),
    ...(state?.highlightCards ?? []),
    ...(state?.conclusion?.highlight_cards ?? []),
    ...(state?.conclusion?.highlightCards ?? []),
    ...cardListFromWinner(winner),
  ]);

  if (directCards.length > 0) {
    return directCards;
  }

  const winnerLists = [
    ...(state?.conclusion?.winners ?? []),
    ...(state?.multiSeat?.winners ?? []),
    ...(state?.multiSeat?.showdown?.winners ?? []),
  ];

  return uniquePokerCards(winnerLists.flatMap((item) => cardListFromWinner(item)));
}

export function createShowdownHighlightMatcher(cards: PokerCard[] = []): CardMatcher {
  const signatures = new Set(cards.map((card) => pokerCardSignature(card)).filter(Boolean));

  return (card) => signatures.has(pokerCardSignature(card));
}

export function useShowdownHighlights(state: PokerTableState | null | undefined): {
  highlightCards: PokerCard[];
  hasHighlights: boolean;
  isHighlightedCard: CardMatcher;
} {
  const highlightCards = useMemo(() => extractShowdownHighlightCards(state), [state]);
  const isHighlightedCard = useMemo(() => createShowdownHighlightMatcher(highlightCards), [highlightCards]);

  return {
    highlightCards,
    hasHighlights: highlightCards.length > 0,
    isHighlightedCard,
  };
}
