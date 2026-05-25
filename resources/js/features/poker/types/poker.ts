export type PokerSuit = 'hearts' | 'diamonds' | 'clubs' | 'spades' | string;

export type PokerRank =
  | 'A'
  | 'K'
  | 'Q'
  | 'J'
  | 'T'
  | '10'
  | '9'
  | '8'
  | '7'
  | '6'
  | '5'
  | '4'
  | '3'
  | '2'
  | string;

export type PokerCard = {
  rank?: PokerRank | null;
  suit?: PokerSuit | null;
  label?: string | null;
  code?: string | null;
  value?: string | null;
};

export type PokerSeatNumber = number | string;

export type PokerWinnerPayload = {
  player?: string | number | null;
  seatNumber?: PokerSeatNumber | null;
  tablePlayerId?: number | string | null;
  winning_cards?: PokerCard[] | null;
  winningCards?: PokerCard[] | null;
  highlight_cards?: PokerCard[] | null;
  highlightCards?: PokerCard[] | null;
  hand_name?: string | null;
  handName?: string | null;
};

export type PokerTableState = {
  street?: string | null;
  isFinished?: boolean;
  conclusion?: {
    winner?: PokerWinnerPayload | null;
    winners?: PokerWinnerPayload[] | null;
    winning_cards?: PokerCard[] | null;
    winningCards?: PokerCard[] | null;
    highlight_cards?: PokerCard[] | null;
    highlightCards?: PokerCard[] | null;
  } | null;
  multiSeat?: {
    enabled?: boolean;
    winners?: PokerWinnerPayload[] | null;
    winner?: PokerWinnerPayload | null;
    showdown?: {
      winners?: PokerWinnerPayload[] | null;
      winner?: PokerWinnerPayload | null;
    } | null;
  } | null;
  winning_cards?: PokerCard[] | null;
  winningCards?: PokerCard[] | null;
  highlight_cards?: PokerCard[] | null;
  highlightCards?: PokerCard[] | null;
};

export type CardMatcher = (card: PokerCard | null | undefined) => boolean;
