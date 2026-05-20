import React from 'react';
import { usePage } from '@inertiajs/react';

function formatChips(value) {
    return new Intl.NumberFormat('pt-BR').format(Number(value ?? 0));
}

function amountClass(amount) {
    return Number(amount) >= 0 ? 'text-emerald-200' : 'text-red-200';
}

export default function Bankroll({ summary = {}, transactions = [] }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <header className="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p className="text-sm font-bold uppercase tracking-[0.3em] text-emerald-300">Poker ABS</p>
                            <h1 className="mt-2 text-3xl font-black">Histórico de fichas</h1>
                            <p className="mt-2 max-w-2xl text-sm text-slate-300">
                                Acompanhe buy-ins, premiações e saldo atual do seu bankroll.
                            </p>
                        </div>

                        <nav className="flex flex-wrap gap-3 lg:justify-end">
                            <a href="/poker/lobby" className="rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 py-2 text-sm font-black text-amber-100 transition hover:bg-amber-300/20">
                                Lobby
                            </a>
                            <a href="/poker/hands" className="rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-black text-white transition hover:bg-white/20">
                                Histórico
                            </a>
                            <a href="/poker/statistics" className="rounded-xl border border-cyan-300/30 bg-cyan-300/10 px-4 py-2 text-sm font-black text-cyan-100 transition hover:bg-cyan-300/20">
                                Estatísticas
                            </a>
                        </nav>
                    </div>

                    {user && (
                        <div className="mt-5 rounded-2xl border border-emerald-300/20 bg-emerald-300/10 px-4 py-3 text-sm font-bold text-emerald-100">
                            Logado como <strong className="text-white">{user.name}</strong> · Saldo atual: <strong className="text-white">{formatChips(summary.currentBalance ?? user.pokerBankroll)} fichas</strong>
                        </div>
                    )}
                </header>

                <section className="grid gap-4 md:grid-cols-4">
                    <div className="rounded-3xl border border-emerald-300/20 bg-emerald-300/10 p-5 shadow-xl">
                        <p className="text-xs font-black uppercase tracking-[0.22em] text-emerald-200">Saldo</p>
                        <p className="mt-2 text-3xl font-black">{formatChips(summary.currentBalance)}</p>
                    </div>
                    <div className="rounded-3xl border border-cyan-300/20 bg-cyan-300/10 p-5 shadow-xl">
                        <p className="text-xs font-black uppercase tracking-[0.22em] text-cyan-200">Créditos</p>
                        <p className="mt-2 text-3xl font-black">{formatChips(summary.credits)}</p>
                    </div>
                    <div className="rounded-3xl border border-red-300/20 bg-red-300/10 p-5 shadow-xl">
                        <p className="text-xs font-black uppercase tracking-[0.22em] text-red-200">Débitos</p>
                        <p className="mt-2 text-3xl font-black">{formatChips(summary.debits)}</p>
                    </div>
                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-xl">
                        <p className="text-xs font-black uppercase tracking-[0.22em] text-slate-200">Movimentações</p>
                        <p className="mt-2 text-3xl font-black">{formatChips(summary.transactionsCount)}</p>
                    </div>
                </section>

                <section className="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-xl">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-300">Ledger</p>
                            <h2 className="mt-1 text-xl font-black">Últimas movimentações</h2>
                        </div>
                    </div>

                    <div className="mt-5 overflow-hidden rounded-2xl border border-white/10">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-white/10 text-xs uppercase tracking-[0.16em] text-slate-300">
                                <tr>
                                    <th className="px-4 py-3">Tipo</th>
                                    <th className="px-4 py-3">Mesa</th>
                                    <th className="px-4 py-3">Valor</th>
                                    <th className="px-4 py-3">Saldo</th>
                                    <th className="px-4 py-3">Quando</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-white/10">
                                {transactions.map((transaction) => (
                                    <tr key={transaction.id} className="bg-slate-950/40">
                                        <td className="px-4 py-3 font-bold text-white">{transaction.typeLabel}</td>
                                        <td className="px-4 py-3 text-slate-300">{transaction.tableName ?? '—'}</td>
                                        <td className={`px-4 py-3 font-black ${amountClass(transaction.amount)}`}>
                                            {Number(transaction.amount) > 0 ? '+' : ''}{formatChips(transaction.amount)}
                                        </td>
                                        <td className="px-4 py-3 text-slate-300">
                                            {formatChips(transaction.balanceBefore)} → <strong className="text-white">{formatChips(transaction.balanceAfter)}</strong>
                                        </td>
                                        <td className="px-4 py-3 text-slate-400">{transaction.createdAtLabel ?? '—'}</td>
                                    </tr>
                                ))}

                                {transactions.length === 0 && (
                                    <tr>
                                        <td colSpan="5" className="px-4 py-8 text-center text-slate-400">
                                            Nenhuma movimentação de fichas registrada ainda.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    );
}
