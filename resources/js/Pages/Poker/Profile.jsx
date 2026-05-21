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

function StatCard({ label, value, helper }) {
    return (
        <article className="rounded-3xl border border-white/10 bg-slate-950/45 p-5 shadow-xl">
            <p className="text-xs font-black uppercase tracking-[0.22em] text-slate-400">{label}</p>
            <strong className="mt-2 block text-3xl font-black text-white">{value}</strong>
            {helper && <span className="mt-1 block text-xs font-semibold text-slate-400">{helper}</span>}
        </article>
    );
}

export default function Profile({ profile = {} }) {
    const player = profile.player ?? {};
    const stats = profile.stats ?? {};
    const recentTransactions = profile.recentTransactions ?? [];
    const recentHands = profile.recentHands ?? [];

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-7xl flex-col gap-6">
                <header className="overflow-hidden rounded-3xl border border-white/10 bg-white/10 shadow-2xl backdrop-blur">
                    <div className="grid gap-6 p-6 lg:grid-cols-[auto_1fr_auto] lg:items-center">
                        <div className="flex h-24 w-24 items-center justify-center rounded-[2rem] border border-amber-200/30 bg-amber-300/15 text-4xl font-black text-amber-100 shadow-xl shadow-black/25">
                            {player.initials ?? 'JP'}
                        </div>

                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-200">Poker ABS · FASE {profile.phase ?? '12.9'}</p>
                            <h1 className="mt-2 text-4xl font-black">{player.name ?? 'Jogador'}</h1>
                            <p className="mt-2 max-w-3xl text-sm text-slate-300">
                                Perfil financeiro com bankroll, ROI, vitórias, movimentações recentes e histórico resumido das últimas mãos.
                            </p>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2 lg:min-w-[320px]">
                            <div className="rounded-2xl border border-emerald-300/20 bg-emerald-300/10 p-4">
                                <p className="text-xs font-black uppercase tracking-[0.2em] text-emerald-100">Bankroll</p>
                                <strong className="mt-1 block text-3xl font-black">{formatNumber(player.bankroll)}</strong>
                            </div>
                            <div className="rounded-2xl border border-amber-200/20 bg-amber-300/10 p-4">
                                <p className="text-xs font-black uppercase tracking-[0.2em] text-amber-100">Ranking</p>
                                <strong className="mt-1 block text-3xl font-black">#{player.rankingPosition ?? '-'}</strong>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-3 border-t border-white/10 bg-slate-950/30 px-6 py-4">
                        <a href="/poker/ranking" className="rounded-xl bg-white px-4 py-2 text-sm font-black text-slate-950 transition hover:bg-emerald-100">Ranking</a>
                        <a href="/poker/bankroll" className="rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20">Minhas fichas</a>
                        <a href="/poker/lobby" className="rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20">Lobby</a>
                    </div>
                </header>

                <section className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard label="Vitórias" value={formatNumber(stats.wins)} helper={`${formatNumber(stats.playedHands)} mãos com movimentação`} />
                    <StatCard label="Winrate" value={formatPercent(stats.winRate)} helper="Vitórias / mãos jogadas" />
                    <StatCard label="Lucro líquido" value={formatNumber(stats.netProfit)} helper="Retorno - investimento" />
                    <StatCard label="ROI" value={formatPercent(stats.roi)} helper="Resultado sobre buy-ins" />
                </section>

                <section className="grid gap-6 xl:grid-cols-[1fr_380px]">
                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200">Desempenho</p>
                                <h2 className="mt-1 text-2xl font-black">Resumo financeiro</h2>
                            </div>
                            <span className="text-xs font-bold text-slate-400">Último movimento: {stats.lastMovementAt ?? '-'}</span>
                        </div>

                        <div className="mt-5 grid gap-3 md:grid-cols-3">
                            <StatCard label="Investido" value={formatNumber(stats.invested)} helper={`Buy-ins ${formatNumber(stats.buyIns)} · Rebuys ${formatNumber(stats.rebuys)}`} />
                            <StatCard label="Retornado" value={formatNumber(stats.returned)} helper={`Prêmios ${formatNumber(stats.payouts)} · Saídas ${formatNumber(stats.stackReturns)}`} />
                            <StatCard label="Movimentações" value={formatNumber(stats.transactions)} helper="Ledger do bankroll" />
                        </div>

                        <div className="mt-6 rounded-3xl border border-white/10 bg-slate-950/40 p-5">
                            <p className="text-xs font-black uppercase tracking-[0.25em] text-amber-100">Últimas mãos</p>
                            <div className="mt-4 grid gap-3">
                                {recentHands.length === 0 ? (
                                    <p className="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">Nenhuma mão encontrada para este perfil.</p>
                                ) : recentHands.map((hand) => (
                                    <article key={`${hand.handId}-${hand.movement}`} className="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <strong className="block text-white">{hand.table}</strong>
                                                <span className="text-xs text-slate-400">{hand.finishedAt} · Pote {formatNumber(hand.pot)}</span>
                                            </div>
                                            <strong className={movementTone(hand.movement)}>{formatNumber(hand.movement)}</strong>
                                        </div>
                                        <p className="mt-2 text-sm text-slate-300">Vencedor: {hand.winner ?? '-'} · Melhor mão: {hand.winningHand ?? '-'}</p>
                                    </article>
                                ))}
                            </div>
                        </div>
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
                                            <strong className="block text-white">{transaction.typeLabel}</strong>
                                            <span className="text-xs text-slate-400">{transaction.createdAt}</span>
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
