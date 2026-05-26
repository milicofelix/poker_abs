import React, { useEffect, useState } from 'react';
import { AnimatePresence, motion } from 'motion/react';
import {
    actionTimelineItems,
    actionToastTitle,
    actionVisualTone,
    latestActionAnimationKey,
    latestActionItem,
    multiSeatActionPillClasses,
    shouldPulsePot,
} from '@/features/poker/utils/tableActions';
import { chipAmountParts, formatChipAmount } from '@/features/poker/utils/tableState';

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

export function LivePotDisplay({ state, compact = false }) {
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

export function PokerActionReplayRail({ state, compact = false }) {
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

export function ActionEffectOverlay({ item }) {
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

export function LatestActionToast({ state }) {
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
