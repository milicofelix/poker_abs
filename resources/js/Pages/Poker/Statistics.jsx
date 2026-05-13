import React from 'react';

function formatNumber(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

function MetricCard({ label, value, hint }) {
    return (
        <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
            <p className="text-xs font-bold uppercase tracking-[0.25em] text-emerald-200">{label}</p>
            <p className="mt-3 text-3xl font-black text-white">{value}</p>
            {hint && <p className="mt-2 text-sm text-slate-300">{hint}</p>}
        </div>
    );
}

function ActionBar({ label, value, total }) {
    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;

    return (
        <div>
            <div className="mb-2 flex items-center justify-between text-sm">
                <span className="font-bold text-slate-100">{label}</span>
                <span className="text-slate-300">{formatNumber(value)} ações</span>
            </div>
            <div className="h-3 overflow-hidden rounded-full bg-slate-950/50">
                <div
                    className="h-full rounded-full bg-emerald-400 shadow-lg shadow-emerald-500/20"
                    style={{ width: `${percentage}%` }}
                />
            </div>
        </div>
    );
}

export default function Statistics({ statistics }) {
    const overview = statistics.overview;
    const actionsTotal = overview.totalActions;

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <header className="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-200">
                            Poker ABS
                        </p>
                        <h1 className="mt-2 text-3xl font-black">Estatísticas avançadas</h1>
                        <p className="mt-2 max-w-2xl text-sm text-slate-300">
                            Resumo local calculado a partir das mãos persistidas e do histórico de ações gravado no banco.
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

                        <a
                            href="/poker/ranking"
                            className="inline-flex w-fit rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-5 py-3 font-bold text-emerald-100 transition hover:bg-emerald-300/20"
                        >
                            Ranking
                        </a>
                    </div>
                </header>

                <section className="grid gap-4 md:grid-cols-4">
                    <MetricCard label="Mãos finalizadas" value={formatNumber(overview.handsPlayed)} />
                    <MetricCard label="Pote acumulado" value={formatNumber(overview.totalPot)} hint="fichas movimentadas em mãos finalizadas" />
                    <MetricCard label="Pote médio" value={formatNumber(overview.averagePot)} />
                    <MetricCard label="Showdown" value={`${overview.showdownRate}%`} hint={`${formatNumber(overview.showdowns)} mãos chegaram ao showdown`} />
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                        <h2 className="text-xl font-black">Ações mais usadas</h2>
                        <p className="mt-1 text-sm text-slate-300">
                            Distribuição das ações registradas no histórico persistido.
                        </p>

                        <div className="mt-5 space-y-5">
                            <ActionBar label="Checks" value={statistics.actions.checks} total={actionsTotal} />
                            <ActionBar label="Calls" value={statistics.actions.calls} total={actionsTotal} />
                            <ActionBar label="Raises" value={statistics.actions.raises} total={actionsTotal} />
                            <ActionBar label="Folds" value={statistics.actions.folds} total={actionsTotal} />
                        </div>
                    </div>

                    <div className="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                        <h2 className="text-xl font-black">Por street</h2>
                        <p className="mt-1 text-sm text-slate-300">
                            Volume de ações e fichas movimentadas por etapa da mão.
                        </p>

                        <div className="mt-5 overflow-hidden rounded-2xl border border-white/10 bg-slate-950/40">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-white/10 text-xs uppercase tracking-[0.18em] text-emerald-200">
                                    <tr>
                                        <th className="px-4 py-3">Street</th>
                                        <th className="px-4 py-3">Ações</th>
                                        <th className="px-4 py-3">Fichas</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-white/10">
                                    {statistics.streets.map((street) => (
                                        <tr key={street.street}>
                                            <td className="px-4 py-3 font-bold text-white">{street.label}</td>
                                            <td className="px-4 py-3 text-slate-300">{formatNumber(street.actions)}</td>
                                            <td className="px-4 py-3 text-slate-300">{formatNumber(street.chipsInvested)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section className="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                    <h2 className="text-xl font-black">Desempenho por jogador</h2>
                    <p className="mt-1 text-sm text-slate-300">
                        Estatísticas individuais calculadas por participante do jogo local.
                    </p>

                    <div className="mt-5 grid gap-4 md:grid-cols-2">
                        {statistics.players.map((player) => (
                            <article key={player.player} className="rounded-3xl border border-white/10 bg-slate-950/40 p-5">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-[0.25em] text-emerald-200">
                                            Jogador
                                        </p>
                                        <h3 className="mt-2 text-2xl font-black">{player.label}</h3>
                                        <p className="mt-1 text-sm text-slate-300">
                                            {formatNumber(player.victories)} vitórias • {player.winRate}% aproveitamento
                                        </p>
                                    </div>

                                    <span className="rounded-full border border-emerald-300/30 bg-emerald-300/10 px-3 py-1 text-xs font-bold text-emerald-100">
                                        {formatNumber(player.chipsInvested)} fichas
                                    </span>
                                </div>

                                <dl className="mt-5 grid grid-cols-2 gap-3 text-sm">
                                    <div className="rounded-2xl bg-white/10 p-4">
                                        <dt className="text-slate-400">Checks</dt>
                                        <dd className="mt-1 text-2xl font-black">{formatNumber(player.checks)}</dd>
                                    </div>
                                    <div className="rounded-2xl bg-white/10 p-4">
                                        <dt className="text-slate-400">Calls</dt>
                                        <dd className="mt-1 text-2xl font-black">{formatNumber(player.calls)}</dd>
                                    </div>
                                    <div className="rounded-2xl bg-white/10 p-4">
                                        <dt className="text-slate-400">Raises</dt>
                                        <dd className="mt-1 text-2xl font-black">{formatNumber(player.raises)}</dd>
                                    </div>
                                    <div className="rounded-2xl bg-white/10 p-4">
                                        <dt className="text-slate-400">Folds</dt>
                                        <dd className="mt-1 text-2xl font-black">{formatNumber(player.folds)}</dd>
                                    </div>
                                </dl>
                            </article>
                        ))}
                    </div>
                </section>
            </div>
        </main>
    );
}
