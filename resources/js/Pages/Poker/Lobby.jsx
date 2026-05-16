import React from 'react';
import { router, usePage } from '@inertiajs/react';

function statusLabel(status) {
    const labels = {
        waiting: 'Aguardando jogadores',
        playing: 'Em andamento',
        finished: 'Finalizada',
    };

    return labels[status] ?? status;
}

function streetLabel(street) {
    const labels = {
        pre_flop: 'Pré-flop',
        flop: 'Flop',
        turn: 'Turn',
        river: 'River',
        showdown: 'Showdown',
    };

    return labels[street] ?? street;
}

export default function Lobby({ tables = [] }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    function createTable() {
        router.post('/poker/tables');
    }

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <header className="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-sm font-bold uppercase tracking-[0.3em] text-emerald-300">Poker ABS</p>
                        <h1 className="mt-2 text-3xl font-black">Lobby de mesas</h1>
                        <p className="mt-2 max-w-2xl text-sm text-slate-300">
                            Entre em uma mesa existente ou crie uma nova mesa para validar o fluxo multiplayer com estado compartilhado.
                        </p>
                    </div>

                    <div className="flex flex-col gap-3 md:items-end">
                        {user ? (
                            <div className="rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm text-slate-200">
                                Logado como <strong className="text-white">{user.name}</strong>
                            </div>
                        ) : (
                            <div className="flex flex-wrap gap-3">
                                <a
                                    href="/login"
                                    className="rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20"
                                >
                                    Entrar
                                </a>
                                <a
                                    href="/register"
                                    className="rounded-xl bg-white px-4 py-2 text-sm font-black text-slate-950 transition hover:bg-emerald-100"
                                >
                                    Criar conta
                                </a>
                            </div>
                        )}

                        <div className="flex flex-wrap gap-3 md:justify-end">
                            <a
                                href="/poker/hands"
                                className="inline-flex rounded-xl border border-white/10 bg-white/10 px-5 py-3 text-sm font-black text-white transition hover:bg-white/20"
                            >
                                Histórico de mãos
                            </a>

                            <a
                                href="/poker/ranking"
                                className="inline-flex rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-5 py-3 text-sm font-black text-emerald-100 transition hover:bg-emerald-300/20"
                            >
                                Ranking
                            </a>

                            <button
                                type="button"
                                onClick={createTable}
                                className="rounded-2xl bg-emerald-400 px-5 py-3 font-black text-emerald-950 shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-300"
                            >
                                Criar nova mesa
                            </button>
                        </div>
                    </div>
                </header>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {tables.map((table) => (
                        <article key={table.id} className="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-xl">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h2 className="text-xl font-black">{table.name}</h2>
                                    <p className="mt-1 text-sm text-slate-400">
                                        Blinds {table.smallBlind}/{table.bigBlind} • {table.playersCount}/{table.maxPlayers} jogadores
                                    </p>
                                </div>

                                <span className="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-bold text-emerald-200">
                                    {statusLabel(table.status)}
                                </span>
                            </div>

                            <div className="mt-5 rounded-2xl bg-white/5 p-4 text-sm text-slate-300">
                                {table.latestHand ? (
                                    <div className="space-y-1">
                                        <p>
                                            Mão: <strong className="text-white">{statusLabel(table.latestHand.status)}</strong>
                                        </p>
                                        <p>
                                            Rodada: <strong className="text-white">{streetLabel(table.latestHand.street)}</strong>
                                        </p>
                                        <p>
                                            Pote: <strong className="text-white">{table.latestHand.pot}</strong>
                                        </p>
                                    </div>
                                ) : (
                                    <p>Nenhuma mão criada nesta mesa ainda.</p>
                                )}
                            </div>

                            <a
                                href={table.url}
                                className="mt-5 inline-flex w-full justify-center rounded-2xl bg-white px-5 py-3 font-black text-slate-950 transition hover:bg-emerald-100"
                            >
                                Entrar na mesa
                            </a>
                        </article>
                    ))}
                </section>

                {tables.length === 0 && (
                    <div className="rounded-3xl border border-dashed border-white/20 bg-white/5 p-8 text-center text-slate-300">
                        Ainda não existe nenhuma mesa. Crie a primeira para iniciar o teste da FASE 3.2.
                    </div>
                )}
            </div>
        </main>
    );
}
