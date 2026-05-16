import React from 'react';
import { router, usePage } from '@inertiajs/react';

export default function PokerHeader({ compact = false, table = null, rightSlot = null }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    function logout() {
        router.post('/logout');
    }

    if (compact) {
        return (
            <header className="sticky top-2 z-30 flex items-center justify-between gap-2 rounded-2xl border border-amber-200/20 bg-slate-950/88 px-3 py-2 shadow-2xl shadow-black/50 backdrop-blur md:rounded-[1.75rem] md:px-5 md:py-3">
                <a href="/poker/lobby" className="flex items-center gap-2 rounded-xl px-1 py-1 transition hover:bg-white/5">
                    <span className="text-2xl text-amber-300 md:text-4xl">♠</span>
                    <span className="leading-none">
                        <span className="block text-base font-black uppercase tracking-[0.08em] text-white md:text-2xl">Poker</span>
                        <span className="block text-[0.58rem] font-black uppercase tracking-[0.2em] text-amber-300 md:text-xs">Inteligente</span>
                    </span>
                </a>

                <div className="hidden min-w-0 flex-1 items-center justify-center gap-2 md:flex">
                    <span className="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-slate-200">
                        {table?.name ?? 'Mesa de poker'}
                    </span>
                    {table?.bigBlind && (
                        <span className="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-slate-200">
                            Blinds {table?.smallBlind ?? 10} / {table.bigBlind}
                        </span>
                    )}
                    {table?.id && (
                        <span className="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-slate-200">
                            ID da mesa: {table.id}
                        </span>
                    )}
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    {rightSlot}

                    <nav className="hidden items-center gap-2 lg:flex" aria-label="Navegação do poker">
                        <a
                            href="/poker/hands"
                            className="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-black text-white transition hover:bg-white/20"
                        >
                            Histórico
                        </a>

                        <a
                            href="/poker/ranking"
                            className="rounded-xl border border-emerald-300/20 bg-emerald-300/10 px-3 py-2 text-xs font-black text-emerald-100 transition hover:bg-emerald-300/20"
                        >
                            Ranking
                        </a>

                        <a
                            href="/poker/statistics"
                            className="rounded-xl border border-cyan-300/20 bg-cyan-300/10 px-3 py-2 text-xs font-black text-cyan-100 transition hover:bg-cyan-300/20"
                        >
                            Estatísticas
                        </a>
                    </nav>

                    {table?.lobbyUrl && (
                        <a
                            href={table.lobbyUrl}
                            className="hidden rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-black text-white transition hover:bg-white/20 sm:inline-flex"
                        >
                            Lobby
                        </a>
                    )}

                    <a
                        href="/poker/hands"
                        className="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-black text-white transition hover:bg-white/20 lg:hidden"
                        title="Abrir histórico de mãos"
                    >
                        Hist.
                    </a>

                    {user ? (
                        <button
                            type="button"
                            onClick={logout}
                            className="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-black text-white transition hover:bg-white/20"
                        >
                            Sair
                        </button>
                    ) : (
                        <a
                            href="/login"
                            className="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-black text-white transition hover:bg-white/20"
                        >
                            Entrar
                        </a>
                    )}
                </div>
            </header>
        );
    }

    return (
        <header className="flex flex-col gap-4 rounded-[2rem] border border-amber-200/15 bg-white/[0.06] p-6 shadow-2xl shadow-black/40 backdrop-blur md:flex-row md:items-start md:justify-between">
            <div>
                <p className="text-sm uppercase tracking-[0.35em] text-emerald-300">Poker ABS</p>

                <h1 className="mt-2 text-3xl font-black md:text-5xl">
                    Poker Inteligente
                </h1>

                <p className="mt-3 max-w-3xl text-slate-300">
                    Fase 5: mesa com visual poker room, destaque de turno, pote central e experiência mais próxima de cassino.
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
