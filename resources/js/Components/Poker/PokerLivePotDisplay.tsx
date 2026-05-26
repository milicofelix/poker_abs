import { useEffect, useState } from 'react';
import { AnimatePresence, motion } from 'motion/react';
import {
    latestActionAnimationKey,
    latestActionItem,
    shouldPulsePot,
} from '@/features/poker/utils/tableActions';
import type { PokerStateLike } from '@/features/poker/utils/tableActions';
import { chipAmountParts, formatChipAmount } from '@/features/poker/utils/tableState';

type LivePotDisplayProps = {
    state: PokerStateLike;
    compact?: boolean;
};

function useAnimatedChipAmount(value: unknown): number {
    const target = Number(value ?? 0);
    const [displayValue, setDisplayValue] = useState<number>(target);

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
        let frameId: number | null = null;

        const tick = (timestamp: number) => {
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

export function LivePotDisplay({ state, compact = false }: LivePotDisplayProps) {
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
