import { AnimatePresence, motion } from 'motion/react';
import { actionVisualTone } from '@/features/poker/utils/tableActions';

type ActionItem = Record<string, any>;

type MotionChipProps = {
    item: ActionItem;
    index: number;
    allIn?: boolean;
};

type ActionEffectOverlayProps = {
    item?: ActionItem | null;
};

function MotionChip({ item, index, allIn = false }: MotionChipProps) {
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

export function ActionEffectOverlay({ item }: ActionEffectOverlayProps) {
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
