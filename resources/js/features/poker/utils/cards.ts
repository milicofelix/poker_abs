import type { PokerCard } from '../types/poker';

const SUIT_ALIASES: Record<string, string> = {
  h: 'hearts',
  heart: 'hearts',
  hearts: 'hearts',
  '♥': 'hearts',
  d: 'diamonds',
  diamond: 'diamonds',
  diamonds: 'diamonds',
  '♦': 'diamonds',
  c: 'clubs',
  club: 'clubs',
  clubs: 'clubs',
  '♣': 'clubs',
  s: 'spades',
  spade: 'spades',
  spades: 'spades',
  '♠': 'spades',
};

const RANK_ALIASES: Record<string, string> = {
  a: 'A',
  ace: 'A',
  k: 'K',
  king: 'K',
  q: 'Q',
  queen: 'Q',
  j: 'J',
  jack: 'J',
  t: '10',
  ten: '10',
};

export function normalizePokerRank(rank: unknown): string {
  const raw = String(rank ?? '').replace(/[♥♦♣♠]/g, '').trim();

  if (raw === '') {
    return '';
  }

  return RANK_ALIASES[raw.toLowerCase()] ?? raw.toUpperCase();
}

export function normalizePokerSuit(suit: unknown): string {
  const raw = String(suit ?? '').trim().toLowerCase();

  return SUIT_ALIASES[raw] ?? raw;
}

export function parseCardCode(code: unknown): Pick<PokerCard, 'rank' | 'suit'> {
  const raw = String(code ?? '').trim();

  if (raw === '') {
    return {};
  }

  const suitSymbol = raw.match(/[♥♦♣♠]/)?.[0];
  const suitSuffix = raw.match(/([hdcs])$/i)?.[1];
  const suit = suitSymbol ?? suitSuffix ?? '';
  const rank = raw
    .replace(/[♥♦♣♠]/g, '')
    .replace(/[hdcs]$/i, '')
    .trim();

  return {
    rank: normalizePokerRank(rank),
    suit: normalizePokerSuit(suit),
  };
}

export function normalizePokerCard(card: PokerCard | null | undefined): Required<Pick<PokerCard, 'rank' | 'suit'>> {
  const parsed = parseCardCode(card?.code ?? card?.label ?? card?.value);

  return {
    rank: normalizePokerRank(card?.rank ?? parsed.rank),
    suit: normalizePokerSuit(card?.suit ?? parsed.suit),
  };
}

export function pokerCardSignature(card: PokerCard | null | undefined): string {
  const normalized = normalizePokerCard(card);

  if (!normalized.rank || !normalized.suit) {
    return '';
  }

  return `${normalized.rank}-${normalized.suit}`;
}

export function uniquePokerCards(cards: Array<PokerCard | null | undefined> = []): PokerCard[] {
  const seen = new Set<string>();

  return cards.filter((card): card is PokerCard => {
    const signature = pokerCardSignature(card);

    if (!signature || seen.has(signature)) {
      return false;
    }

    seen.add(signature);
    return true;
  });
}
