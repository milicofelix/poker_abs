import { useEffect, useMemo, useState } from 'react';
import { PokerHeadsUpTable } from './PokerHeadsUpTable';
import { MultiSeatPokerTable } from './PokerMultiSeatTable';
import {
    cardSignature,
    isMultiSeatLayout,
    visibleCommunityCards,
} from '@/features/poker/utils/tableState';

export default function PokerTable({ state }) {
    const community = visibleCommunityCards(state);
    const playerHandSignature = useMemo(
        () => cardSignature(state.playerCards ?? []),
        [state.playerCards],
    );
    const [playerCardsRevealed, setPlayerCardsRevealed] = useState(false);

    useEffect(() => {
        setPlayerCardsRevealed(false);
    }, [playerHandSignature]);

    if (isMultiSeatLayout(state)) {
        return (
            <MultiSeatPokerTable
                state={state}
                community={community}
                playerCardsRevealed={playerCardsRevealed}
                setPlayerCardsRevealed={setPlayerCardsRevealed}
            />
        );
    }

    return (
        <PokerHeadsUpTable
            state={state}
            community={community}
            playerCardsRevealed={playerCardsRevealed}
            setPlayerCardsRevealed={setPlayerCardsRevealed}
        />
    );
}
