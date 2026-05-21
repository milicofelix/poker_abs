import React from 'react';

const streets = [
    { value: 'pre_flop', label: 'Pré-flop' },
    { value: 'flop', label: 'Flop' },
    { value: 'turn', label: 'Turn' },
    { value: 'river', label: 'River' },
    { value: 'showdown', label: 'Showdown' },
];

export default function PokerStreetProgress({ currentStreet, compact = false }) {
    const currentIndex = streets.findIndex((street) => street.value === currentStreet);

    return (
        <section className={compact
            ? 'rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-3 shadow-xl shadow-black/35 backdrop-blur'
            : 'rounded-2xl border border-white/10 bg-slate-950/60 p-4 shadow-xl'}
        >
            <h2 className={compact
                ? 'mb-3 text-[0.65rem] font-black uppercase tracking-[0.24em] text-slate-300'
                : 'mb-4 text-lg font-semibold text-white'}
            >
                Progresso da rodada
            </h2>

            <div className={compact ? 'grid grid-cols-5 gap-1' : 'grid gap-3 md:grid-cols-5'}>
                {streets.map((street, index) => {
                    const isCurrent = index === currentIndex;
                    const isCompleted = currentIndex > index;

                    return (
                        <div
                            key={street.value}
                            className={[
                                compact
                                    ? 'rounded-full border px-2 py-1.5 text-center text-[0.58rem] font-black uppercase tracking-[0.08em] transition'
                                    : 'rounded-xl border px-4 py-3 text-center text-sm font-semibold transition',
                                isCurrent
                                    ? 'border-amber-300 bg-amber-400/20 text-amber-100'
                                    : '',
                                isCompleted
                                    ? 'border-emerald-300/40 bg-emerald-400/10 text-emerald-100'
                                    : '',
                                !isCurrent && !isCompleted
                                    ? 'border-white/10 bg-white/5 text-slate-400'
                                    : '',
                            ].join(' ')}
                        >
                            {compact ? street.label.replace('Pré-', 'Pré') : street.label}
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
