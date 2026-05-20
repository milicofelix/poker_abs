import React from 'react';

function formatNumber(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

function formatPercent(value) {
    if (value === null || value === undefined) {
        return '-';
    }

    return `${Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
}

function movementTone(amount) {
    return Number(amount || 0) >= 0 ? 'text-emerald-200' : 'text-red-200';
}

function summaryCards(summary = {}) {
    return [
        ['Jogadores', summary.players],
        ['Bankroll total', summary.totalBankroll],
        ['Maior bankroll', summary.highestBankroll],
        ['Transações', summary.transactions],
    ];
}

export default function Ranking({ ranking = {} }) {
    const leaderboard = ranking.leaderboard ?? [];
    const summary = ranking.summary ?? {};
    const recentTransactions = ranking.recentTransactions ?? [];
    const currentUser = ranking.currentUser;

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-7xl flex-col gap-6">
                <header className="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-200">Poker ABS · FASE {ranking.phase ?? '12.8'}</p>
                        <h1 className="mt-2 text-3xl font-black">Ranking financeiro</h1>
                        <p className="mt-2 max-w-3xl text-sm text-slate-300">
                            Leaderboard calculado por bankroll, lucro líquido, vitórias pagas pelo ledger e movimentações recentes.
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <a href="/poker/lobby" className="inline-flex rounded-xl bg-white px-5 py-3 font-bold text-slate-950 transition hover:bg-emerald-100">
                            Lobby
                        </a>
                        <a href="/poker/bankroll" className="inline-flex rounded-xl border border-white/10 bg-white/10 px-5 py-3 font-bold text-white transition hover:bg-white/20">
                            Minhas fichas
                        </a>
                        <a href="/poker/hands" className="inline-flex rounded-xl border border-white/10 bg-white/10 px-5 py-3 font-bold text-white transition hover:bg-white/20">
                            Histórico
                        </a>
                    </div>
                </header>

                <section className="grid gap-3 md:grid-cols-4">
                    {summaryCards(summary).map(([label, value]) => (
                        <article key={label} className="rounded-3xl border border-white/10 bg-slate-950/45 p-5 shadow-xl">
                            <p className="text-xs font-black uppercase tracking-[0.25em] text-slate-400">{label}</p>
                            <strong className="mt-2 block text-3xl font-black text-white">{formatNumber(value)}</strong>
                        </article>
                    ))}
                </section>

                {currentUser && (
                    <section className="rounded-3xl border border-amber-200/25 bg-amber-300/10 p-5 shadow-xl">
                        <p className="text-xs font-black uppercase tracking-[0.25em] text-amber-100">Seu resumo</p>
                        <div className="mt-3 grid gap-3 md:grid-cols-5">
                            <div><span className="text-xs text-amber-100/70">Posição</span><strong className="block text-2xl">{currentUser.position ?? '-'}</strong></div>
                            <div><span className="text-xs text-amber-100/70">Bankroll</span><strong className="block text-2xl">{formatNumber(currentUser.bankroll)}</strong></div>
                            <div><span className="text-xs text-amber-100/70">Lucro líquido</span><strong className="block text-2xl">{formatNumber(currentUser.netProfit)}</strong></div>
                            <div><span className="text-xs text-amber-100/70">ROI</span><strong className="block text-2xl">{formatPercent(currentUser.roi)}</strong></div>
                            <div><span className="text-xs text-amber-100/70">Vitórias</span><strong className="block text-2xl">{formatNumber(currentUser.wins)}</strong></div>
                        </div>
                    </section>
                )}

                <section className="grid gap-6 xl:grid-cols-[1fr_360px]">
                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200">Leaderboard</p>
                                <h2 className="mt-1 text-2xl font-black">Maiores bankrolls</h2>
                            </div>
                            <span className="text-xs font-bold text-slate-400">Critérios: bankroll, lucro, vitórias</span>
                        </div>

                        {leaderboard.length === 0 ? (
                            <div className="mt-5 rounded-2xl border border-white/10 bg-slate-950/40 p-6 text-center text-slate-300">
                                Nenhum jogador encontrado para o ranking.
                            </div>
                        ) : (
                            <div className="mt-5 overflow-hidden rounded-2xl border border-white/10">
                                <div className="grid grid-cols-[64px_1fr_120px_110px_90px] gap-3 bg-slate-950/70 px-4 py-3 text-xs font-black uppercase tracking-[0.18em] text-slate-400">
                                    <span>#</span><span>Jogador</span><span className="text-right">Bankroll</span><span className="text-right">Lucro</span><span className="text-right">ROI</span>
                                </div>
                                {leaderboard.map((row) => (
                                    <article key={row.userId} className="grid grid-cols-[64px_1fr_120px_110px_90px] gap-3 border-t border-white/10 px-4 py-3 text-sm transition hover:bg-white/5">
                                        <strong className="text-amber-200">{row.podiumLabel}</strong>
                                        <div>
                                            <strong className="block text-white">{row.name}</strong>
                                            <span className="text-xs text-slate-400">Vitórias: {formatNumber(row.wins)} · Movs: {formatNumber(row.transactions)}</span>
                                        </div>
                                        <strong className="text-right text-white">{formatNumber(row.bankroll)}</strong>
                                        <strong className={`text-right ${movementTone(row.netProfit)}`}>{formatNumber(row.netProfit)}</strong>
                                        <strong className="text-right text-slate-200">{formatPercent(row.roi)}</strong>
                                    </article>
                                ))}
                            </div>
                        )}
                    </div>

                    <aside className="rounded-3xl border border-white/10 bg-slate-950/50 p-5 shadow-2xl">
                        <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200">Movimentações recentes</p>
                        <div className="mt-4 grid gap-3">
                            {recentTransactions.length === 0 ? (
                                <p className="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">Sem movimentações ainda.</p>
                            ) : recentTransactions.map((transaction) => (
                                <article key={transaction.id} className="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <strong className="block text-white">{transaction.player}</strong>
                                            <span className="text-xs text-slate-400">{transaction.typeLabel} · {transaction.createdAt}</span>
                                        </div>
                                        <strong className={movementTone(transaction.amount)}>{formatNumber(transaction.amount)}</strong>
                                    </div>
                                    <p className="mt-2 text-xs text-slate-400">Saldo após: {formatNumber(transaction.balanceAfter)}</p>
                                </article>
                            ))}
                        </div>
                    </aside>
                </section>
            </div>
        </main>
    );
}
