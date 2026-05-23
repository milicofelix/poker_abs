import React, { useEffect, useMemo, useState } from 'react';
import CardRow from './CardRow';
import PlayingCard from './PlayingCard';

function visibleCommunityCards(state) {
    const amountByStreet = {
        pre_flop: 0,
        flop: 3,
        turn: 4,
        river: 5,
        showdown: 5,
    };

    const players = Array.isArray(state?.multiSeat?.players) ? state.multiSeat.players : [];
    const hasAllInPlayer = players.some((player) => Boolean(player?.isAllIn) || Number(player?.stack ?? 0) <= 0);
    const shouldRevealFinishedBoard = Boolean(state?.isFinished)
        && (state?.street === 'showdown' || Boolean(state?.multiSeat?.allInRunoutCompleted) || hasAllInPlayer);

    const amount = shouldRevealFinishedBoard ? 5 : (amountByStreet[state?.street] ?? 0);

    return {
        visible: (state?.communityCards ?? []).slice(0, amount),
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

function currentPlayerTitle(state) {
    const currentName = state?.playersContext?.current?.nickname;

    return currentName ? `Suas cartas (${currentName})` : 'Suas cartas';
}


function cardSignature(cards = []) {
    return cards
        .map((card) => `${card?.rank ?? card?.label ?? ''}-${card?.suit ?? ''}`)
        .join('|');
}

function hiddenPlayerHandTitle(isRevealed) {
    return isRevealed ? 'Ocultar suas cartas' : 'Revelar suas cartas';
}

function hiddenPlayerHandDescription(isRevealed, isFinished) {
    if (isFinished) {
        return 'Mão encerrada — cartas liberadas para conferência.';
    }

    return isRevealed
        ? 'Clique para esconder sua mão novamente.'
        : 'Clique para espiar sua mão quando quiser.';
}

function currentTurnLabel(state) {
    return state?.currentTurn?.actorLabel ?? 'Jogador';
}

function chipAmountParts(value) {
    const amount = Number(value ?? 0);

    if (amount <= 0) {
        return [1, 1, 1];
    }

    return [
        Math.max(1, Math.ceil(amount / 120)),
        Math.max(1, Math.ceil(amount / 220)),
        Math.max(1, Math.ceil(amount / 360)),
    ].slice(0, 3);
}

function dealerAnimationLabel(state) {
    if (state?.isFinished) {
        return 'Showdown finalizado';
    }

    if ((state?.actionHistory ?? []).length > 0) {
        return 'Cartas na mesa';
    }

    return 'Dealer distribuindo';
}

function currentTurnMessage(state) {
    if (state?.isFinished) {
        return 'Mão encerrada';
    }

    return state?.currentTurn?.message ?? 'Aguardando ação da mesa.';
}

function winnerPlayer(state) {
    return state?.conclusion?.winner?.player ?? null;
}

function isWinnerSeat(state, seat) {
    const winner = winnerPlayer(state);

    return state?.isFinished && (winner === seat || winner === 'tie');
}

function isLosingSeat(state, seat) {
    const winner = winnerPlayer(state);

    return state?.isFinished && winner && winner !== 'tie' && winner !== seat;
}

function winnerBadgeLabel(state, seat) {
    const winner = winnerPlayer(state);

    if (!state?.isFinished || !winner) {
        return null;
    }

    if (winner === 'tie') {
        return 'Empate';
    }

    return winner === seat ? 'Vencedor' : 'Derrotado';
}

function seatFrameClasses(state, seat) {
    if (isWinnerSeat(state, seat)) {
        return 'poker-winner-seat border-amber-200/70 bg-amber-300/15 shadow-[0_0_42px_rgba(251,191,36,0.28)]';
    }

    if (isLosingSeat(state, seat)) {
        return 'border-slate-500/20 bg-black/25 opacity-70 grayscale-[0.25]';
    }

    return 'border-white/10 bg-black/20 shadow-inner shadow-black/40';
}

function seatBadgeClasses(state, seat) {
    if (isWinnerSeat(state, seat)) {
        return 'border-amber-100/60 bg-amber-300 text-amber-950 shadow-lg shadow-amber-950/25';
    }

    return 'border-slate-400/30 bg-slate-950/80 text-slate-200';
}


function isMultiSeatLayout(state) {
    if (!Boolean(state?.multiSeat?.enabled) || !Array.isArray(state?.multiSeat?.players)) {
        return false;
    }

    const playersCount = state.multiSeat.players.length;

    return playersCount > 2
        || (playersCount >= 2 && Boolean(state?.tournamentRuntime))
        || (playersCount >= 2 && Boolean(state?.botVsBotSimulation));
}

function multiSeatPlayers(state) {
    return [...(state?.multiSeat?.players ?? [])]
        .filter((player) => player && Number(player.seatNumber ?? 0) > 0)
        .sort((left, right) => Number(left.seatNumber ?? 0) - Number(right.seatNumber ?? 0));
}

function currentUserSeatNumber(state) {
    return Number(state?.multiplayerPerspective?.seatNumber ?? state?.playersContext?.current?.seatNumber ?? 0);
}

function multiSeatCurrentTurnSeat(state) {
    const seat = state?.currentTurn?.seatNumber ?? state?.multiSeat?.currentSeat;

    return seat === null || seat === undefined ? null : Number(seat);
}

function multiSeatSeatPositionNumbers(state) {
    return {
        dealerSeat: Number(state?.multiSeat?.dealerSeat ?? state?.multiSeat?.blinds?.dealerSeat ?? 0),
        smallBlindSeat: Number(state?.multiSeat?.smallBlindSeat ?? state?.multiSeat?.blinds?.smallBlindSeat ?? 0),
        bigBlindSeat: Number(state?.multiSeat?.bigBlindSeat ?? state?.multiSeat?.blinds?.bigBlindSeat ?? 0),
    };
}

function multiSeatPositionBadges(player, state) {
    const seatNumber = Number(player?.seatNumber ?? 0);
    const positions = multiSeatSeatPositionNumbers(state);

    return [
        {
            key: 'dealer',
            shortLabel: 'D',
            label: 'Dealer',
            active: Boolean(player?.isDealer) || (positions.dealerSeat > 0 && seatNumber === positions.dealerSeat),
            className: 'border-amber-100/70 bg-amber-300 text-amber-950 shadow-amber-950/20',
        },
        {
            key: 'small-blind',
            shortLabel: 'SB',
            label: 'Small Blind',
            active: Boolean(player?.isSmallBlind) || (positions.smallBlindSeat > 0 && seatNumber === positions.smallBlindSeat),
            className: 'border-sky-100/60 bg-sky-300 text-sky-950 shadow-sky-950/20',
        },
        {
            key: 'big-blind',
            shortLabel: 'BB',
            label: 'Big Blind',
            active: Boolean(player?.isBigBlind) || (positions.bigBlindSeat > 0 && seatNumber === positions.bigBlindSeat),
            className: 'border-fuchsia-100/60 bg-fuchsia-300 text-fuchsia-950 shadow-fuchsia-950/20',
        },
    ].filter((badge) => badge.active);
}

function multiSeatBlindSummary(state) {
    const positions = multiSeatSeatPositionNumbers(state);
    const parts = [
        positions.dealerSeat > 0 ? `Dealer: assento ${positions.dealerSeat}` : null,
        positions.smallBlindSeat > 0 ? `SB: assento ${positions.smallBlindSeat}` : null,
        positions.bigBlindSeat > 0 ? `BB: assento ${positions.bigBlindSeat}` : null,
    ].filter(Boolean);

    return parts.length > 0 ? parts.join(' • ') : 'Dealer/SB/BB aguardando nova mão';
}

function formatChipAmount(value) {
    return Number(value ?? 0).toLocaleString('pt-BR');
}

function playerInitials(name) {
    return String(name ?? 'Jogador')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'P';
}

function stackPressureLabel(player, state) {
    const stack = Number(player?.stack ?? 0);
    const bigBlind = Number(state?.bigBlind ?? state?.multiSeat?.blinds?.bigBlind ?? 0);

    if (stack <= 0) {
        return 'Sem fichas';
    }

    if (bigBlind > 0 && stack <= bigBlind * 3) {
        return 'Short stack';
    }

    if (bigBlind > 0 && stack <= bigBlind * 8) {
        return 'Pressão';
    }

    return 'Stack saudável';
}

function stackPressureClasses(player, state) {
    const label = stackPressureLabel(player, state);

    if (label === 'Sem fichas') {
        return 'border-slate-400/25 bg-slate-950/70 text-slate-200';
    }

    if (label === 'Short stack') {
        return 'border-rose-200/45 bg-rose-400/20 text-rose-100';
    }

    if (label === 'Pressão') {
        return 'border-amber-200/45 bg-amber-300/20 text-amber-100';
    }

    return 'border-emerald-200/35 bg-emerald-300/15 text-emerald-100';
}

function actionTimelineItems(state) {
    const history = Array.isArray(state?.actionHistory) ? state.actionHistory : [];

    return [...history]
        .slice(-5)
        .reverse()
        .map((action, index) => ({
            key: `${action?.seatNumber ?? action?.tablePlayerId ?? 'action'}-${action?.action ?? action?.type ?? index}-${index}`,
            seatNumber: action?.seatNumber,
            actor: action?.nickname ?? action?.playerName ?? action?.actorLabel ?? (action?.seatNumber ? `Assento ${action.seatNumber}` : 'Jogador'),
            label: normalizeActionLabel(action) ?? 'Ação',
            amount: Number(action?.amount ?? 0),
        }));
}


function actionToneLabel(label) {
    const normalized = String(label ?? '').toLowerCase();

    if (normalized.includes('all')) {
        return 'All-in';
    }

    if (normalized.includes('raise')) {
        return 'Raise';
    }

    if (normalized.includes('bet')) {
        return 'Bet';
    }

    if (normalized.includes('call')) {
        return 'Call';
    }

    if (normalized.includes('check')) {
        return 'Check';
    }

    if (normalized.includes('fold')) {
        return 'Fold';
    }

    return 'Ação';
}

function actionStreetLabel(action, fallbackStreet) {
    const street = String(action?.street ?? action?.round ?? fallbackStreet ?? '').toLowerCase();
    const labels = {
        pre_flop: 'Pré-flop',
        preflop: 'Pré-flop',
        flop: 'Flop',
        turn: 'Turn',
        river: 'River',
        showdown: 'Showdown',
    };

    return labels[street] ?? 'Mesa';
}

function actionImpactLabel(item) {
    if (Number(item?.amount ?? 0) > 0) {
        return `${item.label} ${formatChipAmount(item.amount)}`;
    }

    return item?.label ?? 'Ação';
}

function actionTimelineDetailedItems(state) {
    const history = Array.isArray(state?.actionHistory) ? state.actionHistory : [];

    return [...history]
        .slice(-7)
        .reverse()
        .map((action, index) => {
            const label = normalizeActionLabel(action) ?? 'Ação';

            return {
                key: `${action?.id ?? action?.seatNumber ?? action?.tablePlayerId ?? 'action'}-${action?.action ?? action?.type ?? label}-${index}`,
                seatNumber: action?.seatNumber,
                actor: action?.nickname ?? action?.playerName ?? action?.actorLabel ?? (action?.seatNumber ? `Assento ${action.seatNumber}` : 'Jogador'),
                label,
                tone: actionToneLabel(label),
                street: actionStreetLabel(action, state?.street),
                amount: Number(action?.amount ?? 0),
            };
        });
}

function actionFlowSummary(state) {
    const actions = actionTimelineDetailedItems(state);
    const latest = actions[0];

    if (!latest) {
        return {
            title: 'Mesa aguardando primeira ação',
            description: state?.isFinished ? 'Mão encerrada sem novas ações registradas.' : 'Assim que alguém agir, o fluxo aparece aqui.',
        };
    }

    return {
        title: `${latest.actor} · ${actionImpactLabel(latest)}`,
        description: `${latest.street} · ${state?.isFinished ? 'mão finalizada' : 'mesa em andamento'}`,
    };
}

function ActionFlowPanel({ state }) {
    const actions = actionTimelineDetailedItems(state);
    const summary = actionFlowSummary(state);

    return (
        <aside className="overflow-hidden rounded-2xl border border-white/10 bg-black/25 shadow-inner shadow-black/40">
            <div className="flex items-center justify-between gap-3 border-b border-white/10 bg-white/[0.04] px-3 py-2">
                <div className="min-w-0">
                    <p className="text-[0.56rem] font-black uppercase tracking-[0.22em] text-amber-100/75">Ritmo da mesa</p>
                    <strong className="block truncate text-sm font-black text-white" title={summary.title}>{summary.title}</strong>
                    <span className="block truncate text-[0.65rem] font-semibold text-emerald-100/75" title={summary.description}>{summary.description}</span>
                </div>

                <span className="poker-action-pulse shrink-0 rounded-full border border-emerald-100/35 bg-emerald-300/15 px-2 py-1 text-[0.58rem] font-black uppercase tracking-[0.14em] text-emerald-100">
                    Ao vivo
                </span>
            </div>

            <div className="poker-compact-scroll flex max-h-36 flex-col gap-1.5 overflow-y-auto p-2">
                {actions.length === 0 && (
                    <p className="rounded-xl border border-white/10 bg-slate-950/50 px-3 py-2 text-[0.68rem] font-semibold text-slate-300">
                        Nenhuma ação registrada nesta mão ainda.
                    </p>
                )}

                {actions.map((action) => (
                    <div key={action.key} className="grid grid-cols-[auto_1fr_auto] items-center gap-2 rounded-xl border border-white/10 bg-slate-950/45 px-2 py-1.5">
                        <span className={`rounded-full border px-2 py-0.5 text-[0.55rem] font-black uppercase tracking-[0.12em] ${multiSeatActionPillClasses(action.label, false, action.label === 'Fold')}`}>
                            {action.tone}
                        </span>

                        <div className="min-w-0">
                            <strong className="block truncate text-[0.72rem] font-black text-white">{action.actor}</strong>
                            <span className="block truncate text-[0.6rem] font-semibold text-slate-300/80">{action.street}</span>
                        </div>

                        <span className="rounded-full border border-white/10 bg-black/35 px-2 py-1 text-[0.58rem] font-black text-amber-100">
                            {actionImpactLabel(action)}
                        </span>
                    </div>
                ))}
            </div>
        </aside>
    );
}



function nextActionOptions(state) {
    const options = [
        { key: 'fold', label: 'Fold', active: Boolean(state?.canFold ?? !state?.isFinished), helper: 'Desistir da mão' },
        { key: 'check', label: 'Check', active: Boolean(state?.canCheck), helper: 'Passar sem apostar' },
        { key: 'call', label: state?.callAmount ? `Call ${formatChipAmount(state.callAmount)}` : 'Call', active: Boolean(state?.canCall), helper: 'Pagar a aposta' },
        { key: 'raise', label: 'Raise', active: Boolean(state?.canRaise ?? state?.canBet), helper: 'Aumentar pressão' },
        { key: 'all-in', label: 'All-in', active: Boolean(state?.canAllIn ?? state?.canRaise ?? state?.canBet), helper: 'Colocar todas as fichas' },
    ];

    return options;
}

function commandPanelTitle(state, currentPlayer, currentTurnPlayer) {
    if (state?.isFinished) {
        return 'Mão encerrada';
    }

    if (!currentPlayer && state?.tournamentRuntime) {
        return 'Modo espectador';
    }

    if (Boolean(state?.canAct)) {
        return 'Sua decisão';
    }

    if (currentTurnPlayer?.nickname) {
        return `${currentTurnPlayer.nickname} decide`;
    }

    return 'Aguardando mesa';
}

function commandPanelDescription(state, currentPlayer) {
    if (state?.isFinished) {
        return 'Confira o showdown e inicie a próxima mão quando estiver disponível.';
    }

    if (!currentPlayer && state?.tournamentRuntime) {
        return 'Você foi eliminado, mas pode acompanhar stacks, apostas e ações dos jogadores restantes.';
    }

    if (Boolean(state?.canAct)) {
        return 'As opções disponíveis aparecem em destaque para reduzir dúvida na jogada.';
    }

    return state?.currentTurn?.message ?? 'Os cards dos jogadores continuam exibindo ação, stack e aposta atual.';
}

function PremiumCommandPanel({ state, currentPlayer, currentTurnPlayer }) {
    const options = nextActionOptions(state);
    const activeOptions = options.filter((option) => option.active);

    return (
        <aside className="poker-command-panel overflow-hidden rounded-2xl border border-white/10 bg-black/25 shadow-inner shadow-black/40">
            <div className="border-b border-white/10 bg-white/[0.04] px-3 py-2">
                <p className="text-[0.56rem] font-black uppercase tracking-[0.22em] text-sky-100/75">Comando da mão</p>
                <strong className="block truncate text-sm font-black text-white">{commandPanelTitle(state, currentPlayer, currentTurnPlayer)}</strong>
                <span className="block text-[0.65rem] font-semibold leading-snug text-slate-200/75">{commandPanelDescription(state, currentPlayer)}</span>
            </div>

            <div className="grid gap-1.5 p-2">
                {options.map((option) => (
                    <div
                        key={option.key}
                        className={[
                            'grid grid-cols-[1fr_auto] items-center gap-2 rounded-xl border px-2.5 py-2 transition',
                            option.active
                                ? 'poker-command-option-active border-emerald-100/35 bg-emerald-300/12 text-white'
                                : 'border-white/10 bg-slate-950/45 text-slate-400',
                        ].join(' ')}
                    >
                        <div className="min-w-0">
                            <strong className="block truncate text-[0.72rem] font-black uppercase tracking-[0.12em]">{option.label}</strong>
                            <span className="block truncate text-[0.58rem] font-semibold opacity-75">{option.helper}</span>
                        </div>

                        <span className={[
                            'rounded-full border px-2 py-0.5 text-[0.52rem] font-black uppercase tracking-[0.12em]',
                            option.active ? 'border-emerald-100/45 bg-emerald-300 text-emerald-950' : 'border-slate-400/20 bg-slate-900 text-slate-400',
                        ].join(' ')}>{option.active ? 'Disponível' : 'Bloq.'}</span>
                    </div>
                ))}

                {activeOptions.length === 0 && !state?.isFinished && (
                    <p className="rounded-xl border border-amber-200/20 bg-amber-300/10 px-3 py-2 text-[0.65rem] font-semibold text-amber-100">
                        Nenhuma ação sua agora. Acompanhe o card do jogador da vez e o ritmo da mesa.
                    </p>
                )}
            </div>
        </aside>
    );
}

function streetProgressItems(state) {
    const streets = [
        { key: 'pre_flop', label: 'Pré-flop' },
        { key: 'flop', label: 'Flop' },
        { key: 'turn', label: 'Turn' },
        { key: 'river', label: 'River' },
        { key: 'showdown', label: 'Showdown' },
    ];
    const currentIndex = streets.findIndex((street) => street.key === state?.street);
    const safeCurrentIndex = currentIndex >= 0 ? currentIndex : 0;

    return streets.map((street, index) => ({
        ...street,
        isActive: street.key === state?.street || (state?.isFinished && street.key === 'showdown'),
        isCompleted: index < safeCurrentIndex || Boolean(state?.isFinished),
    }));
}

function activeMultiSeatPlayers(state) {
    return multiSeatPlayers(state).filter((player) => Number(player?.stack ?? 0) > 0 && !Boolean(player?.hasFolded));
}

function chipLeaderLabel(state) {
    const leader = [...multiSeatPlayers(state)]
        .filter((player) => Number(player?.stack ?? 0) > 0)
        .sort((left, right) => Number(right?.stack ?? 0) - Number(left?.stack ?? 0))[0];

    if (!leader) {
        return 'A definir';
    }

    return `${leader.nickname ?? leader.displayName ?? `Assento ${leader.seatNumber}`} · ${formatChipAmount(leader.stack)}`;
}

function shortStackLabel(state) {
    const shortStack = [...multiSeatPlayers(state)]
        .filter((player) => Number(player?.stack ?? 0) > 0)
        .sort((left, right) => Number(left?.stack ?? 0) - Number(right?.stack ?? 0))[0];

    if (!shortStack) {
        return 'A definir';
    }

    return `${shortStack.nickname ?? shortStack.displayName ?? `Assento ${shortStack.seatNumber}`} · ${formatChipAmount(shortStack.stack)}`;
}

function latestTableActionLabel(state) {
    const [latest] = actionTimelineItems(state);

    if (!latest) {
        return 'Aguardando ação';
    }

    return latest.amount > 0
        ? `${latest.actor}: ${latest.label} ${formatChipAmount(latest.amount)}`
        : `${latest.actor}: ${latest.label}`;
}

function tournamentBlindLevelLabel(state) {
    const runtime = state?.tournamentRuntime;
    const level = runtime?.blindLevel ?? runtime?.level ?? null;

    if (!runtime) {
        return `Blinds ${formatChipAmount(state?.smallBlind)} / ${formatChipAmount(state?.bigBlind)}`;
    }

    return level
        ? `Nível ${level} · ${formatChipAmount(state?.smallBlind)} / ${formatChipAmount(state?.bigBlind)}`
        : `Blinds ${formatChipAmount(state?.smallBlind)} / ${formatChipAmount(state?.bigBlind)}`;
}

function MultiSeatMesaHud({ state }) {
    const players = multiSeatPlayers(state);
    const activeCount = activeMultiSeatPlayers(state).length;
    const eliminatedCount = players.filter((player) => Number(player?.stack ?? 0) <= 0).length;
    const hudItems = [
        { label: 'Ativos', value: `${activeCount}/${players.length}` },
        { label: 'Chip leader', value: chipLeaderLabel(state) },
        { label: 'Short stack', value: shortStackLabel(state) },
        { label: 'Última ação', value: latestTableActionLabel(state) },
    ];

    return (
        <div className="grid gap-2 rounded-2xl border border-white/10 bg-black/25 p-2 shadow-inner shadow-black/40 md:grid-cols-4">
            {hudItems.map((item) => (
                <div key={item.label} className="rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2">
                    <p className="text-[0.55rem] font-black uppercase tracking-[0.18em] text-emerald-100/60">{item.label}</p>
                    <strong className="mt-1 block truncate text-[0.78rem] font-black text-white" title={item.value}>{item.value}</strong>
                </div>
            ))}

            {eliminatedCount > 0 && (
                <div className="rounded-xl border border-rose-200/25 bg-rose-400/10 px-3 py-2 md:col-span-4">
                    <p className="text-[0.58rem] font-black uppercase tracking-[0.16em] text-rose-100/80">
                        {eliminatedCount} eliminado{eliminatedCount > 1 ? 's' : ''} neste torneio · mesa em acompanhamento
                    </p>
                </div>
            )}
        </div>
    );
}

function StreetProgressRail({ state }) {
    return (
        <div className="rounded-2xl border border-white/10 bg-black/25 p-2 shadow-inner shadow-black/40">
            <div className="mb-2 flex items-center justify-between gap-2">
                <p className="text-[0.58rem] font-black uppercase tracking-[0.2em] text-amber-100/75">Progresso da mão</p>
                <span className="rounded-full border border-emerald-200/25 bg-emerald-300/10 px-2 py-1 text-[0.58rem] font-black uppercase tracking-[0.14em] text-emerald-100">
                    {tournamentBlindLevelLabel(state)}
                </span>
            </div>

            <div className="grid grid-cols-5 gap-1.5">
                {streetProgressItems(state).map((street) => (
                    <span
                        key={street.key}
                        className={[
                            'rounded-full border px-2 py-1 text-center text-[0.56rem] font-black uppercase tracking-[0.12em] transition',
                            street.isActive
                                ? 'border-amber-100/70 bg-amber-300 text-amber-950 shadow-lg shadow-amber-950/25'
                                : street.isCompleted
                                    ? 'border-emerald-100/35 bg-emerald-300/15 text-emerald-100'
                                    : 'border-white/10 bg-slate-950/60 text-slate-300/70',
                        ].join(' ')}
                    >
                        {street.label}
                    </span>
                ))}
            </div>
        </div>
    );
}

function multiSeatWinnerSeats(state) {
    const winnerSeats = Array.isArray(state?.multiSeat?.winnerSeats) ? state.multiSeat.winnerSeats : [];
    const normalizedWinnerSeats = winnerSeats
        .map(Number)
        .filter((seatNumber) => seatNumber > 0);
    const conclusionSeat = Number(state?.conclusion?.winner?.seatNumber ?? 0);

    if (normalizedWinnerSeats.length > 0) {
        return [...new Set(normalizedWinnerSeats)];
    }

    return conclusionSeat > 0 ? [conclusionSeat] : [];
}

function isMultiSeatSplitPot(state) {
    return Boolean(state?.isFinished) && multiSeatWinnerSeats(state).length > 1;
}

function isMultiSeatWinner(state, seatNumber) {
    return Boolean(state?.isFinished) && multiSeatWinnerSeats(state).includes(Number(seatNumber));
}

function multiSeatWinnerBadgeLabel(state, seatNumber) {
    if (!isMultiSeatWinner(state, seatNumber)) {
        return null;
    }

    return isMultiSeatSplitPot(state) ? 'Empate' : 'Vencedor';
}

function multiSeatShowdownSummary(state) {
    if (!state?.isFinished) {
        return state?.currentTurn?.message ?? 'Sincronizando mesa.';
    }

    if (isMultiSeatSplitPot(state)) {
        return `Pote dividido entre ${multiSeatWinnerSeats(state).length} jogadores.`;
    }

    return state?.currentTurn?.message ?? 'Showdown concluído. Inicie uma nova mão para liberar novas ações.';
}

function multiSeatCardVisibilityLabel(isCurrentUserSeat, isFinished, isRevealed, hasFolded = false) {
    if (hasFolded) {
        return 'Cartas descartadas';
    }

    if (isFinished) {
        return 'Cartas abertas no showdown';
    }

    if (!isCurrentUserSeat) {
        return 'Cartas protegidas';
    }

    return isRevealed ? 'Ocultar suas cartas' : 'Revelar suas cartas';
}

function normalizeActionLabel(action) {
    const raw = String(action?.label ?? action?.action ?? action?.type ?? '').toLowerCase();

    const labels = {
        fold: 'Fold',
        folded: 'Fold',
        check: 'Check',
        call: 'Call',
        bet: 'Bet',
        raise: 'Raise',
        all_in: 'All-in',
        allin: 'All-in',
    };

    return labels[raw] ?? (action?.label ?? action?.action ?? action?.type ?? null);
}

function latestActionForSeat(state, player) {
    const seatNumber = Number(player?.seatNumber ?? 0);
    const tablePlayerId = Number(player?.tablePlayerId ?? player?.id ?? 0);
    const history = Array.isArray(state?.actionHistory) ? state.actionHistory : [];

    const action = [...history].reverse().find((item) => {
        const itemSeat = Number(item?.seatNumber ?? 0);
        const itemPlayerId = Number(item?.tablePlayerId ?? item?.table_player_id ?? 0);

        return (seatNumber > 0 && itemSeat === seatNumber)
            || (tablePlayerId > 0 && itemPlayerId === tablePlayerId);
    });

    if (action) {
        return action;
    }

    const lastAction = state?.lastAction;

    if (Number(lastAction?.seatNumber ?? 0) === seatNumber) {
        return lastAction;
    }

    return null;
}

function multiSeatLastActionLabel(state, player, hasFolded, isCurrentTurn) {
    if (hasFolded) {
        return 'Fold';
    }

    const action = latestActionForSeat(state, player);
    const label = normalizeActionLabel(action);

    if (label) {
        const amount = Number(action?.amount ?? 0);

        return amount > 0 ? `${label} ${amount}` : label;
    }

    if (isCurrentTurn) {
        return 'Pensando';
    }

    return 'Aguardando';
}

function multiSeatActionPillClasses(actionLabel, isCurrentTurn, hasFolded) {
    const normalized = String(actionLabel ?? '').toLowerCase();

    if (hasFolded || normalized.includes('fold')) {
        return 'border-slate-400/30 bg-slate-950/80 text-slate-200';
    }

    if (normalized.includes('all')) {
        return 'border-rose-100/60 bg-rose-400 text-rose-950 shadow-rose-950/20';
    }

    if (normalized.includes('raise') || normalized.includes('bet')) {
        return 'border-amber-100/60 bg-amber-300 text-amber-950 shadow-amber-950/20';
    }

    if (normalized.includes('call')) {
        return 'border-emerald-100/60 bg-emerald-300 text-emerald-950 shadow-emerald-950/20';
    }

    if (normalized.includes('check')) {
        return 'border-sky-100/60 bg-sky-300 text-sky-950 shadow-sky-950/20';
    }

    if (isCurrentTurn || normalized.includes('pensando')) {
        return 'border-emerald-100/45 bg-emerald-300 text-emerald-950 shadow-emerald-950/20';
    }

    return 'border-violet-100/40 bg-violet-300 text-violet-950 shadow-violet-950/20';
}

function shouldShowMultiSeatActionPill(actionLabel) {
    return Boolean(actionLabel) && String(actionLabel).toLowerCase() !== 'aguardando';
}

function multiSeatAssistedModeLabel(state, currentPlayer) {
    if (!state?.tournamentRuntime) {
        return null;
    }

    if (currentPlayer) {
        return null;
    }

    return 'Modo assistido — você foi eliminado, mas a mesa continua em acompanhamento.';
}

function MultiSeatPlayerSpot({ player, state, currentUserSeat, currentTurnSeat, playerCardsRevealed, onTogglePlayerCards, index }) {
    const seatNumber = Number(player?.seatNumber ?? 0);
    const isCurrentUserSeat = seatNumber === currentUserSeat;
    const isCurrentTurn = currentTurnSeat !== null && seatNumber === currentTurnSeat && !state?.isFinished;
    const winnerBadge = multiSeatWinnerBadgeLabel(state, seatNumber);
    const isWinner = Boolean(winnerBadge);
    const hasFolded = Boolean(player?.hasFolded) || player?.status === 'folded';
    const cards = isCurrentUserSeat ? (state?.playerCards ?? []) : (player?.cards ?? []);
    const shouldRevealCards = !hasFolded && (Boolean(state?.isFinished) || (isCurrentUserSeat && playerCardsRevealed));
    const visibleCards = shouldRevealCards ? cards : [];
    const hiddenCount = Math.max(0, (cards?.length || 2) - visibleCards.length);
    const displayName = isCurrentUserSeat ? 'Você' : (player?.nickname ?? player?.displayName ?? `Jogador ${seatNumber}`);
    const bestHand = isCurrentUserSeat ? state?.bestHand : player?.bestHand;
    const positionBadges = multiSeatPositionBadges(player, state);
    const actionLabel = multiSeatLastActionLabel(state, player, hasFolded, isCurrentTurn);
    const actionPillClasses = multiSeatActionPillClasses(actionLabel, isCurrentTurn, hasFolded);

    return (
        <article
            className={[
                'relative overflow-hidden rounded-2xl border p-2 shadow-2xl shadow-black/35 transition duration-300 sm:p-3',
                isWinner
                    ? 'poker-winner-seat border-amber-200/70 bg-amber-300/15'
                    : isCurrentTurn
                        ? 'poker-turn-glow border-emerald-200/60 bg-emerald-300/10'
                        : hasFolded
                            ? 'border-slate-500/20 bg-black/25 opacity-60'
                            : 'border-white/10 bg-black/25',
            ].join(' ')}
        >
            <div className="mb-2 flex items-start justify-between gap-2">
                <div className="flex min-w-0 items-start gap-2">
                    <div className={[
                        'grid h-9 w-9 shrink-0 place-items-center rounded-full border text-[0.7rem] font-black shadow-lg shadow-black/30',
                        isCurrentTurn ? 'border-emerald-100/60 bg-emerald-300 text-emerald-950' : isWinner ? 'border-amber-100/70 bg-amber-300 text-amber-950' : 'border-white/15 bg-white/10 text-white',
                    ].join(' ')}>
                        {playerInitials(displayName)}
                    </div>

                    <div className="min-w-0">
                        <p className="text-[0.56rem] font-black uppercase tracking-[0.18em] text-amber-100/75">Assento {seatNumber}</p>
                        <strong className="block truncate text-sm font-black text-white sm:text-base">{displayName}</strong>
                        <div className="mt-1 flex flex-wrap gap-1">
                            {positionBadges.map((badge) => (
                                <span
                                    key={badge.key}
                                    title={badge.label}
                                    className={`inline-flex items-center rounded-full border px-1.5 py-0.5 text-[0.55rem] font-black uppercase tracking-[0.14em] shadow-lg ${badge.className}`}
                                >
                                    {badge.shortLabel}
                                </span>
                            ))}
                            <span className={`inline-flex items-center rounded-full border px-1.5 py-0.5 text-[0.55rem] font-black uppercase tracking-[0.12em] ${stackPressureClasses(player, state)}`}>
                                {stackPressureLabel(player, state)}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="flex shrink-0 flex-col items-end gap-1 text-[0.58rem] font-black uppercase tracking-[0.15em]">
                    {isCurrentTurn && <span className="rounded-full border border-emerald-100/45 bg-emerald-300 px-2 py-0.5 text-emerald-950">Vez</span>}
                    {winnerBadge && <span className="rounded-full border border-amber-100/70 bg-amber-300 px-2 py-0.5 text-amber-950">{winnerBadge}</span>}
                    {shouldShowMultiSeatActionPill(actionLabel) && (
                        <span className={`rounded-full border px-2 py-0.5 ${actionPillClasses}`}>
                            {actionLabel}
                        </span>
                    )}
                </div>
            </div>

            <button
                type="button"
                disabled={!isCurrentUserSeat || Boolean(state?.isFinished) || hasFolded}
                onClick={() => isCurrentUserSeat && !state?.isFinished && !hasFolded && onTogglePlayerCards()}
                className={`block w-full rounded-xl border border-white/10 bg-white/[0.04] p-2 text-left transition ${isCurrentUserSeat && !state?.isFinished && !hasFolded ? 'hover:border-amber-200/40 hover:bg-amber-200/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-200/80' : 'cursor-default'}`}
                aria-label={multiSeatCardVisibilityLabel(isCurrentUserSeat, state?.isFinished, playerCardsRevealed, hasFolded)}
            >
                <div className="poker-card-scroll flex justify-center gap-1 overflow-x-auto pb-1 sm:gap-1.5">
                    {visibleCards.map((card, cardIndex) => (
                        <PlayingCard
                            key={`${seatNumber}-${card?.label ?? cardIndex}`}
                            card={card}
                            compact
                            dealIndex={index + cardIndex}
                            dealStepMs={110}
                            dealFrom={isCurrentUserSeat ? 'bottom' : 'dealer'}
                        />
                    ))}

                    {Array.from({ length: hiddenCount }).map((_, cardIndex) => (
                        <PlayingCard
                            key={`${seatNumber}-hidden-${cardIndex}`}
                            hidden
                            compact
                            dealIndex={index + cardIndex}
                            dealStepMs={110}
                            dealFrom={isCurrentUserSeat ? 'bottom' : 'dealer'}
                        />
                    ))}
                </div>

                <p className="mt-1 text-center text-[0.58rem] font-black uppercase tracking-[0.16em] text-amber-100/80">
                    {multiSeatCardVisibilityLabel(isCurrentUserSeat, state?.isFinished, playerCardsRevealed, hasFolded)}
                </p>
            </button>

            <div className="mt-2 grid grid-cols-2 gap-1 text-center text-[0.62rem] font-bold text-slate-200/85 sm:grid-cols-4">
                <span className="rounded-lg bg-black/35 px-2 py-1">Stack<br /><strong className="text-white">{formatChipAmount(player?.stack)}</strong></span>
                <span className="rounded-lg bg-black/35 px-2 py-1">Aposta<br /><strong className="text-white">{formatChipAmount(player?.streetBet)}</strong></span>
                <span className="rounded-lg bg-black/35 px-2 py-1">Ação<br /><strong className="text-white">{actionLabel}</strong></span>
                <span className="rounded-lg bg-black/35 px-2 py-1">Status<br /><strong className="text-white">{hasFolded ? 'Fold' : isCurrentTurn ? 'Vez' : 'Ativo'}</strong></span>
            </div>

            <div className="mt-2 flex items-center justify-between gap-2 rounded-xl border border-white/10 bg-black/25 px-2 py-1.5 text-[0.6rem] font-bold text-emerald-100/80">
                <span className="uppercase tracking-[0.16em]">Fichas</span>
                <div className="flex items-end gap-1" aria-hidden="true">
                    {chipAmountParts(player?.stack).map((height, chipIndex) => (
                        <span
                            key={`${seatNumber}-chip-${chipIndex}`}
                            className="block w-4 rounded-full border border-amber-100/70 bg-gradient-to-br from-red-500 via-red-700 to-red-950 shadow-md shadow-black/40"
                            style={{ height: `${Math.min(22, 8 + height * 3)}px` }}
                        />
                    ))}
                </div>
            </div>

            {!hasFolded && (state?.isFinished || isCurrentUserSeat) && bestHand?.name && (
                <p className="mt-2 rounded-xl border border-white/10 bg-black/25 px-2 py-1.5 text-center text-[0.68rem] font-bold text-emerald-100">
                    Melhor mão: {bestHand.name}
                </p>
            )}
        </article>
    );
}

function MultiSeatPokerTable({ state, community, playerCardsRevealed, setPlayerCardsRevealed }) {
    const players = multiSeatPlayers(state);
    const currentSeat = currentUserSeatNumber(state);
    const currentTurnSeat = multiSeatCurrentTurnSeat(state);
    const currentTurnPlayer = players.find((player) => Number(player.seatNumber ?? 0) === currentTurnSeat);
    const currentPlayer = players.find((player) => Number(player.seatNumber ?? 0) === currentSeat);
    const opponents = players.filter((player) => Number(player.seatNumber ?? 0) !== currentSeat);
    const maxPlayers = Number(state?.multiSeat?.maxPlayers ?? state?.tableCapacity?.maxPlayers ?? players.length ?? 0);
    const assistedModeLabel = multiSeatAssistedModeLabel(state, currentPlayer);
    const actionTimeline = actionTimelineItems(state);
    const commandCurrentPlayer = currentPlayer;
    const commandCurrentTurnPlayer = currentTurnPlayer;

    return (
        <section className="poker-table-breath relative overflow-hidden rounded-[1.1rem] border border-amber-200/20 bg-[radial-gradient(ellipse_at_center,#166534_0%,#065f46_36%,#052e2b_66%,#020617_100%)] p-1.5 shadow-[0_30px_90px_rgba(0,0,0,0.55)] sm:rounded-[2rem] sm:p-4">
            <div className="pointer-events-none absolute inset-1 rounded-[1rem] border-[3px] border-amber-950/45 shadow-inner shadow-black/80 sm:inset-3 sm:rounded-[1.6rem] sm:border-[7px]" />
            <div className="pointer-events-none absolute inset-4 rounded-[999px] border border-amber-200/20 sm:inset-x-16 sm:inset-y-28" />
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,191,36,0.14),transparent_34%),linear-gradient(120deg,rgba(255,255,255,0.10),transparent_25%,transparent_75%,rgba(255,255,255,0.06))]" />

            <div className="relative z-10 grid gap-3">
                <div className="grid gap-2 rounded-2xl border border-amber-200/25 bg-black/30 p-2 text-center shadow-2xl shadow-black/35 sm:grid-cols-[1fr_auto_1fr] sm:items-center sm:p-3">
                    <div className="text-left">
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.22em] text-amber-100/75">Mesa multi-seat</p>
                        <strong className="block text-lg font-black text-white">{players.length}/{maxPlayers} jogadores</strong>
                    </div>

                    <div className="rounded-full border border-amber-200/40 bg-amber-300/15 px-4 py-2 shadow-xl shadow-amber-950/25">
                        <p className="text-[0.56rem] font-black uppercase tracking-[0.2em] text-amber-100">Pote</p>
                        <strong className="block text-2xl font-black text-white">{formatChipAmount(state?.pot)}</strong>
                    </div>

                    <div className="text-left sm:text-right">
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.22em] text-emerald-100/75">Turno atual</p>
                        <strong className="block text-base font-black text-white">
                            {state?.isFinished ? 'Mão finalizada' : (currentTurnPlayer?.nickname ?? state?.currentTurn?.actorLabel ?? 'Aguardando')}
                        </strong>
                        <span className="text-[0.68rem] font-semibold text-emerald-100/75">{multiSeatShowdownSummary(state)}</span>
                        {assistedModeLabel && (
                            <span className="mt-1 block rounded-full border border-amber-200/30 bg-amber-300/10 px-2 py-1 text-[0.62rem] font-bold text-amber-100">
                                {assistedModeLabel}
                            </span>
                        )}
                    </div>
                </div>

                <div className="grid gap-2 rounded-2xl border border-white/10 bg-black/20 p-2 shadow-inner shadow-black/30 md:grid-cols-[1fr_auto] md:items-center">
                    <div className="flex flex-wrap items-center gap-2 text-[0.62rem] font-black uppercase tracking-[0.16em] text-emerald-100/75">
                        <span className="rounded-full border border-emerald-200/25 bg-emerald-300/10 px-2 py-1">Blinds {formatChipAmount(state?.smallBlind)} / {formatChipAmount(state?.bigBlind)}</span>
                        <span className="rounded-full border border-amber-200/25 bg-amber-300/10 px-2 py-1">{multiSeatBlindSummary(state)}</span>
                    </div>

                    {actionTimeline.length > 0 && (
                        <div className="flex min-w-0 flex-wrap justify-start gap-1 md:justify-end" aria-label="Últimas ações da mesa">
                            {actionTimeline.map((item) => (
                                <span key={item.key} className={`rounded-full border px-2 py-1 text-[0.58rem] font-black uppercase tracking-[0.12em] ${multiSeatActionPillClasses(item.label, false, item.label === 'Fold')}`}>
                                    {item.actor}: {item.amount > 0 ? `${item.label} ${formatChipAmount(item.amount)}` : item.label}
                                </span>
                            ))}
                        </div>
                    )}
                </div>

                <MultiSeatMesaHud state={state} />
                <div className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(260px,340px)_minmax(260px,340px)]">
                    <StreetProgressRail state={state} />
                    <ActionFlowPanel state={state} />
                    <PremiumCommandPanel
                        state={state}
                        currentPlayer={commandCurrentPlayer}
                        currentTurnPlayer={commandCurrentTurnPlayer}
                    />
                </div>

                <div className="grid gap-3 xl:min-h-[620px] xl:grid-rows-[minmax(160px,auto)_minmax(220px,1fr)_minmax(170px,auto)]">
                    <div className={`grid gap-2 md:grid-cols-2 ${players.length <= 2 ? 'mx-auto w-full max-w-4xl xl:grid-cols-2' : 'xl:grid-cols-3'} xl:items-start`}>
                        {opponents.map((player, index) => (
                            <MultiSeatPlayerSpot
                                key={`opponent-${player.seatNumber}`}
                                player={player}
                                state={state}
                                currentUserSeat={currentSeat}
                                currentTurnSeat={currentTurnSeat}
                                playerCardsRevealed={playerCardsRevealed}
                                onTogglePlayerCards={() => setPlayerCardsRevealed((isRevealed) => !isRevealed)}
                                index={index}
                            />
                        ))}
                    </div>

                    <div className="flex min-w-0 items-center justify-center px-1 sm:px-6 xl:px-16">
                        <div className="w-full max-w-3xl rounded-[2rem] border border-amber-200/25 bg-black/25 p-3 shadow-2xl shadow-black/50 backdrop-blur sm:p-5">
                            <CardRow
                                title="Board / Cartas comunitárias"
                                cards={community.visible}
                                hiddenCount={community.hiddenCount}
                                tone="hero"
                                dealStartIndex={4}
                                dealFrom="dealer"
                            />
                        </div>
                    </div>

                    <div className="mx-auto w-full max-w-4xl xl:self-end">
                        {currentPlayer && (
                            <MultiSeatPlayerSpot
                                key={`current-${currentPlayer.seatNumber}`}
                                player={currentPlayer}
                                state={state}
                                currentUserSeat={currentSeat}
                                currentTurnSeat={currentTurnSeat}
                                playerCardsRevealed={playerCardsRevealed}
                                onTogglePlayerCards={() => setPlayerCardsRevealed((isRevealed) => !isRevealed)}
                                index={8}
                            />
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}

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

    const isBotVsBotSimulation = Boolean(state.botVsBotSimulation);
    const hasPlayerCards = (state.playerCards ?? []).length > 0;
    const shouldRevealPlayerCards = isBotVsBotSimulation || state.isFinished || playerCardsRevealed;
    const shouldRevealOpponentCards = isBotVsBotSimulation || state.isFinished;
    const playerBestHandVisible = shouldRevealPlayerCards && state.bestHand?.name;

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
        <section className="poker-table-breath relative overflow-hidden rounded-[1.1rem] border border-amber-200/20 bg-[radial-gradient(circle_at_center,#166534_0%,#065f46_38%,#052e2b_68%,#020617_100%)] p-1.5 shadow-[0_30px_90px_rgba(0,0,0,0.55)] sm:rounded-[2rem] sm:p-4">
            <div className="pointer-events-none absolute inset-1 rounded-[1rem] border-[3px] border-amber-950/45 shadow-inner shadow-black/80 sm:inset-3 sm:rounded-[1.6rem] sm:border-[7px]" />
            <div className="pointer-events-none absolute inset-3 rounded-[0.9rem] border border-amber-200/20 sm:inset-6 sm:rounded-[1.35rem]" />
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,191,36,0.14),transparent_34%),linear-gradient(120deg,rgba(255,255,255,0.10),transparent_25%,transparent_75%,rgba(255,255,255,0.06))]" />
            <div className="poker-dealer-shoe pointer-events-none absolute left-1/2 top-3 z-20 hidden -translate-x-1/2 items-center gap-2 rounded-full border border-amber-200/30 bg-black/55 px-3 py-1.5 text-[0.58rem] font-black uppercase tracking-[0.22em] text-amber-100 shadow-2xl shadow-black/45 sm:flex">
                <span className="h-2 w-2 rounded-full bg-amber-300 shadow-[0_0_12px_rgba(252,211,77,0.9)]" />
                {dealerAnimationLabel(state)}
            </div>

            <div className="relative z-10 grid min-h-[340px] gap-1 sm:gap-3 md:min-h-[410px] lg:min-h-[500px] lg:grid-rows-[auto_1fr_auto]">
                <div className="grid min-w-0 gap-1.5 sm:gap-3 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-start">
                    <div className={[
                        'relative overflow-hidden rounded-lg border p-1.5 transition duration-300 sm:rounded-2xl sm:p-3',
                        seatFrameClasses(state, 'opponent'),
                    ].join(' ')}>
                        {winnerBadgeLabel(state, 'opponent') && (
                            <span className={[
                                'absolute right-2 top-2 rounded-full border px-2 py-0.5 text-[0.6rem] font-black uppercase tracking-[0.22em]',
                                seatBadgeClasses(state, 'opponent'),
                            ].join(' ')}>
                                {winnerBadgeLabel(state, 'opponent')}
                            </span>
                        )}

                        {shouldRevealOpponentCards && state.opponentCards ? (
                            <CardRow
                                title={opponentCardsTitle(state)}
                                cards={state.opponentCards ?? []}
                                align="left"
                                dealStartIndex={2}
                                dealFrom="left"
                            />
                        ) : (
                            <CardRow
                                title="Adversário"
                                hiddenCount={2}
                                align="left"
                                dealStartIndex={1}
                                dealFrom="left"
                            />
                        )}

                        {shouldRevealOpponentCards && state.opponentBestHand?.name && (
                            <p className="mt-2 rounded-xl border border-white/10 bg-white/10 px-2 py-1.5 text-center text-[0.7rem] font-bold text-slate-100">
                                Melhor mão: {state.opponentBestHand.name}
                            </p>
                        )}
                    </div>

                    <div className="poker-status-slide poker-turn-glow rounded-lg border border-amber-200/30 bg-black/35 p-1.5 text-center shadow-2xl shadow-black/40 sm:rounded-2xl sm:p-3">
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.18em] text-amber-200 sm:text-xs sm:tracking-[0.28em]">Turno atual</p>
                        <strong className="mt-0.5 block text-sm font-black text-white sm:mt-1 sm:text-base">{currentTurnLabel(state)}</strong>
                        <span className="mt-0.5 block truncate text-[0.62rem] font-semibold text-emerald-100/80 sm:text-[0.68rem]">{currentTurnMessage(state)}</span>
                    </div>
                </div>

                <div className="flex min-w-0 items-center justify-center">
                    <div className="w-full min-w-0 max-w-2xl rounded-xl border border-amber-200/25 bg-black/25 p-1.5 shadow-2xl shadow-black/50 backdrop-blur sm:rounded-2xl sm:p-3">
                        <div className="poker-soft-enter poker-pot-pulse relative mx-auto mb-1.5 w-fit rounded-full border border-amber-200/40 bg-amber-300/15 px-3 py-1 text-center shadow-xl shadow-amber-950/20 sm:mb-3 sm:px-5 sm:py-2">
                            <div className="pointer-events-none absolute -top-4 left-1/2 flex -translate-x-1/2 items-end gap-1">
                                {chipAmountParts(state.pot).map((height, index) => (
                                    <span
                                        key={`pot-chip-${index}`}
                                        style={{ animationDelay: `${index * 180}ms` }}
                                        className="poker-chip-float block h-4 w-4 rounded-full border-2 border-amber-100/80 bg-gradient-to-br from-red-500 via-red-700 to-red-950 shadow-lg shadow-black/35 sm:h-5 sm:w-5 sm:border-[3px]"
                                    >
                                        <span className="mx-auto mt-0.5 block h-1.5 w-1.5 rounded-full bg-amber-100/80" />
                                    </span>
                                ))}
                            </div>
                            <p className="text-[0.55rem] font-black uppercase tracking-[0.18em] text-amber-100 sm:text-[0.62rem] sm:tracking-[0.22em]">Pote total</p>
                            <strong className="block text-lg font-black text-white sm:text-4xl">{state.pot}</strong>
                        </div>

                        <CardRow
                            title="Board / Cartas comunitárias"
                            cards={community.visible}
                            hiddenCount={community.hiddenCount}
                            tone="hero"
                            dealStartIndex={4}
                            dealFrom="dealer"
                        />
                    </div>
                </div>

                <div className="grid min-w-0 gap-1.5 sm:gap-3 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-end">
                    <button
                        type="button"
                        disabled={!hasPlayerCards || state.isFinished || isBotVsBotSimulation}
                        onClick={() => hasPlayerCards && !state.isFinished && !isBotVsBotSimulation && setPlayerCardsRevealed((isRevealed) => !isRevealed)}
                        className={`group relative block min-w-0 rounded-2xl text-left transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-200/80 ${hasPlayerCards && !state.isFinished && !isBotVsBotSimulation ? 'cursor-pointer hover:-translate-y-0.5 hover:shadow-2xl hover:shadow-amber-950/25' : 'cursor-default'}`}
                        aria-label={hiddenPlayerHandTitle(shouldRevealPlayerCards)}
                    >
                        <CardRow
                            title={currentPlayerTitle(state)}
                            cards={shouldRevealPlayerCards ? (state.playerCards ?? []) : []}
                            hiddenCount={shouldRevealPlayerCards ? 0 : (state.playerCards ?? []).length}
                            dealStartIndex={0}
                            dealFrom="bottom"
                        />

                        {hasPlayerCards && (
                            <div className="mt-1.5 rounded-xl border border-amber-200/20 bg-black/30 px-2 py-1.5 text-center shadow-inner shadow-black/30 sm:mt-2">
                                <p className="text-[0.58rem] font-black uppercase tracking-[0.18em] text-amber-100/90 sm:text-[0.65rem] sm:tracking-[0.24em]">
                                    {isBotVsBotSimulation ? 'Modo espectador — cartas abertas' : hiddenPlayerHandTitle(shouldRevealPlayerCards)}
                                </p>
                                <p className="mt-0.5 text-[0.62rem] font-semibold text-emerald-100/75 sm:text-[0.7rem]">
                                    {isBotVsBotSimulation
                                        ? 'Simulação Bot vs Bot: as duas mãos ficam visíveis para acompanhamento.'
                                        : hiddenPlayerHandDescription(shouldRevealPlayerCards, state.isFinished)}
                                </p>
                            </div>
                        )}
                    </button>

                    <div className={[
                        'relative overflow-hidden rounded-lg border p-1.5 text-center transition duration-300 sm:rounded-2xl sm:p-3',
                        seatFrameClasses(state, 'player'),
                    ].join(' ')}>
                        {winnerBadgeLabel(state, 'player') && (
                            <span className={[
                                'mx-auto mb-2 inline-flex rounded-full border px-2 py-0.5 text-[0.6rem] font-black uppercase tracking-[0.22em]',
                                seatBadgeClasses(state, 'player'),
                            ].join(' ')}>
                                {winnerBadgeLabel(state, 'player')}
                            </span>
                        )}

                        <p className="text-[0.58rem] font-black uppercase tracking-[0.18em] text-emerald-100 sm:text-xs sm:tracking-[0.28em]">Melhor mão</p>
                        <strong className="mt-0.5 block text-sm font-black text-white sm:mt-1 sm:text-base">{playerBestHandVisible ? state.bestHand.name : (shouldRevealPlayerCards ? 'Aguardando' : 'Cartas ocultas')}</strong>
                        <p className="mt-0.5 text-[0.62rem] text-slate-300 sm:mt-1 sm:text-[0.7rem]">Stack: {state.playerStack}</p>
                    </div>
                </div>
            </div>
        </section>
    );
}
