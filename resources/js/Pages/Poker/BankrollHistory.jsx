import React from 'react';
import PokerHeader from '@/Components/Poker/PokerHeader';

function formatNumber(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

function amountClass(amount) {
    if (amount > 0) {
        return 'border-emerald-300/25 bg-emerald-300/10 text-emerald-100';
    }

    return 'border-amber-300/25 bg-amber-300/10 text-amber-100';
}

export default function BankrollHistory({ bankroll = {}, transactions = [] }) {
    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <PokerHeader />

                <section className="rounded-[2rem] border border-white/10 bg-white/[0.07] p-6 shadow-2xl shadow-black/40 backdrop-blur">
                    <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.3em] text-emerald-300">
                                FASE 11.4
                            </p>
                            <h1 className="mt-2 text-3xl font-black md:text-4xl">
                                Meu bankroll
                            </h1>
                            <p className="mt-2 max-w-2xl text-sm text-slate-300">
                                Acompanhe buy-ins, premiações e saldo após cada movimentação de fichas registrada no poker.
                            </p>
                        </div>

                        <a
                            href="/poker/lobby"
                            className="inline-flex w-fit rounded-xl bg-emerald-300 px-5 py-3 text-sm font-black text-emerald-950 transition hover:bg-emerald-200"
                        >
                            Voltar ao lobby
                        </a>
                    </div>

                    <div className="mt-6 grid gap-4 md:grid-cols-4">
                        <div className="rounded-3xl border border-emerald-300/20 bg-emerald-300/10 p-5">
                            <p className="text-xs font-bold uppercase tracking-[0.2em] text-emerald-100">Saldo atual</p>
                            <p className="mt-2 text-3xl font-black">{formatNumber(bankroll.current)}</p>
                        </div>

                        <div className="rounded-3xl border border-white/10 bg-slate-950/50 p-5">
                            <p className="text-xs font-bold uppercase tracking-[0.2em] text-slate-300">Créditos</p>
                            <p className="mt-2 text-2xl font-black text-emerald-100">+{formatNumber(bankroll.credits)}</p>
                        </div>

                        <div className="rounded-3xl border border-white/10 bg-slate-950/50 p-5">
                            <p className="text-xs font-bold uppercase tracking-[0.2em] text-slate-300">Débitos</p>
                            <p className="mt-2 text-2xl font-black text-amber-100">{formatNumber(bankroll.debits)}</p>
                        </div>

                        <div className="rounded-3xl border border-white/10 bg-slate-950/50 p-5">
                            <p className="text-xs font-bold uppercase tracking-[0.2em] text-slate-300">Movimentações</p>
                            <p className="mt-2 text-2xl font-black">{formatNumber(bankroll.transactionsCount)}</p>
                        </div>
                    </div>
                </section>

                <section className="rounded-[2rem] border border-white/10 bg-white/[0.07] p-6 shadow-2xl shadow-black/40 backdrop-blur">
                    <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.3em] text-emerald-300">Ledger</p>
                            <h2 className="mt-2 text-2xl font-black">Últimas movimentações</h2>
                        </div>
                        <p className="text-sm text-slate-300">Exibindo até 50 registros mais recentes.</p>
                    </div>

                    {transactions.length === 0 ? (
                        <div className="mt-6 rounded-3xl border border-dashed border-white/15 bg-slate-950/40 p-8 text-center">
                            <h3 className="text-xl font-black">Nenhuma movimentação ainda</h3>
                            <p className="mt-2 text-sm text-slate-300">
                                Entre em uma mesa para registrar o primeiro buy-in no histórico.
                            </p>
                        </div>
                    ) : (
                        <div className="mt-6 overflow-hidden rounded-3xl border border-white/10">
                            <div className="hidden grid-cols-[1.1fr_1fr_1fr_1fr_1fr] gap-3 bg-slate-950/80 px-5 py-3 text-xs font-black uppercase tracking-[0.18em] text-slate-400 md:grid">
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
                                        className="grid gap-3 bg-slate-950/35 px-5 py-4 md:grid-cols-[1.1fr_1fr_1fr_1fr_1fr] md:items-center"
                                    >
                                        <div>
                                            <p className="text-sm font-black text-white">{transaction.typeLabel}</p>
                                            <p className="mt-1 text-xs text-slate-400">
                                                {transaction.handId ? `Mão #${transaction.handId}` : 'Sem mão vinculada'}
                                            </p>
                                        </div>

                                        <div>
                                            <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-500 md:hidden">Mesa</p>
                                            <p className="text-sm font-bold text-slate-200">{transaction.tableName}</p>
                                        </div>

                                        <div>
                                            <span className={`inline-flex rounded-full border px-3 py-1 text-sm font-black ${amountClass(transaction.amount)}`}>
                                                {transaction.amountLabel}
                                            </span>
                                        </div>

                                        <div>
                                            <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-500 md:hidden">Saldo</p>
                                            <p className="text-sm font-bold text-slate-200">
                                                {formatNumber(transaction.balanceBefore)} → {formatNumber(transaction.balanceAfter)}
                                            </p>
                                        </div>

                                        <div>
                                            <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-500 md:hidden">Data</p>
                                            <p className="text-sm font-bold text-slate-300">{transaction.createdAt}</p>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </div>
                    )}
                </section>
            </div>
        </main>
    );
}
