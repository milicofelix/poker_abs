import React from 'react';

export default function PokerTurnTimer({ timer }) {
    if (!timer) {
        return null;
    }

    const barClass = timer.isExpired
        ? 'bg-rose-400'
        : timer.secondsRemaining <= 10
            ? 'bg-amber-300'
            : 'bg-emerald-300';

    return (
        <section className="rounded-2xl border border-white/10 bg-slate-950/70 p-4 shadow-xl">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <p className="text-xs uppercase tracking-[0.25em] text-slate-400">Turn timer</p>
                    <h2 className="mt-1 text-lg font-black text-white">{timer.label}</h2>
                </div>

                <div className="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-2xl font-black text-white">
                    {timer.secondsRemaining}s
                </div>
            </div>

            <div className="mt-4 h-3 overflow-hidden rounded-full bg-white/10">
                <div
                    className={`h-full rounded-full transition-all duration-500 ${barClass}`}
                    style={{ width: `${timer.percentage}%` }}
                />
            </div>

            <p className="mt-3 text-sm text-slate-300">
                {timer.isExpired
                    ? 'Tempo esgotado. A próxima fase poderá aplicar ação automática.'
                    : 'O contador é sincronizado pela mesa e reinicia após cada ação.'}
            </p>
        </section>
    );
}
