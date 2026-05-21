import React from 'react';
import { usePage } from '@inertiajs/react';
import { PokerBadge, PokerEmptyState, PokerSurface } from '../../Components/Poker/Ui/PokerDesignSystem';
import { PokerInfoGrid, PokerResponsiveTable, PokerTimelineList } from '../../Components/Poker/Ui/PokerDataDisplay';
import { PokerMetricCard, PokerNavButton, PokerPageHero, PokerPageShell } from '../../Components/Poker/Ui/PokerPageLayout';

function formatChips(value) {
    return new Intl.NumberFormat('pt-BR').format(Number(value ?? 0));
}

function amountClass(amount) {
    return Number(amount) >= 0 ? 'text-emerald-200' : 'text-red-200';
}

function amountBadgeTone(amount) {
    return Number(amount) >= 0 ? 'success' : 'danger';
}

function amountPrefix(amount) {
    return Number(amount) > 0 ? '+' : '';
}

function summaryCards(summary = {}) {
    return [
        ['Saldo', summary.currentBalance, 'fichas disponíveis agora', 'emerald'],
        ['Créditos', summary.credits, 'entradas no ledger', 'cyan'],
        ['Débitos', summary.debits, 'saídas registradas', 'danger'],
        ['Movimentações', summary.transactionsCount, 'registros financeiros', 'soft'],
    ];
}

function transactionLabel(transaction) {
    return transaction.tableName ? `Mesa: ${transaction.tableName}` : 'Sem mesa vinculada';
}

export default function Bankroll({ summary = {}, transactions = [] }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const columns = [
        { key: 'type', label: 'Tipo', width: '180px' },
        { key: 'table', label: 'Mesa', width: 'minmax(200px,1fr)' },
        { key: 'amount', label: 'Valor', width: '130px', align: 'right' },
        { key: 'balance', label: 'Saldo', width: '200px', align: 'right' },
        { key: 'created', label: 'Quando', width: '150px', align: 'right' },
    ];

    const lastTransactions = transactions.slice(0, 5);

    return (
        <PokerPageShell tone="emerald" maxWidth="max-w-7xl">
            <PokerPageHero
                eyebrow="Poker ABS · FASE 13.1.4"
                title="Histórico de fichas"
                description="Acompanhe buy-ins, premiações e saldo atual do seu bankroll com o mesmo padrão visual aplicado ao Lobby e ao Ranking."
                actions={(
                    <>
                        <PokerNavButton href="/poker/lobby" tone="primary">Lobby</PokerNavButton>
                        <PokerNavButton href="/poker/ranking" tone="warning">Ranking</PokerNavButton>
                        <PokerNavButton href="/poker/hands">Histórico</PokerNavButton>
                        <PokerNavButton href="/poker/statistics">Estatísticas</PokerNavButton>
                    </>
                )}
                meta={(
                    <>
                        <PokerBadge tone="success">Ledger ativo</PokerBadge>
                        <PokerBadge tone="neutral">{formatChips(transactions.length)} movimentações</PokerBadge>
                    </>
                )}
            />

            {user && (
                <PokerSurface tone="emerald" className="p-5">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200">Conta conectada</p>
                            <h2 className="mt-1 text-xl font-black text-white">{user.name}</h2>
                            <p className="mt-1 text-sm font-semibold text-slate-300">Seu saldo atual considera o ledger de bankroll e as premiações registradas.</p>
                        </div>
                        <PokerBadge tone="success">Saldo: {formatChips(summary.currentBalance ?? user.pokerBankroll)} fichas</PokerBadge>
                    </div>

                    <PokerInfoGrid
                        className="mt-4"
                        columns="sm:grid-cols-2 lg:grid-cols-4"
                        items={[
                            { label: 'Saldo atual', value: formatChips(summary.currentBalance ?? user.pokerBankroll) },
                            { label: 'Créditos', value: formatChips(summary.credits) },
                            { label: 'Débitos', value: formatChips(summary.debits) },
                            { label: 'Total de registros', value: formatChips(summary.transactionsCount) },
                        ]}
                    />
                </PokerSurface>
            )}

            <section className="grid gap-3 md:grid-cols-4">
                {summaryCards(summary).map(([label, value, description, tone]) => (
                    <PokerMetricCard key={label} label={label} value={formatChips(value)} description={description} tone={tone} />
                ))}
            </section>

            <section className="grid gap-6 xl:grid-cols-[1fr_360px]">
                <PokerSurface className="p-5" tone="soft">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200">Ledger</p>
                            <h2 className="mt-1 text-2xl font-black text-white">Últimas movimentações</h2>
                            <p className="mt-1 text-sm font-semibold text-slate-300">Entradas, saídas e saldos preservados em uma tabela responsiva.</p>
                        </div>
                        <PokerBadge tone="info">{formatChips(transactions.length)} itens</PokerBadge>
                    </div>

                    <div className="mt-5">
                        <PokerResponsiveTable
                            columns={columns}
                            rows={transactions}
                            getRowKey={(transaction) => transaction.id}
                            emptyState={(
                                <PokerEmptyState
                                    eyebrow="Bankroll vazio"
                                    title="Nenhuma movimentação de fichas registrada ainda"
                                    description="Quando houver buy-in, premiação ou ajuste financeiro, o histórico aparecerá aqui com saldo antes e depois."
                                />
                            )}
                            renderRow={(transaction) => (
                                <article className="grid grid-cols-[180px_minmax(200px,1fr)_130px_200px_150px] gap-3 px-4 py-3 text-sm transition hover:bg-white/5">
                                    <div>
                                        <strong className="block text-white">{transaction.typeLabel}</strong>
                                        <PokerBadge tone={amountBadgeTone(transaction.amount)} className="mt-2">
                                            {Number(transaction.amount) >= 0 ? 'Entrada' : 'Saída'}
                                        </PokerBadge>
                                    </div>
                                    <span className="text-slate-300">{transaction.tableName ?? '—'}</span>
                                    <strong className={`text-right ${amountClass(transaction.amount)}`}>
                                        {amountPrefix(transaction.amount)}{formatChips(transaction.amount)}
                                    </strong>
                                    <span className="text-right text-slate-300">
                                        {formatChips(transaction.balanceBefore)} → <strong className="text-white">{formatChips(transaction.balanceAfter)}</strong>
                                    </span>
                                    <span className="text-right text-slate-400">{transaction.createdAtLabel ?? '—'}</span>
                                </article>
                            )}
                        />
                    </div>
                </PokerSurface>

                <PokerTimelineList
                    eyebrow="Resumo recente"
                    title="Últimos eventos financeiros"
                    description="Visão compacta para conferir rapidamente o que afetou seu saldo."
                    items={lastTransactions}
                    badge={`${formatChips(lastTransactions.length)} itens`}
                    emptyText="Sem movimentações recentes."
                    renderItem={(transaction) => (
                        <article key={transaction.id} className="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <strong className="block text-white">{transaction.typeLabel}</strong>
                                    <span className="text-xs text-slate-400">{transactionLabel(transaction)}</span>
                                </div>
                                <strong className={amountClass(transaction.amount)}>
                                    {amountPrefix(transaction.amount)}{formatChips(transaction.amount)}
                                </strong>
                            </div>
                            <p className="mt-2 text-xs text-slate-400">Saldo final: {formatChips(transaction.balanceAfter)} · {transaction.createdAtLabel ?? '—'}</p>
                        </article>
                    )}
                />
            </section>
        </PokerPageShell>
    );
}
