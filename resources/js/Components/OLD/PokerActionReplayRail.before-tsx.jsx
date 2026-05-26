import { AnimatePresence, motion } from 'motion/react';
import {
    actionTimelineItems,
    latestActionItem,
    multiSeatActionPillClasses,
} from '@/features/poker/utils/tableActions';
import { formatChipAmount } from '@/features/poker/utils/tableState';

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
