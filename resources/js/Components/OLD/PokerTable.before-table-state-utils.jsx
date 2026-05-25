import React, { useEffect, useMemo, useState } from 'react';
import { AnimatePresence, motion } from 'motion/react';
import CardRow from './CardRow';
import PlayingCard from './PlayingCard';
import usePokerTurnTimer from '../../hooks/usePokerTurnTimer';
import { useShowdownHighlights } from '@/features/poker/hooks/useShowdownHighlights';

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

    return state.multiSeat.players.length >= 2;
}

function isMultiSeatShowdownResolved(state) {
    if (!Boolean(state?.isFinished)) {
        return false;
    }

    return state?.street === 'showdown'
        || Boolean(state?.multiSeat?.showdownCardsRevealed)
        || state?.multiSeat?.showdownResolutionPhase === '10.12';
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


function useAnimatedChipAmount(value) {
    const target = Number(value ?? 0);
    const [displayValue, setDisplayValue] = useState(target);

    useEffect(() => {
        if (!Number.isFinite(target)) {
            setDisplayValue(0);
            return undefined;
        }

        const startValue = Number(displayValue ?? 0);

        if (startValue === target) {
            return undefined;
        }

        const duration = 520;
        const startedAt = typeof performance !== 'undefined' ? performance.now() : Date.now();
        let frameId = null;

        const tick = (timestamp) => {
            const now = Number(timestamp ?? Date.now());
            const elapsed = Math.max(0, now - startedAt);
            const progress = Math.min(1, elapsed / duration);
            const eased = 1 - Math.pow(1 - progress, 3);
            const nextValue = Math.round(startValue + ((target - startValue) * eased));

            setDisplayValue(nextValue);

            if (progress < 1 && typeof requestAnimationFrame !== 'undefined') {
                frameId = requestAnimationFrame(tick);
            }
        };

        if (typeof requestAnimationFrame === 'undefined') {
            setDisplayValue(target);
            return undefined;
        }

        frameId = requestAnimationFrame(tick);

        return () => {
            if (frameId !== null && typeof cancelAnimationFrame !== 'undefined') {
                cancelAnimationFrame(frameId);
            }
        };
    }, [target]);

    return displayValue;
}

function LivePotDisplay({ state, compact = false }) {
    const pot = Number(state?.pot ?? 0);
    const animatedPot = useAnimatedChipAmount(pot);
    const latest = latestActionItem(state);
    const increment = shouldPulsePot(state) ? Number(latest?.amount ?? 0) : 0;
    const chipSizeClass = compact
        ? 'h-4 w-4 border-2 sm:h-5 sm:w-5 sm:border-[3px]'
        : 'h-5 w-5 border-[3px]';
    const containerClass = compact
        ? 'poker-soft-enter poker-pot-pulse relative mx-auto mb-1.5 w-fit rounded-full border border-amber-200/40 bg-amber-300/15 px-3 py-1 text-center shadow-xl shadow-amber-950/20 sm:mb-3 sm:px-5 sm:py-2'
        : 'poker-soft-enter poker-pot-pulse relative mx-auto w-fit rounded-full border border-amber-200/45 bg-amber-300/15 px-7 py-3 text-center shadow-2xl shadow-amber-950/30';
    const labelClass = compact
        ? 'text-[0.55rem] font-black uppercase tracking-[0.18em] text-amber-100 sm:text-[0.62rem] sm:tracking-[0.22em]'
        : 'text-[0.62rem] font-black uppercase tracking-[0.24em] text-amber-100';
    const valueClass = compact
        ? 'block text-lg font-black text-white tabular-nums sm:text-4xl'
        : 'block text-4xl font-black text-white tabular-nums sm:text-5xl';

    return (
        <div className={`${containerClass} ${shouldPulsePot(state) ? 'poker-pot-receive' : ''}`}>
            <div className={`${compact ? '-top-4' : '-top-5'} pointer-events-none absolute left-1/2 flex -translate-x-1/2 items-end gap-1`}>
                {chipAmountParts(pot).map((height, index) => (
                    <span
                        key={`live-pot-chip-${compact ? 'compact' : 'table'}-${index}`}
                        style={{ animationDelay: `${index * 180}ms` }}
                        className={`poker-chip-float block rounded-full ${chipSizeClass} border-amber-100/80 bg-gradient-to-br from-red-500 via-red-700 to-red-950 shadow-lg shadow-black/35`}
                    >
                        <span className="mx-auto mt-0.5 block h-1.5 w-1.5 rounded-full bg-amber-100/80" />
                    </span>
                ))}
            </div>

            <AnimatePresence mode="wait">
                {increment > 0 && (
                    <motion.span
                        key={`live-pot-increment-${latestActionAnimationKey(state)}`}
                        initial={{ opacity: 0, y: 8, scale: 0.92 }}
                        animate={{ opacity: 1, y: compact ? -18 : -24, scale: 1 }}
                        exit={{ opacity: 0, y: compact ? -28 : -34, scale: 0.96 }}
                        transition={{ duration: 0.72, ease: 'easeOut' }}
                        className="pointer-events-none absolute -right-3 -top-2 rounded-full border border-emerald-200/40 bg-emerald-400/20 px-2 py-0.5 text-[0.6rem] font-black text-emerald-50 shadow-lg shadow-emerald-950/20"
                    >
                        +{formatChipAmount(increment)}
                    </motion.span>
                )}
            </AnimatePresence>

            <p className={labelClass}>Pote total</p>
            <strong className={valueClass}>{formatChipAmount(animatedPot)}</strong>
        </div>
    );
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

function actionActorRole(action) {
    const raw = String(action?.actor ?? action?.role ?? '').toLowerCase();

    if (raw === 'player' || raw === 'current' || raw === 'user') {
        return 'player';
    }

    if (raw === 'opponent' || raw === 'bot' || raw === 'adversario' || raw === 'adversário') {
        return 'opponent';
    }

    return raw || null;
}

function actionActorLabel(action) {
    const role = actionActorRole(action);

    if (action?.nickname || action?.playerName || action?.actorLabel) {
        return action.nickname ?? action.playerName ?? action.actorLabel;
    }

    if (action?.seatNumber) {
        return `Assento ${action.seatNumber}`;
    }

    if (role === 'player') {
        return 'Você';
    }

    if (role === 'opponent') {
        return 'Adversário';
    }

    return 'Jogador';
}

function actionTimelineItems(state) {
    const history = Array.isArray(state?.actionHistory) ? state.actionHistory : [];

    return [...history]
        .slice(-5)
        .reverse()
        .map((action, index) => ({
            key: `${action?.seatNumber ?? action?.tablePlayerId ?? actionActorRole(action) ?? 'action'}-${action?.action ?? action?.type ?? index}-${index}`,
            seatNumber: action?.seatNumber,
            actorRole: actionActorRole(action),
            actor: actionActorLabel(action),
            label: normalizeActionLabel(action) ?? 'Ação',
            amount: Number(action?.amount ?? 0),
        }));
}


function latestActionItem(state) {
    return actionTimelineItems(state)[0] ?? null;
}

function latestActionAnimationKey(state) {
    const latest = latestActionItem(state);

    if (!latest) {
        return `street-${state?.street ?? 'waiting'}-${Number(state?.pot ?? 0)}`;
    }

    return `${latest.key}-${state?.street ?? 'mesa'}-${Number(state?.pot ?? 0)}`;
}

function isLatestSeatAction(state, seatNumber) {
    const latest = latestActionItem(state);

    return latest && Number(latest.seatNumber ?? 0) === Number(seatNumber ?? 0);
}

function isLatestLegacyAction(state, actorRole) {
    const latest = latestActionItem(state);

    return latest && latest.actorRole === actorRole && !state?.isFinished;
}


function PokerActionReplayRail({ state, compact = false }) {
    const items = actionTimelineItems(state).slice(0, compact ? 3 : 5);

    if (items.length === 0) {
        return null;
    }

    const latestKey = latestActionItem(state)?.key;

    return (
        <div className={`w-full max-w-full ${compact ? 'mt-2' : ''}`} aria-label="Replay rápido das últimas ações">
            <div className="mb-1 flex items-center justify-between gap-2 px-1">
                <span className="text-[0.55rem] font-black uppercase tracking-[0.22em] text-amber-100/70">Replay da rodada</span>
                <span className="hidden rounded-full border border-white/10 bg-white/[0.06] px-2 py-0.5 text-[0.52rem] font-black uppercase tracking-[0.16em] text-slate-200 sm:inline-flex">
                    últimas ações
                </span>
            </div>

            <div className="poker-card-scroll flex max-w-full gap-1.5 overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/50 px-2 py-2 shadow-xl shadow-black/35 backdrop-blur">
                <AnimatePresence initial={false}>
                    {items.map((item, index) => {
                        const isLatest = item.key === latestKey;
                        const classes = multiSeatActionPillClasses(item.label, false, item.label === 'Fold');

                        return (
                            <motion.span
                                key={item.key}
                                initial={{ opacity: 0, y: -8, scale: 0.92 }}
                                animate={{ opacity: 1, y: 0, scale: isLatest ? 1.03 : 1 }}
                                exit={{ opacity: 0, y: 8, scale: 0.94 }}
                                transition={{ type: 'spring', stiffness: 420, damping: 28, delay: Math.min(index * 0.025, 0.12) }}
                                className={`relative shrink-0 overflow-hidden rounded-full border px-2.5 py-1 text-[0.56rem] font-black uppercase tracking-[0.12em] ${isLatest ? 'poker-action-pop ring-2 ring-amber-100/30' : ''} ${classes}`}
                            >
                                {isLatest && <span className="pointer-events-none absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent" />}
                                <span className="relative z-10">
                                    {item.actor}: {item.amount > 0 ? `${item.label} ${formatChipAmount(item.amount)}` : item.label}
                                </span>
                            </motion.span>
                        );
                    })}
                </AnimatePresence>
            </div>
        </div>
    );
}

function latestLegacyActionItem(state, actorRole) {
    return isLatestLegacyAction(state, actorRole) ? latestActionItem(state) : null;
}

function shouldPulsePot(state) {
    const latest = latestActionItem(state);

    return Boolean(latest && Number(latest.amount ?? 0) > 0);
}

function PokerTableAnimationStyles() {
    return (
        <style>{`
            @keyframes pokerActionFlash {
                0% { transform: translateY(0) scale(1); box-shadow: 0 0 0 rgba(16,185,129,0); }
                28% { transform: translateY(-4px) scale(1.015); box-shadow: 0 0 34px rgba(16,185,129,0.34); }
                100% { transform: translateY(0) scale(1); box-shadow: 0 0 0 rgba(16,185,129,0); }
            }

            @keyframes pokerActionPillPop {
                0% { transform: scale(0.88); filter: brightness(0.92); }
                45% { transform: scale(1.08); filter: brightness(1.18); }
                100% { transform: scale(1); filter: brightness(1); }
            }

            @keyframes pokerPotReceive {
                0% { transform: scale(1); }
                35% { transform: scale(1.055); box-shadow: 0 0 42px rgba(251,191,36,0.32); }
                100% { transform: scale(1); }
            }

            @keyframes pokerStreetFade {
                0% { opacity: 0.68; transform: translateY(7px) scale(0.985); }
                100% { opacity: 1; transform: translateY(0) scale(1); }
            }

            @keyframes pokerWinnerGlow {
                0%, 100% { box-shadow: 0 0 28px rgba(251,191,36,0.18); }
                50% { box-shadow: 0 0 54px rgba(251,191,36,0.36); }
            }

            @keyframes pokerTurnLiveGlow {
                0%, 100% { box-shadow: 0 0 22px rgba(16,185,129,0.20), inset 0 0 18px rgba(16,185,129,0.08); }
                50% { box-shadow: 0 0 58px rgba(16,185,129,0.46), inset 0 0 28px rgba(16,185,129,0.14); }
            }

            @keyframes pokerTurnBorderSweep {
                0% { transform: rotate(0deg); opacity: .42; }
                50% { opacity: .95; }
                100% { transform: rotate(360deg); opacity: .42; }
            }

            @keyframes pokerTurnCriticalPulse {
                0%, 100% { transform: scale(1); filter: brightness(1); }
                50% { transform: scale(1.065); filter: brightness(1.22); }
            }

            @keyframes pokerLiveNameGlow {
                0%, 100% { text-shadow: 0 0 0 rgba(110,231,183,0); }
                50% { text-shadow: 0 0 18px rgba(110,231,183,0.82); }
            }

            .poker-action-flash { animation: pokerActionFlash 760ms ease-out both; }
            .poker-action-pop { animation: pokerActionPillPop 420ms cubic-bezier(.2,.9,.3,1.25) both; }
            .poker-pot-receive { animation: pokerPotReceive 820ms ease-out both; }
            .poker-street-transition { animation: pokerStreetFade 520ms ease-out both; }
            .poker-winner-seat { animation: pokerWinnerGlow 1.65s ease-in-out infinite; }
            .poker-live-turn-seat { animation: pokerTurnLiveGlow 1.45s ease-in-out infinite; }
            .poker-turn-critical-pulse { animation: pokerTurnCriticalPulse 720ms ease-in-out infinite; }
            .poker-live-name-glow { animation: pokerLiveNameGlow 1.35s ease-in-out infinite; }
            .poker-live-border-sweep::before {
                content: '';
                position: absolute;
                inset: -42%;
                background: conic-gradient(from 0deg, transparent 0deg, rgba(110,231,183,.0) 46deg, rgba(110,231,183,.52) 90deg, transparent 134deg, transparent 360deg);
                animation: pokerTurnBorderSweep 2.4s linear infinite;
                pointer-events: none;
            }
            @keyframes pokerChipFlightToPot {
                0% { opacity: 0; transform: translate3d(0, 0, 0) scale(.72) rotate(0deg); }
                16% { opacity: 1; }
                70% { opacity: 1; transform: translate3d(36px, -78px, 0) scale(1.04) rotate(220deg); }
                100% { opacity: 0; transform: translate3d(72px, -126px, 0) scale(.64) rotate(380deg); }
            }

            @keyframes pokerAllInBlast {
                0%, 100% { filter: brightness(1); box-shadow: 0 0 0 rgba(251,113,133,0); }
                35% { filter: brightness(1.26); box-shadow: 0 0 48px rgba(251,113,133,.48); }
            }

            @keyframes pokerRaiseImpact {
                0% { transform: translateY(0) scale(1); }
                34% { transform: translateY(-5px) scale(1.022); }
                100% { transform: translateY(0) scale(1); }
            }

            @keyframes pokerFoldOverlay {
                0% { opacity: 0; backdrop-filter: grayscale(0); }
                100% { opacity: 1; backdrop-filter: grayscale(.8); }
            }

            @keyframes pokerCheckRipple {
                0% { opacity: .95; transform: scale(.94); }
                100% { opacity: 0; transform: scale(1.14); }
            }

            @keyframes pokerLiveActionToast {
                0% { opacity: 0; transform: translateY(12px) scale(.97); }
                18% { opacity: 1; transform: translateY(0) scale(1); }
                82% { opacity: 1; transform: translateY(0) scale(1); }
                100% { opacity: .92; transform: translateY(-2px) scale(.995); }
            }

            .poker-action-chip-flight {
                position: absolute;
                display: block;
                width: 1.05rem;
                height: 1.05rem;
                border-radius: 999px;
                border: 3px solid rgba(254,243,199,.9);
                background: radial-gradient(circle at 50% 50%, rgba(254,243,199,.85) 0 18%, rgba(220,38,38,.96) 20% 62%, rgba(127,29,29,.98) 64% 100%);
                box-shadow: 0 10px 18px rgba(0,0,0,.34);
                animation: pokerChipFlightToPot 820ms cubic-bezier(.2,.9,.25,1) both;
            }
            .poker-action-chip-flight-allin { width: 1.18rem; height: 1.18rem; animation-duration: 980ms; }
            .poker-action-chip-flight-source { box-shadow: 0 0 34px rgba(251,191,36,.18), inset 0 0 20px rgba(251,191,36,.08); }
            .poker-action-raise-impact { animation: pokerRaiseImpact 620ms cubic-bezier(.2,.9,.3,1.25) both; }
            .poker-action-allin-blast { animation: pokerAllInBlast 900ms ease-out both; }
            .poker-action-fold-shade { filter: saturate(.76) brightness(.82); }
            .poker-fold-overlay { animation: pokerFoldOverlay 480ms ease-out both; }
            .poker-check-ripple-ring { animation: pokerCheckRipple 760ms ease-out both; }
            .poker-action-check-ripple { box-shadow: 0 0 30px rgba(125,211,252,.20); }
            .poker-live-action-toast { animation: pokerLiveActionToast 2.4s ease-out both; }

            .poker-live-border-sweep > * { position: relative; z-index: 1; }

            @keyframes pokerShowdownWinningGlow {
                0%, 100% {
                    transform: translateY(0) scale(1);
                    box-shadow:
                        0 0 18px rgba(251,191,36,.34),
                        0 0 28px rgba(251,191,36,.22);
                }

                50% {
                    transform: translateY(-3px) scale(1.055);
                    box-shadow:
                        0 0 42px rgba(251,191,36,.78),
                        0 0 68px rgba(251,191,36,.35);
                }
            }

            .poker-showdown-winning-card {
                animation:
                    pokerShowdownWinningGlow 1.35s ease-in-out infinite;
                z-index: 2;
            }

            @media (prefers-reduced-motion: reduce) {
                .poker-action-flash,
                .poker-action-pop,
                .poker-pot-receive,
                .poker-street-transition,
                .poker-winner-seat,
                .poker-live-turn-seat,
                .poker-turn-critical-pulse,
                .poker-live-name-glow,
                .poker-live-border-sweep::before,
                .poker-action-chip-flight,
                .poker-action-raise-impact,
                .poker-action-allin-blast,
                .poker-fold-overlay,
                .poker-check-ripple-ring,
                .poker-live-action-toast {
                    animation: none !important;
                }
            }
        `}</style>
    );
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


function actionVisualTone(label) {
    const tone = actionToneLabel(label);

    const tones = {
        Fold: {
            seatClass: 'poker-action-fold-shade',
            badgeClass: 'border-slate-300/40 bg-slate-950/90 text-slate-100',
            effect: 'fold',
            verb: 'descartou',
            description: 'Cartas protegidas e assento escurecido.',
        },
        Check: {
            seatClass: 'poker-action-check-ripple',
            badgeClass: 'border-sky-100/60 bg-sky-300 text-sky-950',
            effect: 'check',
            verb: 'passou a ação',
            description: 'Sem aposta adicional nesta rodada.',
        },
        Call: {
            seatClass: 'poker-action-chip-flight-source',
            badgeClass: 'border-emerald-100/60 bg-emerald-300 text-emerald-950',
            effect: 'chips',
            verb: 'pagou',
            description: 'Fichas seguem para o centro da mesa.',
        },
        Bet: {
            seatClass: 'poker-action-chip-flight-source',
            badgeClass: 'border-amber-100/60 bg-amber-300 text-amber-950',
            effect: 'chips',
            verb: 'apostou',
            description: 'Aposta adicionada ao pote.',
        },
        Raise: {
            seatClass: 'poker-action-raise-impact',
            badgeClass: 'border-orange-100/70 bg-orange-300 text-orange-950',
            effect: 'chips',
            verb: 'aumentou',
            description: 'Pressão na mesa e fichas ao centro.',
        },
        'All-in': {
            seatClass: 'poker-action-allin-blast',
            badgeClass: 'border-rose-100/70 bg-rose-400 text-rose-950',
            effect: 'allin',
            verb: 'foi all-in',
            description: 'Momento decisivo da mão.',
        },
    };

    return tones[tone] ?? {
        seatClass: 'poker-action-flash',
        badgeClass: 'border-violet-100/50 bg-violet-300 text-violet-950',
        effect: 'pulse',
        verb: 'agiu',
        description: 'Ação executada na mesa.',
    };
}

function actionToastTitle(item) {
    if (!item) {
        return 'Ação executada';
    }

    const tone = actionVisualTone(item.label);
    const amount = Number(item?.amount ?? 0);
    const amountLabel = amount > 0 ? ` ${formatChipAmount(amount)}` : '';

    return `${item.actor} ${tone.verb}${amountLabel}`;
}

function MotionChip({ item, index, allIn = false }) {
    const startX = (index - 2) * 14;
    const startY = 8 + (index % 3) * 6;
    const endX = 76 + (index % 4) * 18;
    const endY = -92 - (index % 3) * 18;

    return (
        <motion.span
            key={`motion-chip-${item.key}-${index}`}
            className={`absolute block rounded-full border-[3px] border-amber-100/90 bg-gradient-to-br from-amber-100 via-rose-500 to-rose-950 shadow-xl shadow-black/35 ${allIn ? 'h-5 w-5' : 'h-4 w-4'}`}
            initial={{ opacity: 0, x: startX, y: startY, scale: 0.55, rotate: 0 }}
            animate={{ opacity: [0, 1, 1, 0], x: endX, y: endY, scale: allIn ? [0.72, 1.28, 1.05, 0.62] : [0.62, 1.08, 0.96, 0.58], rotate: allIn ? 540 : 360 }}
            transition={{ duration: allIn ? 1.1 : 0.85, delay: index * 0.045, ease: [0.16, 1, 0.3, 1] }}
            style={{ left: `${32 + index * 6}%`, bottom: `${16 + (index % 2) * 8}%` }}
        />
    );
}

function ActionEffectOverlay({ item }) {
    if (!item) {
        return null;
    }

    const tone = actionVisualTone(item.label);

    if (tone.effect === 'chips' || tone.effect === 'allin') {
        const chips = tone.effect === 'allin' ? 14 : 7;
        const allIn = tone.effect === 'allin';

        return (
            <AnimatePresence mode="popLayout">
                <motion.div
                    key={`motion-action-${item.key}`}
                    className="pointer-events-none absolute inset-0 z-30 overflow-visible rounded-2xl"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.18 }}
                    aria-hidden="true"
                >
                    {allIn && (
                        <motion.span
                            className="absolute inset-2 rounded-2xl border border-rose-100/40 bg-rose-500/10 shadow-[0_0_55px_rgba(251,113,133,.34)]"
                            initial={{ opacity: 0, scale: 0.88 }}
                            animate={{ opacity: [0, 1, 0.3, 0], scale: [0.88, 1.08, 1.16, 1.22] }}
                            transition={{ duration: 1.05, ease: 'easeOut' }}
                        />
                    )}

                    {Array.from({ length: chips }).map((_, index) => (
                        <MotionChip key={`chip-${item.key}-${index}`} item={item} index={index} allIn={allIn} />
                    ))}
                </motion.div>
            </AnimatePresence>
        );
    }

    if (tone.effect === 'check') {
        return (
            <motion.span
                key={`motion-check-${item.key}`}
                className="pointer-events-none absolute inset-2 z-30 rounded-2xl border border-sky-100/50 bg-sky-300/5"
                initial={{ opacity: 0.9, scale: 0.88 }}
                animate={{ opacity: 0, scale: 1.18 }}
                transition={{ duration: 0.85, ease: 'easeOut' }}
                aria-hidden="true"
            />
        );
    }

    if (tone.effect === 'fold') {
        return (
            <motion.span
                key={`motion-fold-${item.key}`}
                className="pointer-events-none absolute inset-0 z-30 rounded-2xl bg-slate-950/36 backdrop-grayscale"
                initial={{ opacity: 0, x: 0, rotate: 0 }}
                animate={{ opacity: 1, x: [0, -7, 0], rotate: [0, -1.8, 0] }}
                transition={{ duration: 0.56, ease: [0.16, 1, 0.3, 1] }}
                aria-hidden="true"
            />
        );
    }

    return (
        <motion.span
            key={`motion-pulse-${item.key}`}
            className="pointer-events-none absolute inset-1 z-30 rounded-2xl border border-emerald-100/35 bg-emerald-300/5"
            initial={{ opacity: 0, scale: 0.94 }}
            animate={{ opacity: [0, 1, 0], scale: [0.94, 1.04, 1.1] }}
            transition={{ duration: 0.72, ease: 'easeOut' }}
            aria-hidden="true"
        />
    );
}

function LatestActionToast({ state }) {
    const latest = latestActionItem(state);

    if (!latest || state?.isFinished) {
        return null;
    }

    const tone = actionVisualTone(latest.label);

    return (
        <AnimatePresence mode="wait">
            <motion.div
                key={`motion-action-toast-${latest.key}`}
                className="pointer-events-none mx-auto w-full max-w-2xl rounded-[1.35rem] border border-white/10 bg-slate-950/90 p-2 shadow-2xl shadow-black/55 backdrop-blur"
                initial={{ opacity: 0, y: 20, scale: 0.965 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={{ opacity: 0, y: -10, scale: 0.985 }}
                transition={{ type: 'spring', stiffness: 420, damping: 28, mass: 0.72 }}
            >
                <motion.div
                    className="grid gap-2 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center"
                    initial={{ filter: 'brightness(1)' }}
                    animate={{ filter: ['brightness(1)', 'brightness(1.24)', 'brightness(1)'] }}
                    transition={{ duration: 0.7, ease: 'easeOut' }}
                >
                    <motion.span
                        className={`rounded-full border px-3 py-1 text-[0.58rem] font-black uppercase tracking-[0.18em] ${tone.badgeClass}`}
                        initial={{ scale: 0.82 }}
                        animate={{ scale: [0.82, 1.1, 1] }}
                        transition={{ duration: 0.42, ease: [0.16, 1, 0.3, 1] }}
                    >
                        Ação executada
                    </motion.span>
                    <div className="min-w-0 text-center sm:text-left">
                        <strong className="block truncate text-sm font-black text-white" title={actionToastTitle(latest)}>{actionToastTitle(latest)}</strong>
                        <p className="text-[0.68rem] font-bold text-slate-200/75">{tone.description}</p>
                    </div>
                    <motion.span
                        className="hidden rounded-full border border-amber-200/25 bg-amber-300/10 px-3 py-1 text-[0.58rem] font-black uppercase tracking-[0.14em] text-amber-100 sm:inline-flex"
                        initial={{ opacity: 0, x: 8 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: 0.12, duration: 0.28 }}
                    >
                        HUD atualizando
                    </motion.span>
                </motion.div>
            </motion.div>
        </AnimatePresence>
    );
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
                key: `${action?.id ?? action?.seatNumber ?? action?.tablePlayerId ?? actionActorRole(action) ?? 'action'}-${action?.action ?? action?.type ?? label}-${index}`,
                seatNumber: action?.seatNumber,
                actorRole: actionActorRole(action),
                actor: actionActorLabel(action),
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

function quickSeatRailLabel(player, state) {
    const seatNumber = Number(player?.seatNumber ?? 0);
    const currentTurnSeat = multiSeatCurrentTurnSeat(state);
    const hasFolded = Boolean(player?.hasFolded) || player?.status === 'folded';
    const isCurrentTurn = currentTurnSeat !== null && seatNumber === currentTurnSeat && !state?.isFinished;

    return multiSeatLastActionLabel(state, player, hasFolded, isCurrentTurn);
}

function quickSeatRailClasses(player, state) {
    const seatNumber = Number(player?.seatNumber ?? 0);
    const currentTurnSeat = multiSeatCurrentTurnSeat(state);
    const isCurrentTurn = currentTurnSeat !== null && seatNumber === currentTurnSeat && !state?.isFinished;
    const hasFolded = Boolean(player?.hasFolded) || player?.status === 'folded';
    const isWinner = isMultiSeatWinner(state, seatNumber);

    if (isWinner) {
        return 'border-amber-100/60 bg-amber-300/20 shadow-amber-950/25';
    }

    if (isCurrentTurn) {
        return 'border-emerald-100/50 bg-emerald-300/15 shadow-emerald-950/25';
    }

    if (hasFolded || Number(player?.stack ?? 0) <= 0) {
        return 'border-slate-500/20 bg-slate-950/45 opacity-75';
    }

    return 'border-white/10 bg-white/[0.04]';
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


function narrativeStageLabel(state) {
    if (state?.isFinished) {
        return isMultiSeatSplitPot(state) ? 'Pote dividido' : 'Showdown decidido';
    }

    const street = String(state?.street ?? '').toLowerCase();
    const labels = {
        pre_flop: 'Pré-flop em andamento',
        preflop: 'Pré-flop em andamento',
        flop: 'Flop aberto',
        turn: 'Turn revelado',
        river: 'River revelado',
        showdown: 'Showdown',
        waiting: 'Aguardando próxima mão',
    };

    return labels[street] ?? 'Mão em andamento';
}

function narrativeStageDescription(state, currentTurnPlayer) {
    if (state?.isFinished) {
        if (isMultiSeatSplitPot(state)) {
            return `O pote foi dividido entre ${multiSeatWinnerSeats(state).length} jogadores. Revise as mãos e inicie a próxima rodada.`;
        }

        const winner = multiSeatPlayers(state).find((player) => isMultiSeatWinner(state, player?.seatNumber));
        const winnerName = winner?.nickname ?? winner?.displayName ?? (winner?.seatNumber ? `Assento ${winner.seatNumber}` : null);

        return winnerName
            ? `${winnerName} levou o pote. A mesa está pronta para conferir o showdown antes da próxima mão.`
            : 'Showdown concluído. Confira o resultado e inicie uma nova mão quando estiver pronto.';
    }

    if (currentTurnPlayer?.nickname || currentTurnPlayer?.displayName) {
        return `${currentTurnPlayer.nickname ?? currentTurnPlayer.displayName} está com a decisão da rodada.`;
    }

    return state?.currentTurn?.message ?? 'A mesa está sincronizando a próxima ação.';
}

function narrativeWinnerNames(state) {
    const winners = multiSeatWinnerSeats(state);

    if (winners.length === 0) {
        return 'A definir';
    }

    return multiSeatPlayers(state)
        .filter((player) => winners.includes(Number(player?.seatNumber ?? 0)))
        .map((player) => player?.nickname ?? player?.displayName ?? `Assento ${player.seatNumber}`)
        .join(' · ') || 'A definir';
}

function narrativeTimelineItems(state) {
    const currentStreet = String(state?.street ?? '').toLowerCase();
    const order = ['pre_flop', 'flop', 'turn', 'river', 'showdown'];
    const safeStreet = currentStreet === 'preflop' ? 'pre_flop' : currentStreet;
    const currentIndex = Math.max(0, order.indexOf(safeStreet));

    return [
        { key: 'pre_flop', label: 'Pré-flop' },
        { key: 'flop', label: 'Flop' },
        { key: 'turn', label: 'Turn' },
        { key: 'river', label: 'River' },
        { key: 'showdown', label: 'Showdown' },
    ].map((item, index) => ({
        ...item,
        active: !state?.isFinished && item.key === safeStreet,
        done: Boolean(state?.isFinished) || index < currentIndex,
    }));
}

function ShowdownPremiumPanel({ state }) {
    if (!state?.isFinished) {
        return null;
    }

    const winners = multiSeatPlayers(state).filter((player) => isMultiSeatWinner(state, player?.seatNumber));
    const latestActions = actionTimelineItems(state).slice(0, 4);
    const winnerNames = winners
        .map((player) => player?.nickname ?? player?.displayName ?? `Assento ${player?.seatNumber}`)
        .filter(Boolean);
    const title = isMultiSeatSplitPot(state)
        ? `Pote dividido entre ${winnerNames.length || multiSeatWinnerSeats(state).length} jogadores`
        : `${winnerNames[0] ?? 'Vencedor definido'} venceu a mão`;
    const subtitle = isMultiSeatSplitPot(state)
        ? 'Showdown resolvido com empate técnico. Cada vencedor recebe sua parte do pote.'
        : 'Showdown resolvido. Confira as cartas e inicie a próxima mão quando disponível.';

    return (
        <section className="poker-soft-enter rounded-[1.35rem] border border-amber-200/25 bg-gradient-to-r from-amber-300/15 via-black/30 to-emerald-300/10 p-3 shadow-2xl shadow-black/35">
            <div className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                <div className="min-w-0">
                    <p className="text-[0.58rem] font-black uppercase tracking-[0.24em] text-amber-100/75">Showdown</p>
                    <strong className="block truncate text-xl font-black text-white" title={title}>{title}</strong>
                    <p className="mt-1 text-sm font-semibold leading-snug text-emerald-100/80">{subtitle}</p>
                </div>

                <div className="flex flex-wrap gap-2 lg:justify-end">
                    <span className="rounded-full border border-amber-100/45 bg-amber-300 px-3 py-1 text-[0.62rem] font-black uppercase tracking-[0.16em] text-amber-950">
                        Pote {formatChipAmount(state?.pot)}
                    </span>
                    {winnerNames.map((name) => (
                        <span key={`showdown-winner-${name}`} className="rounded-full border border-emerald-100/35 bg-emerald-300/15 px-3 py-1 text-[0.62rem] font-black uppercase tracking-[0.16em] text-emerald-100">
                            {isMultiSeatSplitPot(state) ? 'Empate' : 'Vencedor'} · {name}
                        </span>
                    ))}
                </div>
            </div>

            {latestActions.length > 0 && (
                <div className="mt-3 flex max-w-full gap-1.5 overflow-x-auto rounded-full border border-white/10 bg-slate-950/40 px-2 py-2" aria-label="Ritmo final da mão">
                    {latestActions.map((item) => (
                        <span key={`showdown-action-${item.key}`} className={`shrink-0 rounded-full border px-2.5 py-1 text-[0.56rem] font-black uppercase tracking-[0.12em] ${multiSeatActionPillClasses(item.label, false, item.label === 'Fold')}`}>
                            {item.actor}: {item.amount > 0 ? `${item.label} ${formatChipAmount(item.amount)}` : item.label}
                        </span>
                    ))}
                </div>
            )}
        </section>
    );
}



function showdownCinematicWinnerLabel(state) {
    if (!state?.isFinished) {
        return 'Aguardando resultado';
    }

    if (isMultiSeatLayout(state)) {
        const winners = multiSeatPlayers(state).filter((player) => isMultiSeatWinner(state, player?.seatNumber));
        const winnerNames = winners
            .map((player) => player?.nickname ?? player?.displayName ?? `Assento ${player?.seatNumber}`)
            .filter(Boolean);

        if (isMultiSeatSplitPot(state)) {
            return winnerNames.length > 0 ? `Pote dividido: ${winnerNames.join(' · ')}` : 'Pote dividido';
        }

        return winnerNames[0] ? `${winnerNames[0]} levou o pote` : 'Vencedor definido';
    }

    const winner = winnerPlayer(state);

    if (winner === 'tie') {
        return 'Pote dividido no showdown';
    }

    if (winner === 'player') {
        return 'Você levou o pote';
    }

    if (winner === 'opponent') {
        const opponentName = state?.playersContext?.opponents?.[0]?.nickname ?? 'Adversário';
        return `${opponentName} levou o pote`;
    }

    return 'Resultado definido';
}

function showdownCinematicBestHandLabel(state) {
    if (isMultiSeatLayout(state)) {
        const winners = multiSeatPlayers(state).filter((player) => isMultiSeatWinner(state, player?.seatNumber));
        const winnerBestHand = winners.find((player) => player?.bestHand?.name)?.bestHand?.name;

        return winnerBestHand ?? state?.bestHand?.name ?? 'Melhor combinação revelada';
    }

    if (winnerPlayer(state) === 'opponent') {
        return state?.opponentBestHand?.name ?? 'Melhor combinação revelada';
    }

    return state?.bestHand?.name ?? 'Melhor combinação revelada';
}

function ShowdownCinematicRibbon({ state }) {
    if (!state?.isFinished) {
        return null;
    }

    const winnerLabel = showdownCinematicWinnerLabel(state);
    const bestHandLabel = showdownCinematicBestHandLabel(state);
    const potAmount = formatChipAmount(state?.pot);
    const cardsRevealed = isMultiSeatLayout(state)
        ? isMultiSeatShowdownResolved(state)
        : Boolean(state?.isFinished);

    return (
        <AnimatePresence>
            <motion.section
                key={`showdown-cinematic-${state?.street ?? 'showdown'}-${state?.pot ?? 0}`}
                initial={{ opacity: 0, y: 18, scale: 0.98 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={{ opacity: 0, y: -10, scale: 0.98 }}
                transition={{ type: 'spring', stiffness: 260, damping: 24 }}
                className="relative overflow-hidden rounded-[1.45rem] border border-amber-200/35 bg-gradient-to-r from-slate-950/92 via-amber-950/40 to-emerald-950/80 p-3 shadow-2xl shadow-black/45"
            >
                <motion.div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_22%_22%,rgba(251,191,36,0.22),transparent_30%),radial-gradient(circle_at_78%_8%,rgba(16,185,129,0.18),transparent_32%)]"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: 0.08, duration: 0.45 }}
                />

                <div className="relative z-10 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                    <div className="min-w-0">
                        <motion.p
                            initial={{ opacity: 0, letterSpacing: '0.1em' }}
                            animate={{ opacity: 1, letterSpacing: '0.28em' }}
                            transition={{ duration: 0.35 }}
                            className="text-[0.58rem] font-black uppercase text-amber-100/75"
                        >
                            Showdown cinematográfico
                        </motion.p>
                        <motion.strong
                            initial={{ opacity: 0, x: -12 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ delay: 0.08, duration: 0.3 }}
                            className="block truncate text-lg font-black text-white sm:text-2xl"
                            title={winnerLabel}
                        >
                            {winnerLabel}
                        </motion.strong>
                        <p className="mt-1 text-xs font-bold text-emerald-100/80 sm:text-sm">
                            {bestHandLabel} · Pote {potAmount}
                        </p>
                    </div>

                    <div className="grid grid-cols-3 gap-1.5 text-center text-[0.55rem] font-black uppercase tracking-[0.13em] text-slate-100 sm:gap-2">
                        {[
                            { label: 'Cartas', value: cardsRevealed ? 'Abertas' : 'Ocultas', tone: cardsRevealed ? 'border-emerald-100/40 bg-emerald-300/15 text-emerald-100' : 'border-slate-300/25 bg-white/10 text-slate-200' },
                            { label: 'Pote', value: potAmount, tone: 'border-amber-100/40 bg-amber-300/15 text-amber-100' },
                            { label: 'Status', value: 'Resolvido', tone: 'border-sky-100/35 bg-sky-300/10 text-sky-100' },
                        ].map((item, index) => (
                            <motion.div
                                key={item.label}
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.12 + index * 0.06, duration: 0.24 }}
                                className={`rounded-2xl border px-2 py-2 shadow-lg shadow-black/20 ${item.tone}`}
                            >
                                <span className="block text-[0.5rem] opacity-75">{item.label}</span>
                                <strong className="mt-0.5 block truncate text-[0.62rem] sm:text-xs" title={String(item.value)}>{item.value}</strong>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </motion.section>
        </AnimatePresence>
    );
}

function normalizeCardCollection(cards) {
    return Array.isArray(cards) ? cards.filter(Boolean) : [];
}

function multiSeatShowdownCardsForPlayer(player, state, isCurrentUserSeat, opponentsCount = 0) {
    const seatNumber = Number(player?.seatNumber ?? 0);
    const playerCards = normalizeCardCollection(player?.cards);
    const playerShowdownCards = normalizeCardCollection(player?.showdownCards);
    const playerHoleCards = normalizeCardCollection(player?.holeCards);
    const playerHandCards = normalizeCardCollection(player?.handCards);
    const playerPrivateCards = normalizeCardCollection(player?.privateCards);
    const showdownCardsBySeat = state?.multiSeat?.showdownCardsBySeat ?? {};
    const publicSeatCards = normalizeCardCollection(
        showdownCardsBySeat?.[seatNumber]
            ?? showdownCardsBySeat?.[String(seatNumber)]
            ?? playerShowdownCards,
    );

    if (isMultiSeatShowdownResolved(state) && publicSeatCards.length > 0) {
        return publicSeatCards.slice(0, 2);
    }

    if (isCurrentUserSeat) {
        const currentCards = normalizeCardCollection(state?.playerCards);

        return currentCards.length > 0
            ? currentCards
            : [...playerCards, ...playerShowdownCards, ...playerHoleCards, ...playerHandCards, ...playerPrivateCards].slice(0, 2);
    }

    const directCards = [...playerCards, ...playerShowdownCards, ...playerHoleCards, ...playerHandCards, ...playerPrivateCards].slice(0, 2);

    if (directCards.length > 0) {
        return directCards;
    }

    const legacyOpponentCards = normalizeCardCollection(state?.opponentCards);

    if (isMultiSeatShowdownResolved(state) && opponentsCount === 1 && legacyOpponentCards.length > 0) {
        return legacyOpponentCards.slice(0, 2);
    }

    return [];
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


function tournamentHudMetrics(state, currentPlayer = null) {
    const runtime = state?.tournamentRuntime;

    if (!runtime) {
        return null;
    }

    const players = multiSeatPlayers(state);
    const activePlayers = players.filter((player) => Number(player?.stack ?? 0) > 0 && !Boolean(player?.hasFolded));
    const stackPlayers = players.filter((player) => Number(player?.stack ?? 0) > 0);
    const currentSmallBlind = Number(runtime?.smallBlind ?? state?.smallBlind ?? 0);
    const currentBigBlind = Number(runtime?.bigBlind ?? state?.bigBlind ?? 0);
    const nextSmallBlind = Number(runtime?.nextSmallBlind ?? runtime?.nextLevelSmallBlind ?? state?.nextSmallBlind ?? 0) || (currentSmallBlind > 0 ? currentSmallBlind * 2 : 0);
    const nextBigBlind = Number(runtime?.nextBigBlind ?? runtime?.nextLevelBigBlind ?? state?.nextBigBlind ?? 0) || (currentBigBlind > 0 ? currentBigBlind * 2 : 0);
    const activeCount = Number(runtime?.activePlayers ?? 0) || activePlayers.length || stackPlayers.length || Number(runtime?.syncedPlayers ?? 0) || players.length;
    const totalStack = stackPlayers.reduce((sum, player) => sum + Number(player?.stack ?? 0), 0);
    const averageStack = activeCount > 0 ? Math.round(totalStack / activeCount) : 0;
    const averageStackInBb = currentBigBlind > 0 ? Math.max(1, Math.round(averageStack / currentBigBlind)) : 0;
    const currentSeat = Number(currentPlayer?.seatNumber ?? currentUserSeatNumber(state) ?? 0);
    const rankedSeats = [...stackPlayers]
        .sort((left, right) => Number(right?.stack ?? 0) - Number(left?.stack ?? 0))
        .map((player) => Number(player?.seatNumber ?? 0));
    const positionIndex = currentSeat > 0 ? rankedSeats.indexOf(currentSeat) : -1;
    const positionLabel = positionIndex >= 0 ? `#${positionIndex + 1}` : '—';
    const level = Number(runtime?.blindLevel ?? runtime?.level ?? 1) || 1;

    return {
        name: runtime?.name ?? 'Torneio real',
        status: runtime?.canStartNextHand ? 'próxima mão pronta' : runtime?.isHandFinished ? 'showdown' : 'ao vivo',
        level,
        blinds: `${formatChipAmount(currentSmallBlind)} / ${formatChipAmount(currentBigBlind)}`,
        nextBlinds: `${formatChipAmount(nextSmallBlind)} / ${formatChipAmount(nextBigBlind)}`,
        remaining: activeCount > 0 ? String(activeCount) : '—',
        averageStack: averageStackInBb > 0 ? `${averageStackInBb} BB` : (averageStack > 0 ? formatChipAmount(averageStack) : '—'),
        position: positionLabel,
    };
}

function TournamentLiveHud({ state, currentPlayer = null }) {
    const metrics = tournamentHudMetrics(state, currentPlayer);

    if (!metrics) {
        return null;
    }

    const cards = [
        { label: 'BLINDS', value: metrics.blinds, hint: `Nível ${metrics.level}` },
        { label: 'PRÓXIMO', value: metrics.nextBlinds, hint: 'Próximo nível' },
        { label: 'RESTANTES', value: metrics.remaining, hint: 'Jogadores ativos' },
        { label: 'STACK MÉDIA', value: metrics.averageStack, hint: 'Média em BB' },
        { label: 'POSIÇÃO', value: metrics.position, hint: 'Seu stack' },
    ];

    return (
        <section className="relative overflow-hidden rounded-[1.35rem] border border-amber-200/20 bg-gradient-to-br from-slate-950/82 via-emerald-950/58 to-amber-950/25 p-3 shadow-2xl shadow-black/35 backdrop-blur">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(251,191,36,0.18),transparent_30%),radial-gradient(circle_at_95%_20%,rgba(16,185,129,0.16),transparent_34%)]" />
            <div className="relative z-10 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div className="min-w-0">
                    <p className="text-[0.58rem] font-black uppercase tracking-[0.24em] text-amber-100/75">HUD de torneio</p>
                    <div className="mt-1 flex flex-wrap items-center gap-2">
                        <strong className="truncate text-sm font-black text-white sm:text-base" title={metrics.name}>{metrics.name}</strong>
                        <span className="rounded-full border border-emerald-100/25 bg-emerald-300/12 px-2.5 py-1 text-[0.56rem] font-black uppercase tracking-[0.16em] text-emerald-100">
                            {metrics.status}
                        </span>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-2 sm:grid-cols-5 lg:min-w-[560px]">
                    {cards.map((card) => (
                        <div key={card.label} className="rounded-2xl border border-white/10 bg-black/32 px-3 py-2 shadow-inner shadow-black/20">
                            <span className="block text-[0.52rem] font-black uppercase tracking-[0.18em] text-slate-400">{card.label}</span>
                            <strong className="mt-1 block text-sm font-black text-white">{card.value}</strong>
                            <span className="mt-0.5 block truncate text-[0.56rem] font-bold uppercase tracking-[0.1em] text-amber-100/55">{card.hint}</span>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function liveTurnTimerTone(timer) {
    const percentage = Number(timer?.percentage ?? 0);

    if (timer?.isExpired || percentage < 20) {
        return {
            name: 'danger',
            text: 'text-rose-100',
            subtleText: 'text-rose-100/75',
            ring: 'border-rose-200/70 bg-rose-400/20 shadow-rose-950/45',
            fill: 'bg-rose-300',
            track: 'from-rose-500/85 via-rose-300/70 to-rose-100/60',
            glow: 'shadow-[0_0_46px_rgba(251,113,133,0.40)]',
            label: 'Decisão urgente',
        };
    }

    if (percentage <= 50) {
        return {
            name: 'warning',
            text: 'text-amber-100',
            subtleText: 'text-amber-100/75',
            ring: 'border-amber-200/70 bg-amber-300/20 shadow-amber-950/40',
            fill: 'bg-amber-300',
            track: 'from-amber-400/85 via-orange-300/70 to-amber-100/60',
            glow: 'shadow-[0_0_42px_rgba(251,191,36,0.34)]',
            label: 'Tempo em atenção',
        };
    }

    return {
        name: 'safe',
        text: 'text-emerald-100',
        subtleText: 'text-emerald-100/75',
        ring: 'border-emerald-200/65 bg-emerald-300/15 shadow-emerald-950/35',
        fill: 'bg-emerald-300',
        track: 'from-emerald-400/85 via-lime-300/70 to-emerald-100/60',
        glow: 'shadow-[0_0_38px_rgba(16,185,129,0.30)]',
        label: 'Tempo confortável',
    };
}

function LiveTurnTimerBadge({ timer, compact = false }) {
    if (!timer) {
        return null;
    }

    const tone = liveTurnTimerTone(timer);
    const percentage = Math.max(0, Math.min(100, Number(timer?.percentage ?? 0)));
    const seconds = Math.max(0, Number(timer?.secondsRemaining ?? 0));
    const isFinalSeconds = !timer?.isExpired && seconds <= 5;
    const conicStyle = {
        background: `conic-gradient(currentColor ${percentage * 3.6}deg, rgba(15,23,42,0.84) 0deg)`,
    };

    return (
        <div className={[compact ? 'w-full' : 'w-full max-w-[13rem]', 'rounded-2xl border border-white/10 bg-slate-950/72 p-2 shadow-2xl shadow-black/35 backdrop-blur'].join(' ')}>
            <div className="flex items-center gap-2">
                <div
                    className={[
                        'relative grid shrink-0 place-items-center rounded-full text-emerald-300 transition duration-500',
                        compact ? 'h-14 w-14' : 'h-20 w-20',
                        tone.glow,
                        isFinalSeconds ? 'poker-turn-critical-pulse' : '',
                    ].join(' ')}
                    style={conicStyle}
                    aria-label={`${seconds} segundos restantes`}
                >
                    <div className="absolute inset-1 rounded-full bg-slate-950" />
                    <div className={["relative grid place-items-center rounded-full border font-black", compact ? 'h-11 w-11 text-lg' : 'h-16 w-16 text-2xl', tone.ring, tone.text].join(' ')}>
                        {seconds}s
                    </div>
                </div>

                <div className="min-w-0 flex-1">
                    <p className={["text-[0.55rem] font-black uppercase tracking-[0.18em]", tone.subtleText].join(' ')}>{tone.label}</p>
                    <strong className="mt-0.5 block truncate text-[0.78rem] font-black text-white">Timer da jogada</strong>
                    <div className="mt-2 h-2 overflow-hidden rounded-full border border-white/10 bg-black/45 p-0.5">
                        <div className={["h-full rounded-full transition-all duration-500", tone.fill].join(' ')} style={{ width: `${percentage}%` }} />
                    </div>
                </div>
            </div>
        </div>
    );
}


function isCurrentUserTurn(state) {
    if (!state || state?.isFinished) {
        return false;
    }

    if (Boolean(state?.canAct) || Boolean(state?.currentTurn?.canAct)) {
        return true;
    }

    if (isMultiSeatLayout(state)) {
        const currentSeat = currentUserSeatNumber(state);
        const currentTurnSeat = multiSeatCurrentTurnSeat(state);
        const currentPlayer = multiSeatPlayers(state).find((player) => Number(player?.seatNumber ?? 0) === currentSeat);

        return currentSeat > 0
            && currentTurnSeat !== null
            && currentSeat === currentTurnSeat
            && !Boolean(currentPlayer?.isBot);
    }

    const role = String(state?.currentTurn?.actorRole ?? state?.currentTurn?.player ?? '').toLowerCase();
    const label = String(state?.currentTurn?.actorLabel ?? '').toLowerCase();

    return role === 'player' || label.includes('você') || label.includes('voce');
}

function YourTurnOverlay({ state, timer }) {
    if (!isCurrentUserTurn(state)) {
        return null;
    }

    const seconds = Math.max(0, Number(timer?.secondsRemaining ?? state?.turnTimer?.secondsRemaining ?? state?.turnTimer?.secondsTotal ?? 0));
    const percentage = Math.max(0, Math.min(100, Number(timer?.percentage ?? 100)));
    const tone = liveTurnTimerTone(timer ?? { percentage, secondsRemaining: seconds });
    const isCritical = seconds > 0 && seconds <= 5;
    const secondsLabel = seconds > 0 ? `${seconds}s restantes` : 'decida agora';

    return (
        <AnimatePresence>
            <motion.div
                key={`your-turn-${state?.turnTimer?.expiresAt ?? state?.street ?? 'live'}`}
                className="pointer-events-none absolute inset-x-2 top-16 z-50 hidden justify-center md:flex"
                initial={{ opacity: 0, y: -18, scale: 0.96 }}
                animate={{ opacity: 1, y: 0, scale: isCritical ? [1, 1.025, 1] : 1 }}
                exit={{ opacity: 0, y: -10, scale: 0.98 }}
                transition={{ duration: 0.28, ease: [0.16, 1, 0.3, 1], repeat: isCritical ? Infinity : 0, repeatDelay: 0.48 }}
            >
                <div className="relative w-full max-w-xl overflow-hidden rounded-[1.75rem] border border-emerald-100/40 bg-slate-950/88 px-6 py-4 text-center shadow-[0_0_70px_rgba(16,185,129,0.34)] backdrop-blur-xl">
                    <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(16,185,129,0.26),transparent_58%),linear-gradient(90deg,transparent,rgba(255,255,255,0.12),transparent)]" />
                    <div className="relative z-10">
                        <p className="text-[0.62rem] font-black uppercase tracking-[0.38em] text-emerald-100/75">━━━━━━━━━━</p>
                        <strong className="mt-1 block text-3xl font-black uppercase tracking-[0.28em] text-white drop-shadow-[0_0_22px_rgba(110,231,183,0.75)]">Sua vez</strong>
                        <p className={["mt-1 text-sm font-black uppercase tracking-[0.24em]", tone.subtleText].join(' ')}>{secondsLabel}</p>
                        <div className="mx-auto mt-3 h-2 max-w-sm overflow-hidden rounded-full border border-white/10 bg-black/50 p-0.5">
                            <div className={["h-full rounded-full transition-all duration-500", tone.fill].join(' ')} style={{ width: `${percentage}%` }} />
                        </div>
                        <p className="mt-2 text-[0.62rem] font-black uppercase tracking-[0.38em] text-emerald-100/75">━━━━━━━━━━</p>
                    </div>
                </div>
            </motion.div>

            <motion.div
                key={`your-turn-mobile-${state?.turnTimer?.expiresAt ?? state?.street ?? 'live'}`}
                className="pointer-events-none fixed inset-x-3 bottom-[5.75rem] z-[70] md:hidden"
                initial={{ opacity: 0, y: 18, scale: 0.97 }}
                animate={{ opacity: 1, y: 0, scale: isCritical ? [1, 1.02, 1] : 1 }}
                exit={{ opacity: 0, y: 10, scale: 0.98 }}
                transition={{ duration: 0.25, ease: [0.16, 1, 0.3, 1], repeat: isCritical ? Infinity : 0, repeatDelay: 0.55 }}
            >
                <div className="mx-auto max-w-md rounded-2xl border border-emerald-100/45 bg-slate-950/94 px-4 py-3 text-center shadow-[0_0_45px_rgba(16,185,129,0.32)] backdrop-blur-xl">
                    <strong className="block text-lg font-black uppercase tracking-[0.26em] text-white">Sua vez</strong>
                    <span className={["mt-1 block text-xs font-black uppercase tracking-[0.2em]", tone.subtleText].join(' ')}>{secondsLabel}</span>
                    <div className="mt-2 h-2 overflow-hidden rounded-full border border-white/10 bg-black/50 p-0.5">
                        <div className={["h-full rounded-full transition-all duration-500", tone.fill].join(' ')} style={{ width: `${percentage}%` }} />
                    </div>
                </div>
            </motion.div>
        </AnimatePresence>
    );
}

function seatLiveStateLabel(player, isCurrentTurn, hasFolded, isAllIn) {
    if (hasFolded) {
        return 'Fold';
    }

    if (isAllIn) {
        return 'All-in';
    }

    if (isCurrentTurn) {
        return 'Ativo';
    }

    if (Boolean(player?.isDisconnected) || player?.status === 'disconnected') {
        return 'Desconectado';
    }

    return 'Aguardando';
}

function seatLiveStateClasses(label) {
    const normalized = String(label ?? '').toLowerCase();

    if (normalized.includes('ativo')) {
        return 'border-emerald-100/55 bg-emerald-300 text-emerald-950 shadow-emerald-950/25';
    }

    if (normalized.includes('all')) {
        return 'border-rose-100/60 bg-rose-400 text-rose-950 shadow-rose-950/25';
    }

    if (normalized.includes('fold')) {
        return 'border-slate-400/25 bg-slate-950/80 text-slate-200';
    }

    if (normalized.includes('desconectado')) {
        return 'border-orange-100/45 bg-orange-300/20 text-orange-100';
    }

    return 'border-white/15 bg-white/[0.08] text-slate-200';
}

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


function premiumActionControlItems(state) {
    const canAct = Boolean(state?.canAct) || Boolean(state?.currentTurn?.canAct);
    const callAmount = Number(state?.callAmount ?? state?.toCall ?? state?.currentBetToCall ?? 0);
    const minimumRaise = Number(state?.minimumRaiseTo ?? state?.minimumRaise ?? state?.minRaise ?? 0);
    const maximumRaise = Number(state?.maximumRaiseTo ?? state?.maximumRaise ?? state?.maxRaise ?? state?.playerStack ?? 0);
    const canCheck = Boolean(state?.canCheck) || callAmount <= 0;
    const canCall = Boolean(state?.canCall) || callAmount > 0;
    const canRaise = Boolean(state?.canRaise) || maximumRaise > minimumRaise;

    return [
        {
            key: 'fold',
            label: 'Fold',
            helper: 'Sair da mão',
            enabled: canAct,
            className: 'border-rose-200/35 bg-rose-400/10 text-rose-100',
        },
        {
            key: canCheck ? 'check' : 'call',
            label: canCheck ? 'Check' : 'Call',
            helper: canCheck ? 'Passar' : formatChipAmount(callAmount),
            enabled: canAct && (canCheck || canCall),
            className: 'border-emerald-200/35 bg-emerald-300/12 text-emerald-100',
        },
        {
            key: 'raise',
            label: 'Raise',
            helper: minimumRaise > 0 ? `mín. ${formatChipAmount(minimumRaise)}` : 'Aumentar',
            enabled: canAct && canRaise,
            className: 'border-amber-200/40 bg-amber-300/15 text-amber-100',
        },
        {
            key: 'all-in',
            label: 'All-in',
            helper: maximumRaise > 0 ? formatChipAmount(maximumRaise) : 'Tudo',
            enabled: canAct && maximumRaise > 0,
            className: 'border-fuchsia-200/35 bg-fuchsia-400/12 text-fuchsia-100',
        },
    ];
}


function MobileTableStickyStatus({ state }) {
    const latest = latestActionItem(state);
    const turnLabel = state?.isFinished ? 'Mão encerrada' : currentTurnLabel(state);
    const timer = usePokerTurnTimer(state?.turnTimer);
    const seconds = Math.max(0, Number(timer?.secondsRemaining ?? state?.turnTimer?.secondsRemaining ?? state?.turnTimer?.secondsTotal ?? 0));
    const percentage = Math.max(0, Math.min(100, Number(timer?.percentage ?? 100)));
    const tone = liveTurnTimerTone(timer ?? { percentage, secondsRemaining: seconds });
    const hasActiveTimer = !state?.isFinished && seconds > 0;

    return (
        <div className="sticky top-2 z-40 -mx-1 rounded-[1.25rem] border border-amber-200/25 bg-slate-950/94 p-2 shadow-2xl shadow-black/55 backdrop-blur-xl xl:hidden" aria-label="Resumo móvel premium da mesa">
            <div className="flex items-center gap-2">
                <div className="relative grid h-14 w-14 shrink-0 place-items-center rounded-full border border-amber-100/25 bg-black/45 shadow-inner shadow-black/50">
                    <div className={['absolute inset-1 rounded-full border-4 border-slate-800', tone.ring].join(' ')} style={{ opacity: hasActiveTimer ? 1 : 0.45 }} />
                    <strong className="relative z-10 text-sm font-black text-white">{hasActiveTimer ? seconds : '—'}</strong>
                    <span className="absolute bottom-1 text-[0.44rem] font-black uppercase tracking-[0.14em] text-amber-100/65">timer</span>
                </div>

                <div className="min-w-0 flex-1">
                    <div className="grid grid-cols-3 gap-1.5 text-center">
                        <div className="rounded-xl border border-amber-200/15 bg-amber-300/10 px-2 py-1.5">
                            <span className="block text-[0.5rem] font-black uppercase tracking-[0.14em] text-amber-100/70">Turno</span>
                            <strong className="block truncate text-[0.68rem] font-black text-white">{turnLabel}</strong>
                        </div>
                        <div className="rounded-xl border border-emerald-200/15 bg-emerald-300/10 px-2 py-1.5">
                            <span className="block text-[0.5rem] font-black uppercase tracking-[0.14em] text-emerald-100/70">Pote</span>
                            <strong className="block truncate text-[0.68rem] font-black text-white">{formatChipAmount(state?.pot)}</strong>
                        </div>
                        <div className="rounded-xl border border-sky-200/15 bg-sky-300/10 px-2 py-1.5">
                            <span className="block text-[0.5rem] font-black uppercase tracking-[0.14em] text-sky-100/70">Street</span>
                            <strong className="block truncate text-[0.68rem] font-black text-white">{narrativeStageLabel(state)}</strong>
                        </div>
                    </div>

                    {hasActiveTimer && (
                        <div className="mt-1.5 h-1.5 overflow-hidden rounded-full border border-white/10 bg-black/45 p-0.5">
                            <div className={['h-full rounded-full transition-all duration-500', tone.fill].join(' ')} style={{ width: `${percentage}%` }} />
                        </div>
                    )}
                </div>
            </div>

            {latest && (
                <div className="mt-1.5 truncate rounded-full border border-white/10 bg-black/35 px-3 py-1 text-center text-[0.58rem] font-black uppercase tracking-[0.11em] text-amber-100">
                    Última: {latest.actor} · {latest.amount > 0 ? `${latest.label} ${formatChipAmount(latest.amount)}` : latest.label}
                </div>
            )}
        </div>
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
