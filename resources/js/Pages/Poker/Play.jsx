import React, { useCallback, useState } from 'react';
import axios from 'axios';
import HandConclusionBanner from '../../Components/Poker/HandConclusionBanner';
import LastActionAlert from '../../Components/Poker/LastActionAlert';
import PokerActionHistory from '../../Components/Poker/PokerActionHistory';
import PokerActionPanel from '../../Components/Poker/PokerActionPanel';
import PokerHandRankCheatSheet from '../../Components/Poker/PokerHandRankCheatSheet';
import PokerHeader from '../../Components/Poker/PokerHeader';
import PokerSoundToggle from '../../Components/Poker/PokerSoundToggle';
import PokerStreetProgress from '../../Components/Poker/PokerStreetProgress';
import PokerTable from '../../Components/Poker/PokerTable';
import PokerTableStatus from '../../Components/Poker/PokerTableStatus';
import PokerTurnTimer from '../../Components/Poker/PokerTurnTimer';
import PokerRealtimeStatus from '../../Components/Poker/PokerRealtimeStatus';
import PokerRealPlayersPanel from '../../Components/Poker/PokerRealPlayersPanel';
import usePokerSoundEffects from '../../hooks/usePokerSoundEffects';
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
    const [actionInFlight, setActionInFlight] = useState(null);
    const [actionError, setActionError] = useState(null);
    const [joiningTable, setJoiningTable] = useState(false);
    const [seatingTable, setSeatingTable] = useState(false);
    const [leavingTable, setLeavingTable] = useState(false);
    const [addingBot, setAddingBot] = useState(false);
    const [startingNewHand, setStartingNewHand] = useState(false);
    const [realPlayers, setRealPlayers] = useState(table?.realPlayers ?? []);
    const [seatSlots, setSeatSlots] = useState(table?.seatSlots ?? []);
    const [joinMessage, setJoinMessage] = useState(null);
    const [handRankHelpOpen, setHandRankHelpOpen] = useState(false);
    const soundEffects = usePokerSoundEffects(state);

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

    const handleRealtimeStateNotification = useCallback(() => {
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

    async function handleAddBot(profile, difficulty = 'normal') {
        if (!table?.botUrl) {
            return;
        }

        setAddingBot(true);
        setJoinMessage(null);

        try {
            const response = await axios.post(table.botUrl, {
                profile,
                difficulty,
            });

            setRealPlayers(response.data.players ?? []);
            setSeatSlots(response.data.seatSlots ?? []);
            setJoinMessage(response.data.message ?? 'Bot adicionado à mesa.');

            if (response.data.state) {
                setState(response.data.state);
            }

            rehydrationStatus.rehydrate();
        } catch (error) {
            setJoinMessage(
                error?.response?.data?.message
                    ?? 'Não foi possível adicionar o bot nesta mesa.',
            );
        } finally {
            setAddingBot(false);
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
        setActionInFlight(action);
        setActionError(null);

        try {
            const response = await axios.post(table?.actionUrl ?? '/poker/actions', {
                state,
                action,
                raiseAmount,
            });

            setState(response.data.state);
            rehydrationStatus.rehydrate();
        } catch (error) {
            setActionError(
                error?.response?.data?.message
                    ?? 'Não foi possível executar esta ação agora. Atualize a mesa e tente novamente.',
            );
        } finally {
            setLoading(false);
            setActionInFlight(null);
        }
    }

    return (
        <main className="min-h-screen overflow-x-hidden bg-[radial-gradient(circle_at_top_left,rgba(16,185,129,0.18),transparent_35%),linear-gradient(135deg,#020617,#031b16_45%,#020617)] px-2 py-2 text-white sm:px-4 sm:py-3">
            <div className="pointer-events-none fixed inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(251,191,36,0.08),transparent_26%),radial-gradient(circle_at_80%_5%,rgba(16,185,129,0.12),transparent_28%)]" />

            <div className="relative z-10 mx-auto flex max-w-[1500px] flex-col gap-3 pb-28 lg:pb-6">
                <PokerHeader
                    compact
                    table={table}
                    rightSlot={(
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => setHandRankHelpOpen(true)}
                                className="rounded-2xl border border-amber-200/20 bg-amber-300/10 px-3 py-2 text-xs font-black uppercase tracking-[0.16em] text-amber-100 transition hover:bg-amber-300/20 sm:px-4"
                                title="Ver hierarquia das mãos"
                            >
                                <span className="hidden sm:inline">Ranking das mãos</span>
                                <span className="sm:hidden">Mãos</span>
                            </button>

                            <PokerSoundToggle
                                enabled={soundEffects.enabled}
                                onToggle={soundEffects.toggleEnabled}
                            />
                        </div>
                    )}
                />

                <PokerHandRankCheatSheet
                    open={handRankHelpOpen}
                    onClose={() => setHandRankHelpOpen(false)}
                />

                {(joinMessage || actionError) && (
                    <div className="rounded-2xl border border-amber-200/20 bg-amber-300/10 px-4 py-3 text-sm font-bold text-amber-50 shadow-xl shadow-black/35">
                        {actionError ?? joinMessage}
                    </div>
                )}

                <HandConclusionBanner conclusion={state.conclusion} />
                <LastActionAlert action={state.lastAction} />

                <div className="grid gap-3 xl:grid-cols-[minmax(0,1fr)_360px] xl:items-start 2xl:grid-cols-[minmax(0,1fr)_390px]">
                    <section className="min-w-0 space-y-3">
                        <div className="grid grid-cols-2 gap-2 md:grid-cols-4 xl:hidden">
                            <div className="rounded-2xl border border-amber-200/25 bg-amber-300/10 px-3 py-2 shadow-xl shadow-black/30">
                                <span className="block text-[0.58rem] font-black uppercase tracking-[0.18em] text-amber-200">Street</span>
                                <strong className="mt-1 block truncate text-base font-black text-white">{state.streetLabel}</strong>
                            </div>
                            <div className="rounded-2xl border border-amber-200/25 bg-amber-300/10 px-3 py-2 shadow-xl shadow-black/30">
                                <span className="block text-[0.58rem] font-black uppercase tracking-[0.18em] text-amber-200">Pote</span>
                                <strong className="mt-1 block truncate text-base font-black text-white">{state.pot}</strong>
                            </div>
                            <div className="rounded-2xl border border-white/10 bg-white/[0.06] px-3 py-2 shadow-xl shadow-black/30">
                                <span className="block text-[0.58rem] font-black uppercase tracking-[0.18em] text-emerald-200">Vez</span>
                                <strong className="mt-1 block truncate text-base font-black text-white">{state.currentTurn?.actorLabel ?? 'Jogador'}</strong>
                            </div>
                            <div className="rounded-2xl border border-white/10 bg-white/[0.06] px-3 py-2 shadow-xl shadow-black/30">
                                <span className="block text-[0.58rem] font-black uppercase tracking-[0.18em] text-emerald-200">Timer</span>
                                <strong className="mt-1 block truncate text-base font-black text-white">{turnTimer ? `${turnTimer.secondsRemaining}s` : '-'}</strong>
                            </div>
                        </div>

                        <PokerTable state={state} />

                        <div className="xl:hidden">
                            <div className="sticky bottom-2 z-40 rounded-[1.35rem] border border-amber-200/20 bg-slate-950/95 p-2 shadow-2xl shadow-black/70 backdrop-blur-md supports-[padding:max(0px)]:mb-[max(0.5rem,env(safe-area-inset-bottom))]">
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
                                    actingAction={actionInFlight}
                                    errorMessage={actionError}
                                    onAction={handleAction}
                                />
                            </div>
                        </div>

                        <PokerActionHistory history={state.actionHistory} compact />

                        <details className="rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-3 shadow-xl shadow-black/35 backdrop-blur">
                            <summary className="cursor-pointer select-none text-xs font-black uppercase tracking-[0.24em] text-emerald-100">
                                Detalhes da mesa
                            </summary>

                            <div className="mt-3 grid gap-3 lg:grid-cols-3">
                                <PokerRealtimeStatus status={realtimeStatus} title="Tempo real" />
                                <PokerRealtimeStatus status={rehydrationStatus} title="Reconexão" />
                                <PokerRealtimeStatus status={timeoutStatus} title="Timeout automático" />
                            </div>

                            <div className="mt-3">
                                <PokerRealPlayersPanel
                                    players={realPlayers}
                                    seatSlots={seatSlots}
                                    currentUserId={table?.currentUserId}
                                    maxPlayers={table?.maxPlayers}
                                    loading={joiningTable || seatingTable || leavingTable || addingBot}
                                    joining={joiningTable}
                                    seating={seatingTable}
                                    leaving={leavingTable}
                                    addingBot={addingBot}
                                    message={joinMessage}
                                    onJoin={table?.joinUrl ? handleJoinTable : null}
                                    onSeat={table?.seatUrl ? handleSeatTable : null}
                                    onLeave={table?.leaveUrl ? handleLeaveTable : null}
                                    onAddBot={table?.botUrl ? handleAddBot : null}
                                    botProfiles={table?.botProfiles ?? []}
                                    botDifficulties={table?.botDifficulties ?? []}
                                    botDifficultyOptions={table?.botDifficultyOptions ?? []}
                                />
                            </div>
                        </details>
                    </section>

                    <aside className="space-y-3 xl:sticky xl:top-24 xl:max-h-[calc(100vh-7rem)] xl:overflow-y-auto xl:pr-1">
                        <PokerTableStatus state={state} compact />
                        <PokerTurnTimer timer={turnTimer} compact />

                        <div className="hidden xl:block">
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
                                actingAction={actionInFlight}
                                errorMessage={actionError}
                                onAction={handleAction}
                            />
                        </div>

                        <PokerStreetProgress currentStreet={state.street} compact />

                        {state.isFinished && table?.newHandActionUrl && (
                            <button
                                type="button"
                                onClick={handleStartNewHand}
                                disabled={startingNewHand}
                                className="w-full rounded-2xl bg-amber-300 px-6 py-3 font-black text-amber-950 shadow-xl transition hover:bg-amber-200 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {startingNewHand ? 'Iniciando...' : 'Iniciar nova mão'}
                            </button>
                        )}
                    </aside>
                </div>
            </div>
        </main>
    );
}
