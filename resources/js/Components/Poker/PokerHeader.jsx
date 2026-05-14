import React from 'react';
import { router, usePage } from '@inertiajs/react';

export default function PokerHeader() {
    const { auth } = usePage().props;
    const user = auth?.user;

    function logout() {
        router.post('/logout');
    }

    return (
        <header className="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl md:flex-row md:items-start md:justify-between">
            <div>
                <p className="text-sm uppercase tracking-[0.35em] text-emerald-300">Poker ABS</p>

                <h1 className="mt-2 text-3xl font-black md:text-5xl">
                    Mesa local de Texas Hold'em
                </h1>

                <p className="mt-3 max-w-3xl text-slate-300">
                    Fase 2: backend com persistência, histórico de ações e base para evoluir mesa, jogadores e ranking.
                </p>
            </div>

            <div className="flex flex-col gap-3 md:items-end">
                <nav className="flex flex-wrap gap-3 md:justify-end">
                    <a
                        href="/poker/hands"
                        className="inline-flex w-fit rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/20"
                    >
                        Histórico de mãos
                    </a>

                    <a
                        href="/poker/ranking"
                        className="inline-flex w-fit rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-4 py-3 text-sm font-bold text-emerald-100 transition hover:bg-emerald-300/20"
                    >
                        Ranking local
                    </a>

                    <a
                        href="/poker/statistics"
                        className="inline-flex w-fit rounded-xl border border-cyan-300/30 bg-cyan-300/10 px-4 py-3 text-sm font-bold text-cyan-100 transition hover:bg-cyan-300/20"
                    >
                        Estatísticas avançadas
                    </a>

                    <a
                        href="/poker/lobby"
                        className="inline-flex w-fit rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm font-bold text-amber-100 transition hover:bg-amber-300/20"
                    >
                        Lobby multiplayer
                    </a>
                </nav>

                {user ? (
                    <div className="flex flex-wrap items-center gap-3 text-sm text-slate-300 md:justify-end">
                        <span className="rounded-xl border border-white/10 bg-white/10 px-4 py-2">
                            Logado como <strong className="text-white">{user.name}</strong>
                        </span>

                        <button
                            type="button"
                            onClick={logout}
                            className="rounded-xl border border-red-300/30 bg-red-300/10 px-4 py-2 font-bold text-red-100 transition hover:bg-red-300/20"
                        >
                            Sair
                        </button>
                    </div>
                ) : (
                    <div className="flex flex-wrap gap-3 md:justify-end">
                        <a
                            href="/login"
                            className="rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20"
                        >
                            Entrar
                        </a>

                        <a
                            href="/register"
                            className="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-black text-emerald-950 transition hover:bg-emerald-300"
                        >
                            Criar conta
                        </a>
                    </div>
                )}
            </div>
        </header>
    );
}
