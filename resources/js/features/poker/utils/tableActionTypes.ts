export type PokerStateLike = Record<string, any> | null | undefined;
export type PokerActionLike = Record<string, any> | null | undefined;
export type PokerPlayerLike = Record<string, any> | null | undefined;

export type ActionVisualTone = {
    seatClass: string;
    badgeClass: string;
    effect: string;
    verb: string;
    description: string;
};
