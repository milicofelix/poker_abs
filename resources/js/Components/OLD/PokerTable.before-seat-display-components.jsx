import { useEffect, useMemo, useState } from 'react';
import CardRow from './CardRow';
import PlayingCard from './PlayingCard';
import {
    ActionEffectOverlay,
    LatestActionToast,
    LivePotDisplay,
    PokerActionReplayRail,
} from './PokerActionDisplay';
import {
    LiveTurnTimerBadge,
    MobileTableStickyStatus,
    TournamentLiveHud,
    YourTurnOverlay,
} from './PokerTableLiveStatus';
import {
    ShowdownCinematicRibbon,
    ShowdownPremiumPanel,
} from './PokerShowdownDisplay';
import PokerTableAnimationStyles from './PokerTableAnimationStyles';
import usePokerTurnTimer from '../../hooks/usePokerTurnTimer';
import { useShowdownHighlights } from '@/features/poker/hooks/useShowdownHighlights';
import {
    actionVisualTone,
    isLatestLegacyAction,
    isLatestSeatAction,
    latestActionAnimationKey,
    latestActionItem,
    latestLegacyActionItem,
    multiSeatActionPillClasses,
    multiSeatLastActionLabel,
    premiumActionControlItems,
    shouldShowMultiSeatActionPill,
} from '@/features/poker/utils/tableActions';
import {
    multiSeatCardVisibilityLabel,
    multiSeatShowdownCardsForPlayer,
} from '@/features/poker/utils/tableCards';
import {
    multiSeatAssistedModeLabel,
    seatLiveStateClasses,
    seatLiveStateLabel,
} from '@/features/poker/utils/tableLive';
import { playerInitials, stackPressureClasses, stackPressureLabel } from '@/features/poker/utils/tablePlayers';
import {
    multiSeatShowdownSummary,
    multiSeatWinnerBadgeLabel,
} from '@/features/poker/utils/tableShowdown';
import {
    cardSignature,
    chipAmountParts,
    currentPlayerTitle,
    currentTurnLabel,
    currentTurnMessage,
    currentUserSeatNumber,
    dealerAnimationLabel,
    formatChipAmount,
    hiddenPlayerHandDescription,
    hiddenPlayerHandTitle,
    isMultiSeatLayout,
    isMultiSeatShowdownResolved,
    multiSeatBlindSummary,
    multiSeatCurrentTurnSeat,
    multiSeatPlayers,
    multiSeatPositionBadges,
    opponentCardsTitle,
    seatBadgeClasses,
    seatFrameClasses,
    visibleCommunityCards,
    winnerBadgeLabel,
} from '@/features/poker/utils/tableState';

function MultiSeatPlayerSpot({ player, state, currentUserSeat, currentTurnSeat, playerCardsRevealed, onTogglePlayerCards, index, opponentsCount = 0, turnTimer = null, showdownHighlights = null }) {
    const seatNumber = Number(player?.seatNumber ?? 0);
    const isCurrentUserSeat = seatNumber === currentUserSeat;
    const isCurrentTurn = currentTurnSeat !== null && seatNumber === currentTurnSeat && !state?.isFinished;
    const winnerBadge = multiSeatWinnerBadgeLabel(state, seatNumber);
    const isWinner = Boolean(winnerBadge);
    const hasFolded = Boolean(player?.hasFolded) || player?.status === 'folded';
    const isAllIn = Boolean(player?.isAllIn) || Number(player?.stack ?? 0) <= 0;
    const liveStateLabel = seatLiveStateLabel(player, isCurrentTurn, hasFolded, isAllIn);
    const liveStateClasses = seatLiveStateClasses(liveStateLabel);
    const isShowdownFinished = isMultiSeatShowdownResolved(state);
    const cards = multiSeatShowdownCardsForPlayer(player, state, isCurrentUserSeat, opponentsCount);
    const shouldRevealCards = !hasFolded && ((isShowdownFinished && cards.length > 0) || (Boolean(state?.isFinished) || (isCurrentUserSeat && playerCardsRevealed)));
    const visibleCards = shouldRevealCards ? cards : [];
    const hiddenCount = Math.max(0, (cards.length || 2) - visibleCards.length);
    const displayName = isCurrentUserSeat ? 'Você' : (player?.nickname ?? player?.displayName ?? `Jogador ${seatNumber}`);
    const bestHand = isCurrentUserSeat ? state?.bestHand : player?.bestHand;
    const positionBadges = multiSeatPositionBadges(player, state);
    const actionLabel = multiSeatLastActionLabel(state, player, hasFolded, isCurrentTurn);
    const actionPillClasses = multiSeatActionPillClasses(actionLabel, isCurrentTurn, hasFolded);
    const isLatestAction = isLatestSeatAction(state, seatNumber) && !state?.isFinished;
    const latestSeatAction = isLatestAction ? latestActionItem(state) : null;
    const actionVisual = actionVisualTone(actionLabel);

    return (
        <article
            key={`${seatNumber}-${latestActionAnimationKey(state)}`}
            className={[
                'group relative overflow-hidden rounded-2xl border p-2 shadow-2xl shadow-black/35 transition duration-300 hover:-translate-y-0.5 hover:shadow-black/50 sm:p-3',
                isLatestAction ? `poker-action-flash ${actionVisual.seatClass}` : '',
                isWinner
                    ? 'poker-winner-seat border-amber-200/70 bg-amber-300/15'
                    : isCurrentTurn
                        ? 'poker-live-turn-seat poker-live-border-sweep scale-[1.015] border-emerald-100/70 bg-emerald-300/15 shadow-[0_0_46px_rgba(16,185,129,0.26)]'
                        : hasFolded
                            ? 'border-slate-500/20 bg-black/25 opacity-60'
                            : 'border-white/10 bg-black/25',
            ].join(' ')}
        >
            <ActionEffectOverlay item={latestSeatAction} />
            <div className="mb-2 flex items-start justify-between gap-2">
                <div className="flex min-w-0 items-start gap-2">
                    <div className={[
                        'grid h-10 w-10 shrink-0 place-items-center rounded-full border text-[0.72rem] font-black shadow-lg shadow-black/30 ring-2 ring-black/25 transition duration-300',
                        isCurrentTurn ? 'scale-110 border-emerald-100/80 bg-emerald-300 text-emerald-950 shadow-[0_0_28px_rgba(110,231,183,0.42)] ring-emerald-100/30' : isWinner ? 'border-amber-100/70 bg-amber-300 text-amber-950' : 'border-white/15 bg-white/10 text-white',
                    ].join(' ')}>
                        {playerInitials(displayName)}
                    </div>

                    <div className="min-w-0">
                        <p className="text-[0.56rem] font-black uppercase tracking-[0.18em] text-amber-100/75">Seat {seatNumber}</p>
                        <strong className={`block truncate text-sm font-black sm:text-base ${isCurrentTurn ? 'poker-live-name-glow text-emerald-50' : 'text-white'}`}>{displayName}</strong>
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
                    <span className={`rounded-full border px-2 py-0.5 ${liveStateClasses}`}>{liveStateLabel}</span>
                    {isCurrentTurn && <span className="rounded-full border border-emerald-100/45 bg-emerald-300 px-2 py-0.5 text-emerald-950">Vez</span>}
                    {winnerBadge && <span className="rounded-full border border-amber-100/70 bg-amber-300 px-2 py-0.5 text-amber-950">{winnerBadge}</span>}
                    {shouldShowMultiSeatActionPill(actionLabel) && (
                        <span className={`rounded-full border px-2 py-0.5 ${isLatestAction ? 'poker-action-pop' : ''} ${actionPillClasses}`}>
                            {actionLabel}
                        </span>
                    )}
                </div>
            </div>

            {isCurrentTurn && (
                <div className="mb-2">
                    <LiveTurnTimerBadge timer={turnTimer} />
                </div>
            )}

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
                            highlighted={Boolean(showdownHighlights?.hasHighlights) && Boolean(showdownHighlights?.isHighlightedCard?.(card))}
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

            <div className="mt-2 grid grid-cols-2 gap-1 text-center text-[0.58rem] font-bold text-slate-200/85 sm:grid-cols-4">
                <span className="rounded-xl border border-white/10 bg-black/40 px-2 py-1">Stack<br /><strong className="text-white">{formatChipAmount(player?.stack)}</strong></span>
                <span className="rounded-xl border border-white/10 bg-black/40 px-2 py-1">Aposta<br /><strong className="text-white">{formatChipAmount(player?.streetBet)}</strong></span>
                <span className="rounded-xl border border-white/10 bg-black/40 px-2 py-1">Ação<br /><strong className="text-white">{actionLabel}</strong></span>
                <span className="rounded-xl border border-white/10 bg-black/40 px-2 py-1">Status<br /><strong className="text-white">{hasFolded ? 'Fold' : isCurrentTurn ? 'Vez' : 'Ativo'}</strong></span>
            </div>

            <div className="mt-2 flex items-center justify-between gap-2 rounded-xl border border-emerald-200/15 bg-black/25 px-2 py-1.5 text-[0.6rem] font-bold text-emerald-100/80">
                <span className="uppercase tracking-[0.16em]">Pilha</span>
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


function PremiumActionControlPanel({ state }) {
    const items = premiumActionControlItems(state);
    const canAct = items.some((item) => item.enabled);
    const latest = latestActionItem(state);
    const minimumRaise = Number(state?.minimumRaiseTo ?? state?.minimumRaise ?? state?.minRaise ?? 0);
    const maximumRaise = Number(state?.maximumRaiseTo ?? state?.maximumRaise ?? state?.maxRaise ?? state?.playerStack ?? 0);
    const raiseSpan = Math.max(0, maximumRaise - minimumRaise);
    const sliderPercent = maximumRaise > 0 && minimumRaise > 0
        ? Math.min(100, Math.max(10, Math.round((minimumRaise / maximumRaise) * 100)))
        : 34;

    return (
        <aside className="poker-mobile-action-panel poker-safe-sticky-actions sticky bottom-2 z-40 rounded-[1.35rem] border border-amber-200/25 bg-slate-950/94 p-3 shadow-2xl shadow-black/55 backdrop-blur-xl xl:top-4 xl:bottom-auto" aria-label="Comando premium da mão">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-[0.58rem] font-black uppercase tracking-[0.24em] text-amber-100/70">Comando</p>
                    <strong className="mt-1 block text-base font-black text-white">{canAct ? 'Sua decisão' : 'Acompanhando mesa'}</strong>
                </div>
                <span className={`rounded-full border px-2.5 py-1 text-[0.58rem] font-black uppercase tracking-[0.14em] ${canAct ? 'border-emerald-200/45 bg-emerald-300/15 text-emerald-100' : 'border-slate-400/25 bg-slate-900 text-slate-300'}`}>
                    {canAct ? 'Ativo' : 'Bloqueado'}
                </span>
            </div>

            <div className="mt-3 grid grid-cols-2 gap-2 max-[420px]:gap-1.5">
                {items.map((item) => (
                    <div
                        key={item.key}
                        className={`rounded-2xl border px-3 py-2.5 text-left transition active:scale-[0.98] max-[420px]:px-2.5 max-[420px]:py-2 sm:py-2 ${item.className} ${item.enabled ? 'shadow-lg shadow-black/20' : 'opacity-45 grayscale'}`}
                    >
                        <span className="block text-base font-black uppercase tracking-[0.12em] max-[420px]:text-sm sm:text-sm">{item.label}</span>
                        <span className="mt-0.5 block text-[0.62rem] font-bold uppercase tracking-[0.12em] opacity-80 max-[420px]:text-[0.56rem]">{item.helper}</span>
                    </div>
                ))}
            </div>

            <div className="mt-3 rounded-2xl border border-white/10 bg-black/30 p-2.5">
                <div className="flex items-center justify-between text-[0.6rem] font-black uppercase tracking-[0.16em] text-slate-300">
                    <span>Raise</span>
                    <span>{raiseSpan > 0 ? `${formatChipAmount(minimumRaise)} → ${formatChipAmount(maximumRaise)}` : 'Indisponível'}</span>
                </div>
                <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-800">
                    <div className="h-full rounded-full bg-gradient-to-r from-amber-300 via-orange-300 to-rose-300" style={{ width: `${sliderPercent}%` }} />
                </div>
                <div className="mt-2 grid grid-cols-3 gap-1 text-center text-[0.58rem] font-black uppercase tracking-[0.12em] text-amber-100/80">
                    <span className="rounded-full border border-white/10 bg-white/[0.04] px-2 py-1">1/2 pote</span>
                    <span className="rounded-full border border-white/10 bg-white/[0.04] px-2 py-1">Pote</span>
                    <span className="rounded-full border border-white/10 bg-white/[0.04] px-2 py-1">All-in</span>
                </div>
            </div>

            <p className="mt-3 rounded-2xl border border-emerald-200/15 bg-emerald-300/10 px-3 py-2 text-[0.68rem] font-bold leading-snug text-emerald-100/85">
                {latest
                    ? `Última ação: ${latest.actor} · ${latest.amount > 0 ? `${latest.label} ${formatChipAmount(latest.amount)}` : latest.label}`
                    : 'As ações executadas aparecerão em destaque no assento de cada jogador.'}
            </p>
        </aside>
    );
}

function MultiSeatPokerTable({ state, community, playerCardsRevealed, setPlayerCardsRevealed }) {
    const turnTimer = usePokerTurnTimer(state?.turnTimer);
    const showdownHighlights = useShowdownHighlights(state);
    const players = multiSeatPlayers(state);
    const currentSeat = currentUserSeatNumber(state);
    const currentTurnSeat = multiSeatCurrentTurnSeat(state);
    const currentTurnPlayer = players.find((player) => Number(player.seatNumber ?? 0) === currentTurnSeat);
    const currentPlayer = players.find((player) => Number(player.seatNumber ?? 0) === currentSeat);
    const opponents = players.filter((player) => Number(player.seatNumber ?? 0) !== currentSeat);
    const maxPlayers = Number(state?.multiSeat?.maxPlayers ?? state?.tableCapacity?.maxPlayers ?? players.length ?? 0);
    const assistedModeLabel = multiSeatAssistedModeLabel(state, currentPlayer);
    const tableStatusLabel = state?.isFinished
        ? multiSeatShowdownSummary(state)
        : (currentTurnPlayer?.nickname ?? state?.currentTurn?.actorLabel ?? 'Aguardando ação');
    const liveFocusName = currentTurnPlayer?.nickname ?? currentTurnPlayer?.displayName ?? state?.currentTurn?.actorLabel ?? 'Aguardando ação';

    return (
        <section className="poker-table-breath relative overflow-hidden rounded-[1.4rem] border border-amber-200/20 bg-[radial-gradient(ellipse_at_center,#166534_0%,#065f46_34%,#052e2b_64%,#020617_100%)] p-2 pb-24 shadow-[0_30px_90px_rgba(0,0,0,0.55)] sm:rounded-[2rem] sm:p-4 sm:pb-4">
            <PokerTableAnimationStyles />
            <div className="pointer-events-none absolute inset-1 rounded-[1.1rem] border-[3px] border-amber-950/45 shadow-inner shadow-black/80 sm:inset-3 sm:rounded-[1.7rem] sm:border-[7px]" />
            <div className="pointer-events-none absolute inset-x-3 top-[22%] bottom-[17%] rounded-[999px] border border-amber-200/25 shadow-[inset_0_0_60px_rgba(0,0,0,0.45)] sm:inset-x-10 lg:inset-x-20" />
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,191,36,0.14),transparent_32%),linear-gradient(120deg,rgba(255,255,255,0.10),transparent_25%,transparent_75%,rgba(255,255,255,0.06))]" />

            <div className="relative z-10 grid gap-3">
                <div className="flex flex-col gap-2 rounded-2xl border border-white/10 bg-slate-950/45 p-2 shadow-xl shadow-black/35 backdrop-blur md:flex-row md:items-center md:justify-between">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-full border border-emerald-200/25 bg-emerald-300/10 px-3 py-1 text-[0.6rem] font-black uppercase tracking-[0.18em] text-emerald-100">
                            {players.length}/{maxPlayers} jogadores
                        </span>
                        <span className="rounded-full border border-emerald-200/25 bg-emerald-300/10 px-3 py-1 text-[0.6rem] font-black uppercase tracking-[0.18em] text-emerald-100">
                            Blinds {formatChipAmount(state?.smallBlind)} / {formatChipAmount(state?.bigBlind)}
                        </span>
                        <span className="rounded-full border border-amber-200/25 bg-amber-300/10 px-3 py-1 text-[0.6rem] font-black uppercase tracking-[0.16em] text-amber-100">
                            {multiSeatBlindSummary(state)}
                        </span>
                    </div>

                    <div className="min-w-0 text-left md:text-right">
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.22em] text-amber-100/70">Mesa em jogo</p>
                        <strong className="block truncate text-sm font-black text-white md:max-w-[420px]" title={tableStatusLabel}>{tableStatusLabel}</strong>
                    </div>
                </div>

                {assistedModeLabel && (
                    <div className="rounded-2xl border border-amber-200/25 bg-amber-300/10 px-3 py-2 text-sm font-bold text-amber-100 shadow-xl shadow-black/30">
                        {assistedModeLabel}
                    </div>
                )}

                <TournamentLiveHud state={state} currentPlayer={currentPlayer} />

                {!state?.isFinished && currentTurnSeat !== null && (
                    <div className="grid gap-2 rounded-[1.4rem] border border-emerald-200/25 bg-emerald-300/10 p-2 shadow-2xl shadow-emerald-950/20 backdrop-blur lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                        <div className="min-w-0 px-1">
                            <p className="text-[0.58rem] font-black uppercase tracking-[0.26em] text-emerald-100/70">Live focus</p>
                            <strong className="block truncate text-lg font-black text-white" title={liveFocusName}>{liveFocusName}</strong>
                            <p className="text-xs font-semibold text-emerald-100/75">Assento {currentTurnSeat} está com a decisão da rodada.</p>
                        </div>
                        <LiveTurnTimerBadge timer={turnTimer} compact />
                    </div>
                )}

                <MobileTableStickyStatus state={state} />

                <ShowdownPremiumPanel state={state} />
                <ShowdownCinematicRibbon state={state} />
                <LatestActionToast state={state} />
                <YourTurnOverlay state={state} timer={turnTimer} />

                <div className="grid gap-3 xl:grid-cols-[minmax(0,1fr)_320px] xl:items-start">
                <div className="relative order-2 min-h-[430px] overflow-hidden rounded-[1.5rem] border border-amber-200/20 bg-[radial-gradient(ellipse_at_center,rgba(6,95,70,0.78),rgba(2,44,34,0.84)_58%,rgba(2,6,23,0.82)_100%)] p-2 shadow-inner shadow-black/60 sm:min-h-[620px] sm:p-4 xl:order-1 xl:min-h-[670px]">
                    <div className="pointer-events-none absolute inset-x-10 top-24 bottom-28 rounded-[999px] border border-amber-200/25 shadow-[0_0_90px_rgba(0,0,0,0.35),inset_0_0_80px_rgba(0,0,0,0.35)]" />
                    <div className="pointer-events-none absolute left-1/2 top-[34%] h-[1px] w-2/3 -translate-x-1/2 bg-gradient-to-r from-transparent via-amber-100/20 to-transparent" />

                    <div className="relative z-10 grid min-h-[410px] grid-rows-[auto_1fr_auto] gap-2 sm:min-h-[590px] sm:gap-3 xl:min-h-[640px]">
                        <div className="poker-card-scroll flex gap-2 overflow-x-auto pb-1 xl:grid xl:grid-cols-3 xl:overflow-visible xl:pb-0">
                            {opponents.length === 0 && (
                                <div className="rounded-2xl border border-white/10 bg-black/25 p-4 text-sm font-bold text-slate-200">
                                    Aguardando adversários sentados.
                                </div>
                            )}
                            {opponents.map((player, index) => (
                                <div key={`opponent-wrap-${player.seatNumber}`} className="min-w-[220px] sm:min-w-[270px] xl:min-w-0">
                                    <MultiSeatPlayerSpot
                                        key={`opponent-${player.seatNumber}`}
                                        player={player}
                                        state={state}
                                        currentUserSeat={currentSeat}
                                        currentTurnSeat={currentTurnSeat}
                                        playerCardsRevealed={playerCardsRevealed}
                                        onTogglePlayerCards={() => setPlayerCardsRevealed((isRevealed) => !isRevealed)}
                                        index={index}
                                        opponentsCount={opponents.length}
                                        turnTimer={turnTimer}
                                        showdownHighlights={showdownHighlights}
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="flex min-w-0 flex-col items-center justify-center gap-3 px-1 sm:px-5 xl:px-20">
                            <LivePotDisplay state={state} />

                            <div key={`board-${state?.street ?? 'mesa'}-${community.visible.length}`} className="poker-street-transition w-full max-w-3xl rounded-[2rem] border border-amber-200/25 bg-black/25 p-3 shadow-2xl shadow-black/50 backdrop-blur sm:p-5">
                                <CardRow
                                    title="Board / Cartas comunitárias"
                                    cards={community.visible}
                                    hiddenCount={community.hiddenCount}
                                    tone="hero"
                                    dealStartIndex={4}
                                    dealFrom="dealer"
                                    highlightCards={showdownHighlights.highlightCards}
                                    highlightActive={showdownHighlights.hasHighlights}
                                />
                            </div>

                            <PokerActionReplayRail state={state} />
                        </div>

                        <div className="mx-auto w-full max-w-5xl">
                            {currentPlayer ? (
                                <MultiSeatPlayerSpot
                                    key={`current-${currentPlayer.seatNumber}`}
                                    player={currentPlayer}
                                    state={state}
                                    currentUserSeat={currentSeat}
                                    currentTurnSeat={currentTurnSeat}
                                    playerCardsRevealed={playerCardsRevealed}
                                    onTogglePlayerCards={() => setPlayerCardsRevealed((isRevealed) => !isRevealed)}
                                    index={8}
                                    turnTimer={turnTimer}
                                />
                            ) : (
                                <div className="rounded-2xl border border-amber-200/25 bg-amber-300/10 p-4 text-sm font-bold text-amber-100">
                                    Você está acompanhando a mesa como espectador/eliminado. Acompanhe as ações nos assentos e no histórico recolhido abaixo.
                                </div>
                            )}
                        </div>
                    </div>
                </div>
                    <div className="order-1 xl:order-2">
                        <PremiumActionControlPanel state={state} />
                    </div>
                </div>
            </div>
        </section>
    );
}

export default function PokerTable({ state }) {
    const community = visibleCommunityCards(state);
    const showdownHighlights = useShowdownHighlights(state);
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
    const turnTimer = usePokerTurnTimer(state?.turnTimer);

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
        <section className="poker-table-breath relative overflow-hidden rounded-[1.1rem] border border-amber-200/20 bg-[radial-gradient(circle_at_center,#166534_0%,#065f46_38%,#052e2b_68%,#020617_100%)] p-1.5 pb-24 shadow-[0_30px_90px_rgba(0,0,0,0.55)] sm:rounded-[2rem] sm:p-4 sm:pb-4">
            <PokerTableAnimationStyles />
            <div className="pointer-events-none absolute inset-1 rounded-[1rem] border-[3px] border-amber-950/45 shadow-inner shadow-black/80 sm:inset-3 sm:rounded-[1.6rem] sm:border-[7px]" />
            <div className="pointer-events-none absolute inset-3 rounded-[0.9rem] border border-amber-200/20 sm:inset-6 sm:rounded-[1.35rem]" />
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,191,36,0.14),transparent_34%),linear-gradient(120deg,rgba(255,255,255,0.10),transparent_25%,transparent_75%,rgba(255,255,255,0.06))]" />
            <div className="poker-dealer-shoe pointer-events-none absolute left-1/2 top-3 z-20 hidden -translate-x-1/2 items-center gap-2 rounded-full border border-amber-200/30 bg-black/55 px-3 py-1.5 text-[0.58rem] font-black uppercase tracking-[0.22em] text-amber-100 shadow-2xl shadow-black/45 sm:flex">
                <span className="h-2 w-2 rounded-full bg-amber-300 shadow-[0_0_12px_rgba(252,211,77,0.9)]" />
                {dealerAnimationLabel(state)}
            </div>

            <div className="relative z-10 grid min-h-[340px] gap-1 sm:gap-3 md:min-h-[410px] lg:min-h-[500px] lg:grid-rows-[auto_1fr_auto]">
                <LatestActionToast state={state} />
                <YourTurnOverlay state={state} timer={turnTimer} />
                <ShowdownCinematicRibbon state={state} />

                <div className="grid min-w-0 gap-1.5 sm:gap-3 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-start">
                    <div className={[
                        'relative overflow-visible rounded-lg border p-1.5 transition duration-300 sm:rounded-2xl sm:p-3',
                        seatFrameClasses(state, 'opponent'),
                        isLatestLegacyAction(state, 'opponent') ? `poker-action-flash ${actionVisualTone(latestActionItem(state)?.label).seatClass}` : '',
                    ].join(' ')}>
                        <ActionEffectOverlay item={latestLegacyActionItem(state, 'opponent')} />

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
                                highlightCards={showdownHighlights.highlightCards}
                                highlightActive={showdownHighlights.hasHighlights}
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
                        <LivePotDisplay state={state} compact />

                        <div key={`board-single-${state?.street ?? 'mesa'}-${community.visible.length}`} className="poker-street-transition">
                            <CardRow
                                title="Board / Cartas comunitárias"
                                cards={community.visible}
                                hiddenCount={community.hiddenCount}
                                tone="hero"
                                dealStartIndex={4}
                                dealFrom="dealer"
                                highlightCards={showdownHighlights.highlightCards}
                                highlightActive={showdownHighlights.hasHighlights}
                            />
                        </div>

                        <PokerActionReplayRail state={state} compact />
                    </div>
                </div>

                <div className="grid min-w-0 gap-1.5 sm:gap-3 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-end">
                    <button
                        type="button"
                        disabled={!hasPlayerCards || state.isFinished || isBotVsBotSimulation}
                        onClick={() => hasPlayerCards && !state.isFinished && !isBotVsBotSimulation && setPlayerCardsRevealed((isRevealed) => !isRevealed)}
                        className={`group relative block min-w-0 overflow-visible rounded-2xl text-left transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-200/80 ${isLatestLegacyAction(state, 'player') ? `poker-action-flash ${actionVisualTone(latestActionItem(state)?.label).seatClass}` : ''} ${hasPlayerCards && !state.isFinished && !isBotVsBotSimulation ? 'cursor-pointer hover:-translate-y-0.5 hover:shadow-2xl hover:shadow-amber-950/25' : 'cursor-default'}`}
                        aria-label={hiddenPlayerHandTitle(shouldRevealPlayerCards)}
                    >
                        <ActionEffectOverlay item={latestLegacyActionItem(state, 'player')} />

                        <CardRow
                            title={currentPlayerTitle(state)}
                            cards={shouldRevealPlayerCards ? (state.playerCards ?? []) : []}
                            hiddenCount={shouldRevealPlayerCards ? 0 : (state.playerCards ?? []).length}
                            dealStartIndex={0}
                            dealFrom="bottom"
                            highlightCards={showdownHighlights.highlightCards}
                            highlightActive={showdownHighlights.hasHighlights}
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
