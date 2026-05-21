import React from 'react';
import PokerHeader from '@/Components/Poker/PokerHeader';
import {
    PokerBadge,
    PokerButton,
    PokerEmptyState,
    PokerSectionHeader,
    PokerSurface,
} from '@/Components/Poker/Ui/PokerDesignSystem';

function formatNumber(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

function amountTone(amount) {
    return amount > 0 ? 'success' : 'warning';
}

function metricCard(label, value, tone = 'soft', helper = null) {
    return (
        <PokerSurface tone={tone} className="p-5">
            <p className="text-[0.62rem] font-black uppercase tracking-[0.22em] text-slate-400">{label}</p>
            <p className="mt-2 text-2xl font-black text-white md:text-3xl">{value}</p>
            {helper ? <p className="mt-2 text-xs font-semibold text-slate-400">{helper}</p> : null}
        </PokerSurface>
    );
}

export default function BankrollHistory({ bankroll = {}, transactions = [] }) {
    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-4 text-white md:p-6">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <PokerHeader />

                <PokerSurface className="overflow-hidden p-0">
                    <div className="border-b border-white/10 bg-[radial-gradient(circle_at_top_left,rgba(52,211,153,0.18),transparent_34%),rgba(15,23,42,0.68)] p-6 md:p-7">
                        <PokerSectionHeader
                            eyebrow="Poker ABS • FASE 13.1.6"
                            title="Histórico do bankroll"
                            description="Acompanhe cada crédito, débito, buy-in e premiação em uma tela alinhada ao novo padrão visual do Poker ABS."
                            action={(
                                <PokerButton as="a" href="/poker/lobby" tone="primary">
                                    Voltar ao lobby
                                </PokerButton>
                            )}
                        />
                    </div>

                    <div className="grid gap-4 p-5 md:grid-cols-4 md:p-6">
                        {metricCard('Saldo atual', formatNumber(bankroll.current), 'emerald', 'Saldo consolidado após movimentações')}
                        {metricCard('Créditos', `+${formatNumber(bankroll.credits)}`, 'soft', 'Entradas e premiações')}
                        {metricCard('Débitos', formatNumber(bankroll.debits), 'soft', 'Buy-ins e saídas')}
                        {metricCard('Movimentações', formatNumber(bankroll.transactionsCount), 'soft', 'Até 50 registros recentes')}
                    </div>
                </PokerSurface>

                <PokerSurface className="p-5 md:p-6">
                    <PokerSectionHeader
                        eyebrow="Ledger"
                        title="Últimas movimentações"
                        description="Lista responsiva com tipo, mesa, valor, saldo antes/depois e data da movimentação."
                    />

                    {transactions.length === 0 ? (
                        <div className="mt-6">
                            <PokerEmptyState
                                eyebrow="Sem movimentações"
                                title="Nenhum lançamento no bankroll ainda"
                                description="Entre em uma mesa ou conclua uma mão com buy-in para registrar os primeiros movimentos financeiros."
                                action={(
                                    <PokerButton as="a" href="/poker/lobby" tone="primary">
                                        Abrir lobby
                                    </PokerButton>
                                )}
                            />
                        </div>
                    ) : (
                        <div className="mt-6 overflow-hidden rounded-[1.5rem] border border-white/10">
                            <div className="hidden grid-cols-[1.1fr_1fr_1fr_1.1fr_1fr] gap-3 bg-slate-950/85 px-5 py-3 text-[0.62rem] font-black uppercase tracking-[0.18em] text-slate-400 md:grid">
                                <span>Tipo</span>
                                <span>Mesa</span>
                                <span>Valor</span>
                                <span>Saldo</span>
                                <span>Data</span>
                            </div>

                            <div className="divide-y divide-white/10">
                                {transactions.map((transaction) => (
                                    <article
                                        key={transaction.id}
                                        className="grid gap-4 bg-slate-950/35 px-4 py-4 transition hover:bg-slate-950/55 md:grid-cols-[1.1fr_1fr_1fr_1.1fr_1fr] md:items-center md:px-5"
                                    >
                                        <div>
                                            <p className="text-sm font-black text-white">{transaction.typeLabel}</p>
                                            <p className="mt-1 text-xs font-semibold text-slate-400">
                                                {transaction.handId ? `Mão #${transaction.handId}` : 'Sem mão vinculada'}
                                            </p>
                                        </div>

                                        <div>
                                            <p className="text-[0.62rem] font-black uppercase tracking-[0.18em] text-slate-500 md:hidden">Mesa</p>
                                            <p className="text-sm font-bold text-slate-200">{transaction.tableName}</p>
                                        </div>

                                        <div>
                                            <p className="mb-2 text-[0.62rem] font-black uppercase tracking-[0.18em] text-slate-500 md:hidden">Valor</p>
                                            <PokerBadge tone={amountTone(transaction.amount)}>
                                                {transaction.amountLabel}
                                            </PokerBadge>
                                        </div>

                                        <div>
                                            <p className="text-[0.62rem] font-black uppercase tracking-[0.18em] text-slate-500 md:hidden">Saldo</p>
                                            <p className="text-sm font-bold text-slate-200">
                                                {formatNumber(transaction.balanceBefore)} → {formatNumber(transaction.balanceAfter)}
                                            </p>
                                        </div>

                                        <div>
                                            <p className="text-[0.62rem] font-black uppercase tracking-[0.18em] text-slate-500 md:hidden">Data</p>
                                            <p className="text-sm font-bold text-slate-300">{transaction.createdAt}</p>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </div>
                    )}
                </PokerSurface>
            </div>
        </main>
    );
}
