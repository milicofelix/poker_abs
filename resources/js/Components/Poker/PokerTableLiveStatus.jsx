import { AnimatePresence, motion } from 'motion/react';
import usePokerTurnTimer from '../../hooks/usePokerTurnTimer';
import { latestActionItem } from '@/features/poker/utils/tableActions';
import {
    isCurrentUserTurn,
    liveTurnTimerTone,
    tournamentHudMetrics,
} from '@/features/poker/utils/tableLive';
import {
    currentTurnLabel,
    formatChipAmount,
} from '@/features/poker/utils/tableState';
import { narrativeStageLabel } from '@/features/poker/utils/tableShowdown';

export function TournamentLiveHud({ state, currentPlayer = null }) {
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

export function LiveTurnTimerBadge({ timer, compact = false }) {
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

export function YourTurnOverlay({ state, timer }) {
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

export function MobileTableStickyStatus({ state }) {
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
