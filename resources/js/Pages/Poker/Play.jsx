import React, { useCallback, useEffect, useRef, useState } from 'react';
import axios from 'axios';
import HandConclusionBanner from '../../Components/Poker/HandConclusionBanner';
import LastActionAlert from '../../Components/Poker/LastActionAlert';
import PokerActionHistory from '../../Components/Poker/PokerActionHistory';
import PokerActionPanel from '../../Components/Poker/PokerActionPanel';
import PokerHeader from '../../Components/Poker/PokerHeader';
import PokerStreetProgress from '../../Components/Poker/PokerStreetProgress';
import PokerTable from '../../Components/Poker/PokerTable';
import PokerTableStatus from '../../Components/Poker/PokerTableStatus';
import PokerTurnTimer from '../../Components/Poker/PokerTurnTimer';
import PokerRealtimeStatus from '../../Components/Poker/PokerRealtimeStatus';
import PokerRealPlayersPanel from '../../Components/Poker/PokerRealPlayersPanel';
import usePokerTableRealtime from '../../hooks/usePokerTableRealtime';
import usePokerTableRehydration from '../../hooks/usePokerTableRehydration';
import usePokerTurnTimer from '../../hooks/usePokerTurnTimer';
import usePokerTurnTimeout from '../../hooks/usePokerTurnTimeout';


function resolveCurrentPlayerRole(players = [], currentUserId = null) {
    if (!currentUserId) {
        return 'spectator';
    }

    const orderedPlayers = [...players].sort((first, second) => {
        const firstSeat = first.seatNumber ?? 999;
        const secondSeat = second.seatNumber ?? 999;

        if (firstSeat !== secondSeat) {
            return firstSeat - secondSeat;
        }

        return (first.id ?? 0) - (second.id ?? 0);
    });

    const currentPlayerIndex = orderedPlayers.findIndex(
        (player) => Number(player.userId) === Number(currentUserId),
    );

    const currentPlayer = orderedPlayers[currentPlayerIndex];

    if (!currentPlayer) {
        return 'spectator';
    }

    if (Number(currentPlayer.seatNumber) === 2) {
        return 'opponent';
    }

    if (Number(currentPlayer.seatNumber) === 1) {
        return 'player';
    }

    return currentPlayerIndex === 1 ? 'opponent' : 'player';
}

function personalizeCanonicalStateForCurrentUser(nextState, players = [], currentUserId = null) {
    const role = resolveCurrentPlayerRole(players, currentUserId);

    if (nextState?.multiplayerPerspective?.role && nextState?.playersContext) {
        return nextState;
    }

    if (role !== 'opponent') {
        if (!nextState?.isFinished) {
            const { opponentCards, opponentBestHand, ...safeState } = nextState;
            return {
                ...safeState,
                multiplayerPerspective: {
                    ...(safeState.multiplayerPerspective ?? {}),
                    role,
                },
            };
        }

        return {
            ...nextState,
            multiplayerPerspective: {
                ...(nextState.multiplayerPerspective ?? {}),
                role,
            },
        };
    }

    const originalPlayerCards = nextState.playerCards ?? [];
    const originalOpponentCards = nextState.opponentCards ?? [];
    const originalBestHand = nextState.bestHand ?? null;
    const originalOpponentBestHand = nextState.opponentBestHand ?? null;

    const personalized = {
        ...nextState,
        playerCards: originalOpponentCards,
        bestHand: originalOpponentBestHand,
        multiplayerPerspective: {
            ...(nextState.multiplayerPerspective ?? {}),
            role: 'opponent',
        },
    };

    if (nextState.isFinished) {
        personalized.opponentCards = originalPlayerCards;
        personalized.opponentBestHand = originalBestHand;
    } else {
        delete personalized.opponentCards;
        delete personalized.opponentBestHand;
    }

    return personalized;
}

function buildInitialState(hand) {
    return {
        street: 'pre_flop',
        streetLabel: 'Pré-flop',
        pot: 30,
        playerStack: 990,
        opponentStack: 980,
        currentBet: 20,
        playerStreetBet: 10,
        opponentStreetBet: 20,
        amountToCall: 10,
        minimumRaise: 20,
        minimumRaiseTo: 40,
        maximumRaiseTo: 1000,
        smallBlind: 10,
        bigBlind: 20,
        dealerPosition: 1,
        canCheck: false,
        canCall: true,
        canRaise: true,
        bettingSummary: {
            playerCommitted: 10,
            opponentCommitted: 20,
            amountToCall: 10,
            currentBet: 20,
        },
        tableSeats: [],
        lastAction: null,
        actionHistory: [],
        conclusion: null,
        turnTimer: null,
        isFinished: false,
        ...hand,
    };
}

export default function Play({ hand, table = null }) {
    const [state, setState] = useState(() => buildInitialState(hand));

    const [loading, setLoading] = useState(false);
    const [joiningTable, setJoiningTable] = useState(false);
    const [seatingTable, setSeatingTable] = useState(false);
    const [leavingTable, setLeavingTable] = useState(false);
    const [startingNewHand, setStartingNewHand] = useState(false);
    const [realPlayers, setRealPlayers] = useState(table?.realPlayers ?? []);
    const [seatSlots, setSeatSlots] = useState(table?.seatSlots ?? []);
    const [joinMessage, setJoinMessage] = useState(null);
    const realPlayersRef = useRef(realPlayers);
    const currentUserIdRef = useRef(table?.currentUserId ?? null);

    useEffect(() => {
        realPlayersRef.current = realPlayers;
    }, [realPlayers]);

    useEffect(() => {
        currentUserIdRef.current = table?.currentUserId ?? null;
    }, [table?.currentUserId]);

    const handlePersonalizedState = useCallback((nextState, payload = null) => {
        setState(nextState);

        if (Array.isArray(payload?.players)) {
            setRealPlayers(payload.players);
        }

        if (Array.isArray(payload?.seatSlots)) {
            setSeatSlots(payload.seatSlots);
        }
    }, []);

    const rehydrationStatus = usePokerTableRehydration(
        table?.stateUrl,
        handlePersonalizedState,
    );

    const rehydratePokerTable = rehydrationStatus.rehydrate;

    const handleRealtimeStateNotification = useCallback((canonicalState = null) => {
        if (canonicalState) {
            setState(personalizeCanonicalStateForCurrentUser(
                canonicalState,
                realPlayersRef.current,
                currentUserIdRef.current,
            ));
        }

        rehydratePokerTable();
    }, [rehydratePokerTable]);

    const realtimeStatus = usePokerTableRealtime(
        state?.persistence?.tableId,
        handleRealtimeStateNotification,
    );

    const turnTimer = usePokerTurnTimer(state.turnTimer);

    const timeoutStatus = usePokerTurnTimeout(
        table?.timeoutUrl,
        turnTimer,
        handlePersonalizedState,
    );


    async function handleJoinTable() {
        if (!table?.joinUrl) {
            return;
        }

        setJoiningTable(true);
        setJoinMessage(null);

        try {
            const response = await axios.post(table.joinUrl);

            setRealPlayers(response.data.players ?? []);
            setSeatSlots(response.data.seatSlots ?? []);
            setJoinMessage(response.data.message ?? 'Jogador entrou na mesa com sucesso.');
            rehydrationStatus.rehydrate();
        } catch (error) {
            setJoinMessage(
                error?.response?.data?.message
                    ?? 'Não foi possível entrar como jogador real nesta mesa.',
            );
        } finally {
            setJoiningTable(false);
        }
    }

    async function handleSeatTable(seatNumber) {
        if (!table?.seatUrl) {
            return;
        }

        setSeatingTable(true);
        setJoinMessage(null);

        try {
            const response = await axios.post(table.seatUrl, {
                seat_number: seatNumber,
            });

            setRealPlayers(response.data.players ?? []);
            setSeatSlots(response.data.seatSlots ?? []);
            setJoinMessage(response.data.message ?? 'Assento escolhido com sucesso.');

            if (response.data.state) {
                setState(response.data.state);
            }

            rehydrationStatus.rehydrate();
        } catch (error) {
            setJoinMessage(
                error?.response?.data?.message
                    ?? 'Não foi possível escolher este assento.',
            );
        } finally {
            setSeatingTable(false);
        }
    }

    async function handleLeaveTable() {
        if (!table?.leaveUrl) {
            return;
        }

        setLeavingTable(true);
        setJoinMessage(null);

        try {
            const response = await axios.post(table.leaveUrl);

            setRealPlayers(response.data.players ?? []);
            setSeatSlots(response.data.seatSlots ?? []);
            setJoinMessage(response.data.message ?? 'Você saiu da mesa.');

            if (response.data.state) {
                setState(response.data.state);
            }

            rehydrationStatus.rehydrate();
        } catch (error) {
            setJoinMessage(
                error?.response?.data?.message
                    ?? 'Não foi possível sair da mesa.',
            );
        } finally {
            setLeavingTable(false);
        }
    }

    async function handleStartNewHand() {
        if (!table?.newHandActionUrl) {
            return;
        }

        setStartingNewHand(true);
        setJoinMessage(null);

        try {
            const response = await axios.post(table.newHandActionUrl);

            if (response.data.state) {
                setState(response.data.state);
            }

            setRealPlayers(response.data.players ?? []);
            setSeatSlots(response.data.seatSlots ?? []);
            setJoinMessage(response.data.message ?? 'Nova mão iniciada.');
            rehydrationStatus.rehydrate();
        } catch (error) {
            setJoinMessage(
                error?.response?.data?.message
                    ?? 'Não foi possível iniciar uma nova mão.',
            );
        } finally {
            setStartingNewHand(false);
        }
    }

    async function handleAction(action, raiseAmount = 0) {
        setLoading(true);

        try {
            const response = await axios.post(table?.actionUrl ?? '/poker/actions', {
                state,
                action,
                raiseAmount,
            });

            setState(response.data.state);
            rehydrationStatus.rehydrate();
        } finally {
            setLoading(false);
        }
    }

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <PokerHeader />

                    <div className="flex flex-wrap gap-2">
                        {table?.lobbyUrl && (
                            <a
                                href={table.lobbyUrl}
                                className="rounded-xl border border-white/15 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20"
                            >
                                Lobby
                            </a>
                        )}

                        {table?.name && (
                            <span className="rounded-xl border border-emerald-300/30 bg-emerald-400/10 px-4 py-2 text-sm font-bold text-emerald-100">
                                {table.name}
                            </span>
                        )}
                    </div>
                </div>

                <div className="grid gap-3 md:grid-cols-3">
                    <PokerRealtimeStatus status={realtimeStatus} title="Tempo real" />
                    <PokerRealtimeStatus status={rehydrationStatus} title="Reconexão" />
                    <PokerRealtimeStatus status={timeoutStatus} title="Timeout automático" />
                </div>

                <PokerRealPlayersPanel
                    players={realPlayers}
                    seatSlots={seatSlots}
                    currentUserId={table?.currentUserId}
                    maxPlayers={table?.maxPlayers}
                    loading={joiningTable || seatingTable || leavingTable}
                    joining={joiningTable}
                    seating={seatingTable}
                    leaving={leavingTable}
                    message={joinMessage}
                    onJoin={table?.joinUrl ? handleJoinTable : null}
                    onSeat={table?.seatUrl ? handleSeatTable : null}
                    onLeave={table?.leaveUrl ? handleLeaveTable : null}
                />

                <PokerTableStatus state={state} />

                <PokerTurnTimer timer={turnTimer} />

                <PokerStreetProgress currentStreet={state.street} />

                <HandConclusionBanner conclusion={state.conclusion} />

                <LastActionAlert action={state.lastAction} />

                <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
                    <PokerTable state={state} />
                    <PokerActionHistory history={state.actionHistory} />
                </div>

                <PokerActionPanel
                    disabled={loading || state.isFinished || !state.canAct}
                    currentBet={state.currentBet}
                    amountToCall={state.amountToCall}
                    minimumRaise={state.minimumRaise}
                    minimumRaiseTo={state.minimumRaiseTo}
                    maximumRaiseTo={state.maximumRaiseTo}
                    canCheck={state.canCheck}
                    canCall={state.canCall}
                    canRaise={state.canRaise}
                    onAction={handleAction}
                />

                {state.isFinished && (
                    table?.newHandActionUrl ? (
                        <button
                            type="button"
                            onClick={handleStartNewHand}
                            disabled={startingNewHand}
                            className="inline-flex w-fit rounded-xl bg-white px-5 py-3 font-bold text-slate-950 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {startingNewHand ? 'Iniciando nova mão...' : 'Nova mão para a mesa'}
                        </button>
                    ) : (
                        <a
                            href={table?.newHandUrl ?? "/poker?new=1"}
                            className="inline-flex w-fit rounded-xl bg-white px-5 py-3 font-bold text-slate-950 transition hover:bg-emerald-100"
                        >
                            Nova mão
                        </a>
                    )
                )}
            </div>
        </main>
    );
}
