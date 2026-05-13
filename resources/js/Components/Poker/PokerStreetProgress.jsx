import React from 'react';

const streets = [
    { value: 'pre_flop', label: 'Pré-flop' },
    { value: 'flop', label: 'Flop' },
    { value: 'turn', label: 'Turn' },
    { value: 'river', label: 'River' },
    { value: 'showdown', label: 'Showdown' },
];

export default function PokerStreetProgress({ currentStreet }) {
    const currentIndex = streets.findIndex((street) => street.value === currentStreet);

    return (
        <section className="rounded-2xl border border-white/10 bg-slate-950/60 p-4 shadow-xl">
            <h2 className="mb-4 text-lg font-semibold text-white">Progresso da rodada</h2>

            <div className="grid gap-3 md:grid-cols-5">
                {streets.map((street, index) => {
                    const isCurrent = index === currentIndex;
                    const isCompleted = currentIndex > index;

                    return (
                        <div
                            key={street.value}
                            className={[
                                'rounded-xl border px-4 py-3 text-center text-sm font-semibold transition',
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
                            {street.label}
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
