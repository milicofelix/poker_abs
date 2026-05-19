import React, { useCallback, useState } from 'react';
import axios from 'axios';
import HandConclusionBanner from '../../Components/Poker/HandConclusionBanner';
import LastActionAlert from '../../Components/Poker/LastActionAlert';
import PokerActionHistory from '../../Components/Poker/PokerActionHistory';
import PokerActionPanel from '../../Components/Poker/PokerActionPanel';
import PokerBotThinkingIndicator from '../../Components/Poker/PokerBotThinkingIndicator';
import PokerFinalReviewChecklist from '../../Components/Poker/PokerFinalReviewChecklist';
import PokerHandRankCheatSheet from '../../Components/Poker/PokerHandRankCheatSheet';
import PokerInterfaceStateBanner from '../../Components/Poker/PokerInterfaceStateBanner';
import PokerHeader from '../../Components/Poker/PokerHeader';
import PokerSoundToggle from '../../Components/Poker/PokerSoundToggle';
import PokerStateFeedbackPanel from '../../Components/Poker/PokerStateFeedbackPanel';
import PokerMotionUxPanel from '../../Components/Poker/PokerMotionUxPanel';
import PokerResponsiveUxPanel from '../../Components/Poker/PokerResponsiveUxPanel';
import PokerFinalPolishPanel from '../../Components/Poker/PokerFinalPolishPanel';
import PokerPhaseTenAuditPanel from '../../Components/Poker/PokerPhaseTenAuditPanel';
import PokerMultiSeatIntegrationPanel from '../../Components/Poker/PokerMultiSeatIntegrationPanel';
import PokerStreetProgress from '../../Components/Poker/PokerStreetProgress';
import PokerTable from '../../Components/Poker/PokerTable';
import PokerTableStatus from '../../Components/Poker/PokerTableStatus';
import PokerTurnTimer from '../../Components/Poker/PokerTurnTimer';
import PokerVisualAuditPanel from '../../Components/Poker/PokerVisualAuditPanel';
import PokerRealtimeStatus from '../../Components/Poker/PokerRealtimeStatus';
import PokerRealPlayersPanel from '../../Components/Poker/PokerRealPlayersPanel';
import PokerPresenceMiniPanel from '../../Components/Poker/PokerPresenceMiniPanel';
import usePokerSoundEffects from '../../hooks/usePokerSoundEffects';
import usePokerTableRealtime from '../../hooks/usePokerTableRealtime';
import usePokerTableRehydration from '../../hooks/usePokerTableRehydration';
import usePokerTurnTimer from '../../hooks/usePokerTurnTimer';
import usePokerTurnTimeout from '../../hooks/usePokerTurnTimeout';



function tableHasBot(players = [], state = null) {
    return players.some((player) => Boolean(player?.isBot))
        || Boolean(state?.lastAction?.isBot)
        || Boolean(state?.botDecision?.processed);
}


function hasStatusFailure(status = null) {
    const label = String(status?.label ?? '').toLowerCase();

    return label.includes('falha') || label.includes('erro') || label.includes('indisponível');
}

function realPlayerCount(players = []) {
    return players.filter((player) => !player?.isBot).length;
}

function resolveInterfaceState({
    actionError,
    actionInFlight,
    loading,
    state,
    table,
    realPlayers,
    rehydrationStatus,
    realtimeStatus,
    timeoutStatus,
    botThinking,
}) {
    if (actionError) {
        return {
            visible: true,
            tone: 'error',
            title: 'Ação não executada',
            description: actionError,
            badge: 'erro',
        };
    }

    if (timeoutStatus?.enabled && hasStatusFailure(timeoutStatus)) {
        return {
            visible: true,
            tone: 'warning',
            title: 'Timeout automático precisa de atenção',
            description: timeoutStatus.label,
            badge: 'timer',
        };
    }

    if (rehydrationStatus?.enabled && hasStatusFailure(rehydrationStatus)) {
        return {
            visible: true,
            tone: 'warning',
            title: 'Reconectando estado da mesa',
            description: 'Estamos tentando sincronizar a mão novamente com o servidor.',
            badge: 'sync',
        };
    }

    if (rehydrationStatus?.enabled && rehydrationStatus?.loading) {
        return {
            visible: true,
            tone: 'info',
            title: 'Sincronizando mesa',
            description: 'Atualizando cartas, pote, stacks e vez atual.',
            badge: 'loading',
        };
    }

    if (loading && actionInFlight) {
        return {
            visible: true,
            tone: botThinking ? 'warning' : 'info',
            title: botThinking ? 'Oponente pensando' : 'Processando jogada',
            description: botThinking
                ? 'A jogada do bot está sendo processada com delay humanizado.'
                : 'Aguarde enquanto sua ação é confirmada na mesa.',
            badge: actionInFlight,
        };
    }

    if (state?.isWaitingForPlayers) {
        return {
            visible: true,
            tone: 'neutral',
            title: 'Mesa aguardando jogadores',
            description: state?.waitingForPlayers?.message ?? 'Sente em um assento e adicione um bot ou convide outro jogador para iniciar.',
            badge: `${state?.waitingForPlayers?.playersSeated ?? realPlayerCount(realPlayers)}/2`,
        };
    }

    if (state?.isFinished) {
        return {
            visible: true,
            tone: 'success',
            title: 'Mão finalizada',
            description: state?.conclusion?.message ?? 'Confira o resultado e inicie uma nova mão quando estiver pronto.',
            badge: 'showdown',
        };
    }

    if (table && !tableHasBot(realPlayers, state) && realPlayerCount(realPlayers) < 2) {
        return {
            visible: true,
            tone: 'neutral',
            title: 'Mesa aguardando jogador',
            description: 'Convide outro jogador, escolha um assento ou adicione um bot para continuar a partida.',
            badge: `${realPlayerCount(realPlayers)}/2`,
        };
    }

    if (state && !state.canAct) {
        return {
            visible: true,
            tone: 'neutral',
            title: 'Aguardando sua vez',
            description: state?.currentTurn?.message ?? 'A mesa está aguardando a próxima ação válida.',
            badge: state?.currentTurn?.actorLabel ?? 'turno',
        };
    }

    if (!table?.isLocalMode && realtimeStatus && !realtimeStatus.enabled) {
        return {
            visible: true,
            tone: 'warning',
            title: 'Tempo real em standby',
            description: 'A mesa continua funcionando, mas talvez seja necessário atualizar se outra pessoa agir.',
            badge: 'reverb',
        };
    }

    return {
        visible: true,
        tone: 'success',
        title: 'Sua vez de agir',
        description: 'Escolha uma ação no painel da mesa.',
        badge: 'ativo',
    };
}

function botThinkingLabel(players = []) {
    const bot = players.find((player) => Boolean(player?.isBot));

    if (!bot) {
        return 'Oponente pensando...';
    }

    return `${bot.nickname ?? bot.name ?? 'Bot'} pensando...`;
}

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


function canonicalActorLabel(canonicalActor, role, players = []) {
    if (canonicalActor === 'tie') {
        return 'Empate';
    }

    if (role !== 'spectator' && visibleActorForRole(canonicalActor, role) === 'player') {
        return 'Você';
    }

    const seatNumber = canonicalActor === 'opponent' ? 2 : 1;
    const canonicalPlayer = players.find((player) => Number(player?.seatNumber) === seatNumber);

    return canonicalPlayer?.nickname
        ?? canonicalPlayer?.name
        ?? canonicalPlayer?.displayName
        ?? (canonicalActor === 'opponent' ? 'Oponente' : 'Jogador');
}

function visibleActorForRole(canonicalActor, role) {
    if (canonicalActor === 'tie') {
        return 'tie';
    }

    if (role === 'opponent') {
        return canonicalActor === 'opponent' ? 'player' : 'opponent';
    }

    return canonicalActor === 'opponent' ? 'opponent' : 'player';
}

function personalizeConclusionMessage(message = '', role, players = []) {
    if (!message || role !== 'opponent') {
        return message;
    }

    const canonicalPlayerLabel = canonicalActorLabel('player', role, players);
    const canonicalOpponentLabel = canonicalActorLabel('opponent', role, players);

    return String(message)
        .replaceAll('Você', '__CANONICAL_PLAYER__')
        .replaceAll('Oponente', '__CANONICAL_OPPONENT__')
        .replaceAll('__CANONICAL_PLAYER__', canonicalPlayerLabel)
        .replaceAll('__CANONICAL_OPPONENT__', canonicalOpponentLabel);
}

function personalizeConclusionForRole(conclusion, role, players = []) {
    if (!conclusion?.winner || conclusion.winner.player === 'tie') {
        return conclusion
            ? {
                ...conclusion,
                message: personalizeConclusionMessage(conclusion.message, role, players),
            }
            : conclusion;
    }

    const canonicalWinner = conclusion.winner.canonicalPlayer ?? conclusion.winner.player;

    return {
        ...conclusion,
        message: personalizeConclusionMessage(conclusion.message, role, players),
        winner: {
            ...conclusion.winner,
            canonicalPlayer: canonicalWinner,
            player: visibleActorForRole(canonicalWinner, role),
            label: canonicalActorLabel(canonicalWinner, role, players),
        },
    };
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
            conclusion: personalizeConclusionForRole(nextState.conclusion, role, players),
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
        conclusion: personalizeConclusionForRole(nextState.conclusion, role, players),
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

function isTerminalPokerState(nextState = null) {
    return Boolean(nextState?.isFinished)
        || Boolean(nextState?.conclusion)
        || String(nextState?.street ?? '').toLowerCase() === 'showdown';
}

function normalizePlayableState(nextState = null) {
    if (!nextState) {
        return nextState;
    }

    if (!isTerminalPokerState(nextState)) {
        return nextState;
    }

    return {
        ...nextState,
        isFinished: true,
        canAct: false,
        canCheck: false,
        canCall: false,
        canRaise: false,
        amountToCall: 0,
        turnTimer: null,
        currentTurn: {
            ...(nextState.currentTurn ?? {}),
            actorLabel: 'Mão finalizada',
            message: 'Showdown concluído. Inicie uma nova mão para liberar novas ações.',
        },
    };
}

function buildInitialState(hand) {
    return normalizePlayableState({
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
    });
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
    const [stateContracts, setStateContracts] = useState(table?.stateContracts ?? null);
    const [joinMessage, setJoinMessage] = useState(null);
    const [handRankHelpOpen, setHandRankHelpOpen] = useState(false);
    const soundEffects = usePokerSoundEffects(state);

    const handlePersonalizedState = useCallback((nextState, payload = null) => {
        setState(normalizePlayableState(nextState));

        if (Array.isArray(payload?.players)) {
            setRealPlayers(payload.players);
        }

        if (Array.isArray(payload?.seatSlots)) {
            setSeatSlots(payload.seatSlots);
        }

        if (payload?.stateContracts) {
            setStateContracts(payload.stateContracts);
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
        {
            autoProcessBotTurns: Boolean(
                state?.botVsBotSimulation
                || state?.turnTimer?.autoProcessCurrentBot
                || state?.multiSeat?.autoProcessCurrentBot
            ),
        },
    );

    const actionPanelLocked = isTerminalPokerState(state) || loading || !state?.canAct;
    const botThinking = loading && Boolean(actionInFlight) && tableHasBot(realPlayers, state);
    const currentBotThinkingLabel = botThinkingLabel(realPlayers);
    const interfaceState = resolveInterfaceState({
        actionError,
        actionInFlight,
        loading,
        state,
        table,
        realPlayers,
        rehydrationStatus,
        realtimeStatus,
        timeoutStatus,
        botThinking,
    });


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
                setState(normalizePlayableState(response.data.state));
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
                setState(normalizePlayableState(response.data.state));
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

    async function handleAddBot(profile, difficulty = 'normal', options = {}) {
        if (!table?.botUrl) {
            return;
        }

        setAddingBot(true);
        setJoinMessage(null);

        try {
            const response = await axios.post(table.botUrl, {
                profile,
                difficulty,
                replace_bot: Boolean(options?.replaceBot),
            });

            setRealPlayers(response.data.players ?? []);
            setSeatSlots(response.data.seatSlots ?? []);
            setJoinMessage(response.data.message ?? 'Bot adicionado à mesa.');

            if (response.data.state) {
                setState(normalizePlayableState(response.data.state));
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
        if (!table?.newHandActionUrl && table?.newHandUrl) {
            window.location.assign(table.newHandUrl);
            return;
        }

        if (!table?.newHandActionUrl) {
            return;
        }

        setStartingNewHand(true);
        setJoinMessage(null);

        try {
            const response = await axios.post(table.newHandActionUrl);

            if (response.data.state) {
                setState(normalizePlayableState(response.data.state));
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
        if (isTerminalPokerState(state) || !state?.canAct) {
            setActionError(null);
            setState(normalizePlayableState(state));
            return;
        }

        setLoading(true);
        setActionInFlight(action);
        setActionError(null);

        try {
            const response = await axios.post(table?.actionUrl ?? '/poker/actions', {
                state,
                action,
                raiseAmount,
            });

            setState(normalizePlayableState(response.data.state));
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

            <div className="relative z-10 mx-auto flex max-w-[1500px] flex-col gap-3 pb-36 sm:pb-32 lg:pb-6">
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

                {table?.isPrivate && table?.inviteCode && (
                    <div className="rounded-2xl border border-violet-200/25 bg-violet-300/10 px-4 py-3 text-sm text-violet-50 shadow-xl shadow-black/35">
                        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p className="text-xs font-black uppercase tracking-[0.24em] text-violet-200">Mesa privada</p>
                                <p className="mt-1 font-bold">Código: <span className="rounded-lg bg-slate-950/60 px-2 py-1 font-black tracking-[0.18em] text-white">{table.inviteCode}</span></p>
                                {table.inviteUrl && <p className="mt-1 text-xs text-violet-100/80">Compartilhe o link do convite pelo botão abaixo.</p>}
                            </div>
                            {table.inviteUrl && (
                                <button
                                    type="button"
                                    onClick={() => navigator.clipboard?.writeText(window.location.origin + table.inviteUrl)}
                                    className="rounded-2xl bg-violet-200 px-4 py-2 font-black text-violet-950 transition hover:bg-violet-100"
                                >
                                    Copiar convite
                                </button>
                            )}
                        </div>
                    </div>
                )}

                {joinMessage && (
                    <div className="rounded-2xl border border-amber-200/20 bg-amber-300/10 px-4 py-3 text-sm font-bold text-amber-50 shadow-xl shadow-black/35">
                        {joinMessage}
                    </div>
                )}

                {table?.isLocalMode && (
                    <div className="rounded-2xl border border-cyan-200/20 bg-cyan-300/10 px-4 py-3 text-sm font-bold text-cyan-50 shadow-xl shadow-black/35">
                        Mesa local clássica: mesma base visual da mesa do lobby, porém com engine local. Assentos, presença em tempo real, bots trocáveis e timeout automático continuam concentrados no Lobby multiplayer.
                    </div>
                )}

                <PokerInterfaceStateBanner state={interfaceState} />
                <PokerStateFeedbackPanel state={state} feedback={table?.stateFeedback} />
                <HandConclusionBanner conclusion={state.conclusion} />
                <LastActionAlert action={state.lastAction} />
                <PokerBotThinkingIndicator active={botThinking} label={currentBotThinkingLabel} />

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

                        {!table?.isLocalMode && (
                            <div className="xl:hidden">
                                <PokerPresenceMiniPanel
                                    players={realPlayers}
                                    currentUserId={table?.currentUserId}
                                    currentTurnLabel={state?.currentTurn?.actorLabel}
                                    maxPlayers={table?.maxPlayers}
                                    realtimeStatus={realtimeStatus}
                                />
                            </div>
                        )}

                        <div className="xl:hidden">
                            <div className="poker-safe-sticky-actions sticky bottom-2 z-40 rounded-[1.35rem] border border-amber-200/20 bg-slate-950/95 p-2 shadow-2xl shadow-black/70 backdrop-blur-md supports-[padding:max(0px)]:mb-[max(0.5rem,env(safe-area-inset-bottom))]">
                                <PokerActionPanel
                                    disabled={actionPanelLocked}
                                    currentBet={state.currentBet}
                                    amountToCall={state.amountToCall}
                                    minimumRaise={state.minimumRaise}
                                    minimumRaiseTo={state.minimumRaiseTo}
                                    maximumRaiseTo={state.maximumRaiseTo}
                                    canCheck={state.canCheck}
                                    canCall={state.canCall}
                                    canRaise={state.canRaise}
                                    actingAction={actionInFlight}
                                    thinking={botThinking}
                                    thinkingLabel={currentBotThinkingLabel}
                                    errorMessage={actionError}
                                    actionUx={table?.actionButtonUx}
                                    onAction={handleAction}
                                />
                            </div>
                        </div>

                        <PokerActionHistory history={state.actionHistory} compact />

                        <PokerFinalReviewChecklist table={table} />
                        <PokerVisualAuditPanel audit={table?.visualAudit} />
                        <PokerMotionUxPanel motion={table?.motionUx} />
                        <PokerResponsiveUxPanel responsive={table?.responsiveUx} />
                        <PokerFinalPolishPanel polish={table?.finalPolish} />
                        <PokerPhaseTenAuditPanel audit={table?.phaseTenAudit} />
                        <PokerMultiSeatIntegrationPanel
                            contracts={stateContracts}
                            players={realPlayers}
                            maxPlayers={table?.maxPlayers}
                        />

                        <details className="rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-3 shadow-xl shadow-black/35 backdrop-blur">
                            <summary className="cursor-pointer select-none text-xs font-black uppercase tracking-[0.24em] text-emerald-100">
                                Detalhes da mesa
                            </summary>

                            {!table?.isLocalMode ? (
                                <div className="mt-3 grid gap-3 lg:grid-cols-3">
                                    <PokerRealtimeStatus status={realtimeStatus} title="Tempo real" />
                                    <PokerRealtimeStatus status={rehydrationStatus} title="Reconexão" />
                                    <PokerRealtimeStatus status={timeoutStatus} title="Timeout automático" />
                                </div>
                            ) : (
                                <div className="mt-3 rounded-3xl border border-emerald-200/20 bg-emerald-300/10 p-5 text-sm text-emerald-50">
                                    <p className="font-black uppercase tracking-[0.2em] text-emerald-200">Fluxo local estável</p>
                                    <p className="mt-2 text-emerald-50/90">
                                        As ações locais persistem na sessão e no histórico sem depender de Reverb, reidratação periódica ou timeout automático.
                                        Por isso, esses indicadores ficam reservados para mesas do lobby.
                                    </p>
                                </div>
                            )}

                            {!table?.isLocalMode ? (
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
                            ) : (
                                <div className="mt-3 rounded-3xl border border-cyan-200/20 bg-cyan-300/10 p-5 text-sm text-cyan-50">
                                    <p className="font-black uppercase tracking-[0.2em] text-cyan-200">Modo local</p>
                                    <p className="mt-2 text-cyan-50/90">
                                        Esta mesa usa a engine local e mantém a compatibilidade com histórico, ranking e estatísticas.
                                        Recursos de lobby, presença, assentos, bots trocáveis e timeout automático ficam concentrados nas mesas multiplayer.
                                    </p>
                                    <a
                                        href={table?.lobbyUrl ?? '/poker/lobby'}
                                        className="mt-4 inline-flex rounded-2xl bg-cyan-200 px-4 py-2 font-black text-cyan-950 transition hover:bg-cyan-100"
                                    >
                                        Ir para o Lobby multiplayer
                                    </a>
                                </div>
                            )}
                        </details>
                    </section>

                    <aside className="space-y-3 xl:sticky xl:top-24 xl:max-h-[calc(100vh-7rem)] xl:overflow-y-auto xl:pr-1">
                        {!table?.isLocalMode && (
                            <PokerPresenceMiniPanel
                                players={realPlayers}
                                currentUserId={table?.currentUserId}
                                currentTurnLabel={state?.currentTurn?.actorLabel}
                                maxPlayers={table?.maxPlayers}
                                realtimeStatus={realtimeStatus}
                            />
                        )}

                        <PokerTableStatus state={state} compact />
                        <PokerTurnTimer timer={turnTimer} compact />

                        <div className="hidden xl:block">
                            <PokerActionPanel
                                disabled={actionPanelLocked}
                                currentBet={state.currentBet}
                                amountToCall={state.amountToCall}
                                minimumRaise={state.minimumRaise}
                                minimumRaiseTo={state.minimumRaiseTo}
                                maximumRaiseTo={state.maximumRaiseTo}
                                canCheck={state.canCheck}
                                canCall={state.canCall}
                                canRaise={state.canRaise}
                                actingAction={actionInFlight}
                                thinking={botThinking}
                                thinkingLabel={currentBotThinkingLabel}
                                errorMessage={actionError}
                                actionUx={table?.actionButtonUx}
                                onAction={handleAction}
                            />
                        </div>

                        <PokerStreetProgress currentStreet={state.street} compact />

                        {state.isFinished && (table?.newHandActionUrl || table?.newHandUrl) && (
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
