import React from 'react';

function statusLabel(status) {
    const labels = {
        online: 'Online',
        offline: 'Offline',
    };

    return labels[status] ?? status;
}

export default function PokerRealPlayersPanel({ players = [], loading = false, message = null, onJoin = null }) {
    return (
        <section className="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-xl">
            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-300">Jogadores reais</p>
                    <h2 className="mt-1 text-xl font-black">Mesa multiplayer</h2>
                    <p className="mt-1 text-sm text-slate-400">
                        Base da FASE 3.7: usuários reais vinculados à mesa, sem assentos automáticos ainda.
                    </p>
                </div>

                {onJoin && (
                    <button
                        type="button"
                        onClick={onJoin}
                        disabled={loading}
                        className="rounded-2xl bg-emerald-400 px-4 py-2 text-sm font-black text-emerald-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {loading ? 'Entrando...' : 'Entrar como jogador'}
                    </button>
                )}
            </div>

            {message && (
                <div className="mt-4 rounded-2xl border border-emerald-300/20 bg-emerald-400/10 px-4 py-3 text-sm font-bold text-emerald-100">
                    {message}
                </div>
            )}

            <div className="mt-4 grid gap-3 md:grid-cols-2">
                {players.length > 0 ? players.map((player) => (
                    <div key={player.id} className="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="font-black text-white">{player.nickname}</p>
                                <p className="text-xs text-slate-400">Stack inicial: {player.stack}</p>
                            </div>

                            <span className="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-bold text-emerald-200">
                                {statusLabel(player.status)}
                            </span>
                        </div>
                    </div>
                )) : (
                    <div className="rounded-2xl border border-dashed border-white/15 bg-white/5 p-4 text-sm text-slate-400 md:col-span-2">
                        Nenhum usuário real entrou nesta mesa ainda.
                    </div>
                )}
            </div>
        </section>
    );
}
