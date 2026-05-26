import { AnimatePresence, motion } from 'motion/react';
import {
    actionToastTitle,
    actionVisualTone,
    latestActionItem,
} from '@/features/poker/utils/tableActions';
import type { PokerStateLike } from '@/features/poker/utils/tableActions';

type LatestActionToastProps = {
    state: PokerStateLike;
};

export function LatestActionToast({ state }: LatestActionToastProps) {
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
