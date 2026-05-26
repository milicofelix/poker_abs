import PlayingCard from './PlayingCard';
import { ActionEffectOverlay } from './PokerActionDisplay';
import { LiveTurnTimerBadge } from './PokerTableLiveStatus';
import {
    actionVisualTone,
    isLatestSeatAction,
    latestActionAnimationKey,
    latestActionItem,
    multiSeatActionPillClasses,
    multiSeatLastActionLabel,
    shouldShowMultiSeatActionPill,
} from '@/features/poker/utils/tableActions';
import {
    multiSeatCardVisibilityLabel,
    multiSeatShowdownCardsForPlayer,
} from '@/features/poker/utils/tableCards';
import {
    seatLiveStateClasses,
    seatLiveStateLabel,
} from '@/features/poker/utils/tableLive';
import {
    playerInitials,
    stackPressureClasses,
    stackPressureLabel,
} from '@/features/poker/utils/tablePlayers';
import { multiSeatWinnerBadgeLabel } from '@/features/poker/utils/tableShowdown';
import {
    chipAmountParts,
    formatChipAmount,
    isMultiSeatShowdownResolved,
    multiSeatPositionBadges,
} from '@/features/poker/utils/tableState';

export function MultiSeatPlayerSpot({
    player,
    state,
    currentUserSeat,
    currentTurnSeat,
    playerCardsRevealed,
    onTogglePlayerCards,
    index,
    opponentsCount = 0,
    turnTimer = null,
    showdownHighlights = null,
}) {
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
