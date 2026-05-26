import { AnimatePresence, motion } from 'motion/react';
import {
    actionTimelineItems,
    multiSeatActionPillClasses,
} from '@/features/poker/utils/tableActions';
import {
    formatChipAmount,
    isMultiSeatLayout,
    isMultiSeatShowdownResolved,
    multiSeatPlayers,
} from '@/features/poker/utils/tableState';
import {
    isMultiSeatSplitPot,
    isMultiSeatWinner,
    multiSeatWinnerSeats,
    showdownCinematicBestHandLabel,
    showdownCinematicWinnerLabel,
} from '@/features/poker/utils/tableShowdown';

export function ShowdownPremiumPanel({ state }) {
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

export function ShowdownCinematicRibbon({ state }) {
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
