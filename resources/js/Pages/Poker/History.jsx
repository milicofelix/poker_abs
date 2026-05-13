import React from 'react';

function statusClass(status) {
    return status === 'finished'
        ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-100'
        : 'border-yellow-400/40 bg-yellow-400/10 text-yellow-100';
}

function formatMoney(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

export default function History({ hands = [] }) {
    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <header className="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-200">
                            Poker ABS
                        </p>
                        <h1 className="mt-2 text-3xl font-black">Histórico de mãos</h1>
                        <p className="mt-2 max-w-2xl text-sm text-slate-300">
                            Acompanhe as últimas mãos persistidas no banco, com pote, street,
                            quantidade de ações e a última jogada registrada.
                        </p>
                    </div>

                    <a
                        href="/poker"
                        className="inline-flex w-fit rounded-xl bg-white px-5 py-3 font-bold text-slate-950 transition hover:bg-emerald-100"
                    >
                        Voltar para mesa
                    </a>
                </header>

                {hands.length === 0 ? (
                    <section className="rounded-3xl border border-white/10 bg-white/10 p-8 text-center shadow-2xl backdrop-blur">
                        <h2 className="text-xl font-bold">Nenhuma mão registrada ainda</h2>
                        <p className="mt-2 text-sm text-slate-300">
                            Inicie uma mão local e execute algumas ações para alimentar o histórico.
                        </p>
                    </section>
                ) : (
                    <section className="grid gap-4">
                        {hands.map((hand) => (
                            <article
                                key={hand.id}
                                className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur"
                            >
                                <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-3">
                                            <h2 className="text-xl font-black">{hand.table}</h2>
                                            <span className={`rounded-full border px-3 py-1 text-xs font-bold ${statusClass(hand.status)}`}>
                                                {hand.statusLabel}
                                            </span>
                                            <span className="rounded-full border border-white/10 bg-slate-950/40 px-3 py-1 text-xs font-bold text-slate-200">
                                                {hand.streetLabel}
                                            </span>
                                        </div>

                                        <p className="mt-2 max-w-2xl break-all text-xs text-slate-400">
                                            Código: {hand.code}
                                        </p>

                                        <a
                                            href={`/poker/hands/${hand.id}`}
                                            className="mt-4 inline-flex rounded-xl bg-emerald-400 px-4 py-2 text-sm font-bold text-emerald-950 transition hover:bg-emerald-300"
                                        >
                                            Ver detalhes
                                        </a>
                                    </div>

                                    <div className="text-left md:text-right">
                                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Início</p>
                                        <p className="font-bold text-slate-100">{hand.startedAt || '-'}</p>
                                    </div>
                                </div>

                                <div className="mt-5 grid gap-3 md:grid-cols-4">
                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Pote</p>
                                        <p className="mt-1 text-2xl font-black">{formatMoney(hand.pot)}</p>
                                    </div>

                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Aposta atual</p>
                                        <p className="mt-1 text-2xl font-black">{formatMoney(hand.currentBet)}</p>
                                    </div>

                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Ações</p>
                                        <p className="mt-1 text-2xl font-black">{hand.actionsCount}</p>
                                    </div>

                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Fim</p>
                                        <p className="mt-1 text-sm font-bold text-slate-100">{hand.finishedAt || 'Ainda em andamento'}</p>
                                    </div>
                                </div>

                                {hand.lastAction && (
                                    <div className="mt-4 rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Última ação</p>
                                        <p className="mt-2 font-bold text-slate-100">
                                            {hand.lastAction.player}: {hand.lastAction.action}
                                            {hand.lastAction.amount > 0 ? ` ${formatMoney(hand.lastAction.amount)}` : ''}
                                        </p>
                                        {hand.lastAction.message && (
                                            <p className="mt-1 text-sm text-slate-300">{hand.lastAction.message}</p>
                                        )}
                                    </div>
                                )}
                            </article>
                        ))}
                    </section>
                )}
            </div>
        </main>
    );
}
