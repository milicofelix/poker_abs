import React from 'react';
import CardRow from './CardRow';

function visibleCommunityCards(state) {
    const amountByStreet = {
        pre_flop: 0,
        flop: 3,
        turn: 4,
        river: 5,
        showdown: 5,
    };

    const amount = amountByStreet[state.street] ?? 0;

    return {
        visible: state.communityCards.slice(0, amount),
        hiddenCount: Math.max(0, 5 - amount),
    };
}

function opponentCardsTitle(state) {
    const opponent = state?.playersContext?.opponents?.[0];

    if (opponent?.nickname) {
        return `Cartas de ${opponent.nickname}`;
    }

    return 'Cartas do adversário';
}

export default function PokerTable({ state }) {
    const community = visibleCommunityCards(state);
    const currentName = state?.playersContext?.current?.nickname;

    return (
        <div className="rounded-[2rem] border border-white/10 bg-black/20 p-6 shadow-2xl">
            <div className="flex flex-col gap-8">
                {state.isFinished && state.opponentCards && (
                    <CardRow title={opponentCardsTitle(state)} cards={state.opponentCards} />
                )}

                <CardRow
                    title="Cartas comunitárias"
                    cards={community.visible}
                    hiddenCount={community.hiddenCount}
                />

                <CardRow
                    title={currentName ? `Suas cartas (${currentName})` : 'Suas cartas'}
                    cards={state.playerCards}
                />
            </div>
        </div>
    );
}
