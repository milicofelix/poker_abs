import React from 'react';
import { PokerBadge, PokerEmptyState, PokerSurface } from '../../Components/Poker/Ui/PokerDesignSystem';
import { PokerInfoGrid, PokerResponsiveTable, PokerTimelineList } from '../../Components/Poker/Ui/PokerDataDisplay';
import { PokerMetricCard, PokerNavButton, PokerPageHero, PokerPageShell } from '../../Components/Poker/Ui/PokerPageLayout';

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

function movementBadgeTone(amount) {
    return Number(amount || 0) >= 0 ? 'success' : 'danger';
}

function summaryCards(summary = {}) {
    return [
        ['Jogadores', summary.players, 'participantes com bankroll'],
        ['Bankroll total', summary.totalBankroll, 'fichas somadas'],
        ['Maior bankroll', summary.highestBankroll, 'liderança atual'],
        ['Transações', summary.transactions, 'movimentações no ledger'],
    ];
}

export default function Ranking({ ranking = {} }) {
    const leaderboard = ranking.leaderboard ?? [];
    const summary = ranking.summary ?? {};
    const recentTransactions = ranking.recentTransactions ?? [];
    const currentUser = ranking.currentUser;

    const columns = [
        { key: 'position', label: '#', width: '64px' },
        { key: 'player', label: 'Jogador', width: 'minmax(220px,1fr)' },
        { key: 'bankroll', label: 'Bankroll', width: '130px', align: 'right' },
        { key: 'profit', label: 'Lucro', width: '120px', align: 'right' },
        { key: 'roi', label: 'ROI', width: '100px', align: 'right' },
    ];

    return (
        <PokerPageShell tone="emerald" maxWidth="max-w-7xl">
            <PokerPageHero
                eyebrow={`Poker ABS · FASE ${ranking.phase ?? '13.1.3'}`}
                title="Ranking financeiro"
                description="Leaderboard calculado por bankroll, lucro líquido, vitórias pagas pelo ledger e movimentações recentes. Esta tela agora usa a base visual piloto da FASE 13."
                actions={(
                    <>
                        <PokerNavButton href="/poker/lobby" tone="primary">Lobby</PokerNavButton>
                        <PokerNavButton href="/poker/profile" tone="warning">Meu perfil</PokerNavButton>
                        <PokerNavButton href="/poker/bankroll">Minhas fichas</PokerNavButton>
                        <PokerNavButton href="/poker/hands">Histórico</PokerNavButton>
                    </>
                )}
                meta={(
                    <>
                        <PokerBadge tone="success">Ranking ativo</PokerBadge>
                        <PokerBadge tone="neutral">{formatNumber(leaderboard.length)} jogadores listados</PokerBadge>
                    </>
                )}
            />

            <section className="grid gap-3 md:grid-cols-4">
                {summaryCards(summary).map(([label, value, description]) => (
                    <PokerMetricCard key={label} label={label} value={formatNumber(value)} description={description} />
                ))}
            </section>

            {currentUser && (
                <PokerSurface tone="amber" className="p-5">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.25em] text-amber-100">Seu resumo</p>
                            <h2 className="mt-1 text-xl font-black text-white">Sua posição no ranking</h2>
                        </div>
                        <PokerBadge tone={movementBadgeTone(currentUser.netProfit)}>
                            Lucro: {formatNumber(currentUser.netProfit)}
                        </PokerBadge>
                    </div>

                    <PokerInfoGrid
                        className="mt-4"
                        columns="sm:grid-cols-2 lg:grid-cols-5"
                        items={[
                            { label: 'Posição', value: currentUser.position ?? '-' },
                            { label: 'Bankroll', value: formatNumber(currentUser.bankroll) },
                            { label: 'Lucro líquido', value: formatNumber(currentUser.netProfit) },
                            { label: 'ROI', value: formatPercent(currentUser.roi) },
                            { label: 'Vitórias', value: formatNumber(currentUser.wins) },
                        ]}
                    />
                </PokerSurface>
            )}

            <section className="grid gap-6 xl:grid-cols-[1fr_360px]">
                <PokerSurface className="p-5" tone="soft">
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200">Leaderboard</p>
                            <h2 className="mt-1 text-2xl font-black text-white">Maiores bankrolls</h2>
                        </div>
                        <span className="text-xs font-bold text-slate-400">Critérios: bankroll, lucro, vitórias</span>
                    </div>

                    <div className="mt-5">
                        <PokerResponsiveTable
                            columns={columns}
                            rows={leaderboard}
                            getRowKey={(row) => row.userId}
                            emptyState={(
                                <PokerEmptyState
                                    eyebrow="Ranking vazio"
                                    title="Nenhum jogador encontrado para o ranking"
                                    description="Assim que houver bankroll ou movimentações registradas, os jogadores aparecerão aqui."
                                />
                            )}
                            renderRow={(row) => (
                                <article className="grid grid-cols-[64px_minmax(220px,1fr)_130px_120px_100px] gap-3 px-4 py-3 text-sm transition hover:bg-white/5">
                                    <strong className="text-amber-200">{row.podiumLabel}</strong>
                                    <div>
                                        <a href={`/poker/players/${row.userId}`} className="block font-black text-white transition hover:text-emerald-200">
                                            {row.name}
                                        </a>
                                        <span className="text-xs text-slate-400">Vitórias: {formatNumber(row.wins)} · Movs: {formatNumber(row.transactions)}</span>
                                    </div>
                                    <strong className="text-right text-white">{formatNumber(row.bankroll)}</strong>
                                    <strong className={`text-right ${movementTone(row.netProfit)}`}>{formatNumber(row.netProfit)}</strong>
                                    <strong className="text-right text-slate-200">{formatPercent(row.roi)}</strong>
                                </article>
                            )}
                        />
                    </div>
                </PokerSurface>

                <PokerTimelineList
                    eyebrow="Movimentações recentes"
                    title="Ledger financeiro"
                    description="Últimos registros usados para compor bankroll e lucro."
                    items={recentTransactions}
                    badge={`${formatNumber(recentTransactions.length)} itens`}
                    emptyText="Sem movimentações ainda."
                    renderItem={(transaction) => (
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
                    )}
                />
            </section>
        </PokerPageShell>
    );
}
