import React from 'react';

export default function PokerHeader() {
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

            <nav className="flex flex-wrap gap-3">
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
            </nav>
        </header>
    );
}
