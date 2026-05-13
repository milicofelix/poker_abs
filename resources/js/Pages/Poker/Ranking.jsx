import React from 'react';

function formatNumber(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

function podiumLabel(index) {
    if (index === 0) {
        return '1º lugar';
    }

    if (index === 1) {
        return '2º lugar';
    }

    if (index === 2) {
        return '3º lugar';
    }

    return `${index + 1}º lugar`;
}

export default function Ranking({ ranking = [] }) {
    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <header className="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-200">
                            Poker ABS
                        </p>
                        <h1 className="mt-2 text-3xl font-black">Ranking local</h1>
                        <p className="mt-2 max-w-2xl text-sm text-slate-300">
                            Ranking calculado a partir das mãos finalizadas no banco, usando o vencedor persistido em cada mão.
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <a
                            href="/poker"
                            className="inline-flex w-fit rounded-xl bg-white px-5 py-3 font-bold text-slate-950 transition hover:bg-emerald-100"
                        >
                            Voltar para mesa
                        </a>

                        <a
                            href="/poker/hands"
                            className="inline-flex w-fit rounded-xl border border-white/10 bg-white/10 px-5 py-3 font-bold text-white transition hover:bg-white/20"
                        >
                            Histórico
                        </a>
                    </div>
                </header>

                {ranking.length === 0 ? (
                    <section className="rounded-3xl border border-white/10 bg-white/10 p-8 text-center shadow-2xl backdrop-blur">
                        <h2 className="text-xl font-bold">Nenhuma mão finalizada ainda</h2>
                        <p className="mt-2 text-sm text-slate-300">
                            Finalize algumas mãos na mesa local para alimentar o ranking.
                        </p>
                    </section>
                ) : (
                    <section className="grid gap-4">
                        {ranking.map((row, index) => (
                            <article
                                key={row.winner}
                                className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur"
                            >
                                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-[0.25em] text-emerald-200">
                                            {podiumLabel(index)}
                                        </p>
                                        <h2 className="mt-2 text-2xl font-black">{row.label}</h2>
                                        <p className="mt-1 text-sm text-slate-300">
                                            Última mão vencedora: {row.lastWinningHand || '-'}
                                        </p>
                                    </div>

                                    <div className="rounded-2xl border border-emerald-300/20 bg-emerald-300/10 px-4 py-3 text-left md:text-right">
                                        <p className="text-xs uppercase tracking-[0.2em] text-emerald-100">Vitórias</p>
                                        <p className="text-3xl font-black">{formatNumber(row.victories)}</p>
                                    </div>
                                </div>

                                <div className="mt-5 grid gap-3 md:grid-cols-4">
                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Mãos no ranking</p>
                                        <p className="mt-1 text-2xl font-black">{formatNumber(row.hands)}</p>
                                    </div>

                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Empates</p>
                                        <p className="mt-1 text-2xl font-black">{formatNumber(row.ties)}</p>
                                    </div>

                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Fichas disputadas</p>
                                        <p className="mt-1 text-2xl font-black">{formatNumber(row.chipsWon)}</p>
                                    </div>

                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Última finalização</p>
                                        <p className="mt-1 text-sm font-bold text-slate-100">{row.lastFinishedAt || '-'}</p>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </section>
                )}
            </div>
        </main>
    );
}
