import React from 'react';

export default function PokerTurnTimer({ timer, compact = false }) {
    if (!timer) {
        return null;
    }

    const isUrgent = timer.isExpired || timer.secondsRemaining <= 10;
    const barClass = timer.isExpired
        ? 'bg-rose-400'
        : isUrgent
            ? 'bg-amber-300'
            : 'bg-emerald-300';

    if (compact) {
        return (
            <section className="rounded-[1.5rem] border border-white/10 bg-slate-950/82 p-3 shadow-2xl shadow-black/45 backdrop-blur">
                <div className="flex items-center justify-between gap-4">
                    <div className="min-w-0">
                        <p className="text-[0.65rem] font-black uppercase tracking-[0.28em] text-emerald-200/80">
                            Tempo da jogada
                        </p>
                        <h2 className="mt-1 truncate text-base font-black text-white">
                            {timer.label}
                        </h2>
                        <p className="mt-1 text-xs font-semibold text-slate-400">
                            {timer.isExpired ? 'Tempo esgotado.' : 'Sempre visível durante a mão.'}
                        </p>
                    </div>

                    <div className={[
                        'grid h-20 w-20 shrink-0 place-items-center rounded-full border-4 text-2xl font-black shadow-xl',
                        isUrgent
                            ? 'border-amber-200/70 bg-amber-300/15 text-amber-100 shadow-amber-950/40'
                            : 'border-emerald-200/60 bg-emerald-300/15 text-emerald-100 shadow-emerald-950/30',
                    ].join(' ')}>
                        {timer.secondsRemaining}s
                    </div>
                </div>

                <div className="mt-3 h-2 overflow-hidden rounded-full border border-white/10 bg-black/35 p-0.5">
                    <div
                        className={`h-full rounded-full transition-all duration-500 ${barClass}`}
                        style={{ width: `${timer.percentage}%` }}
                    />
                </div>
            </section>
        );
    }

    return (
        <section className="rounded-2xl border border-white/10 bg-slate-950/75 p-3 shadow-2xl shadow-black/40 backdrop-blur">
            <div className="flex items-center justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-xs font-black uppercase tracking-[0.28em] text-emerald-200/80">Turn timer</p>
                    <h2 className="mt-1 truncate text-xl font-black text-white">{timer.label}</h2>
                </div>

                <div className={`shrink-0 rounded-[1.5rem] border px-5 py-3 text-4xl font-black shadow-xl ${isUrgent ? 'border-amber-200/50 bg-amber-300/15 text-amber-100' : 'border-emerald-200/30 bg-emerald-300/10 text-emerald-100'}`}>
                    {timer.secondsRemaining}s
                </div>
            </div>

            <div className="mt-4 h-4 overflow-hidden rounded-full border border-white/10 bg-black/35 p-1">
                <div
                    className={`h-full rounded-full transition-all duration-500 ${barClass}`}
                    style={{ width: `${timer.percentage}%` }}
                />
            </div>
        </section>
    );
}
