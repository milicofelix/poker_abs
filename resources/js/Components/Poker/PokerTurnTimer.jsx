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
        <section className="rounded-[2rem] border border-white/10 bg-slate-950/75 p-4 shadow-2xl shadow-black/40 backdrop-blur">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p className="text-xs font-black uppercase tracking-[0.28em] text-emerald-200/80">Turn timer</p>
                    <h2 className="mt-1 text-xl font-black text-white">{timer.label}</h2>
                    <p className="mt-2 text-sm text-slate-300">
                        {timer.isExpired
                            ? 'Tempo esgotado. A mesa pode aplicar ação automática.'
                            : 'Contador sincronizado pela mesa e reiniciado após cada ação.'}
                    </p>
                </div>

                <div className={`rounded-[1.5rem] border px-5 py-3 text-4xl font-black shadow-xl ${timer.secondsRemaining <= 10 ? 'border-amber-200/50 bg-amber-300/15 text-amber-100' : 'border-emerald-200/30 bg-emerald-300/10 text-emerald-100'}`}>
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
