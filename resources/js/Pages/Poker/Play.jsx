import React, { useState } from 'react';
import axios from 'axios';
import HandConclusionBanner from '../../Components/Poker/HandConclusionBanner';
import LastActionAlert from '../../Components/Poker/LastActionAlert';
import PokerActionHistory from '../../Components/Poker/PokerActionHistory';
import PokerActionPanel from '../../Components/Poker/PokerActionPanel';
import PokerHeader from '../../Components/Poker/PokerHeader';
import PokerStreetProgress from '../../Components/Poker/PokerStreetProgress';
import PokerTable from '../../Components/Poker/PokerTable';
import PokerTableStatus from '../../Components/Poker/PokerTableStatus';

function buildInitialState(hand) {
    return {
        street: 'pre_flop',
        streetLabel: 'Pré-flop',
        pot: 30,
        playerStack: 1000,
        opponentStack: 1000,
        currentBet: 20,
        playerStreetBet: 0,
        opponentStreetBet: 0,
        amountToCall: 20,
        minimumRaise: 10,
        lastAction: null,
        actionHistory: [],
        conclusion: null,
        isFinished: false,
        ...hand,
    };
}

export default function Play({ hand }) {
    const [state, setState] = useState(() => buildInitialState(hand));

    const [loading, setLoading] = useState(false);

    async function handleAction(action, raiseAmount = 0) {
        setLoading(true);

        try {
            const response = await axios.post('/poker/actions', {
                state,
                action,
                raiseAmount,
            });

            setState(response.data.state);
        } finally {
            setLoading(false);
        }
    }

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <PokerHeader />

                <PokerTableStatus state={state} />

                <PokerStreetProgress currentStreet={state.street} />

                <HandConclusionBanner conclusion={state.conclusion} />

                <LastActionAlert action={state.lastAction} />

                <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
                    <PokerTable state={state} />
                    <PokerActionHistory history={state.actionHistory} />
                </div>

                <PokerActionPanel
                    disabled={loading || state.isFinished}
                    currentBet={state.currentBet}
                    amountToCall={state.amountToCall}
                    minimumRaise={state.minimumRaise}
                    onAction={handleAction}
                />

                {state.isFinished && (
                    <a
                        href="/poker?new=1"
                        className="inline-flex w-fit rounded-xl bg-white px-5 py-3 font-bold text-slate-950 transition hover:bg-emerald-100"
                    >
                        Nova mão
                    </a>
                )}
            </div>
        </main>
    );
}
