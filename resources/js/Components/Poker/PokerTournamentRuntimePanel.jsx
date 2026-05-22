import React from 'react';

function formatBlindLabel(runtime) {
    if (!runtime) {
        return '-';
    }

    return `${runtime.smallBlind ?? '-'} / ${runtime.bigBlind ?? '-'}`;
}

export default function PokerTournamentRuntimePanel({ runtime = null, compact = false }) {
    if (!runtime) {
        return null;
    }

    return (
        <section className="rounded-[1.5rem] border border-amber-200/20 bg-gradient-to-br from-amber-300/15 via-emerald-300/10 to-slate-950/85 p-4 shadow-2xl shadow-black/40 backdrop-blur">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-[0.62rem] font-black uppercase tracking-[0.22em] text-amber-200">
                        Torneio real
                    </p>
                    <h3 className="mt-1 text-base font-black text-white">
                        {runtime.name ?? 'Mesa de torneio'}
                    </h3>
                    {!compact && (
                        <p className="mt-1 text-xs font-semibold text-emerald-50/75">
                            {runtime.message ?? 'Runtime sincronizado com a engine multiplayer.'}
                        </p>
                    )}
                </div>

                <span className="rounded-full border border-emerald-200/25 bg-emerald-300/15 px-3 py-1 text-[0.62rem] font-black uppercase tracking-[0.16em] text-emerald-100">
                    {runtime.canStartNextHand ? 'próxima mão pronta' : runtime.isHandFinished ? 'showdown' : 'ao vivo'}
                </span>
            </div>

            <div className="mt-4 grid grid-cols-2 gap-2">
                <div className="rounded-2xl border border-white/10 bg-slate-950/55 px-3 py-2">
                    <span className="block text-[0.58rem] font-black uppercase tracking-[0.18em] text-slate-400">Blinds</span>
                    <strong className="mt-1 block text-sm font-black text-white">{formatBlindLabel(runtime)}</strong>
                </div>
                <div className="rounded-2xl border border-white/10 bg-slate-950/55 px-3 py-2">
                    <span className="block text-[0.58rem] font-black uppercase tracking-[0.18em] text-slate-400">Nível</span>
                    <strong className="mt-1 block text-sm font-black text-white">{runtime.blindLevel ?? 1}</strong>
                </div>
                <div className="rounded-2xl border border-white/10 bg-slate-950/55 px-3 py-2">
                    <span className="block text-[0.58rem] font-black uppercase tracking-[0.18em] text-slate-400">Ativos</span>
                    <strong className="mt-1 block text-sm font-black text-white">{runtime.activePlayers ?? 0}</strong>
                </div>
                <div className="rounded-2xl border border-white/10 bg-slate-950/55 px-3 py-2">
                    <span className="block text-[0.58rem] font-black uppercase tracking-[0.18em] text-slate-400">Final table</span>
                    <strong className="mt-1 block text-sm font-black text-white">{runtime.isFinalTable ? 'Sim' : 'Não'}</strong>
                </div>
            </div>

            {runtime.canStartNextHand && (
                <div className="mt-3 rounded-2xl border border-amber-200/25 bg-amber-300/10 px-3 py-2 text-xs font-bold text-amber-50">
                    Jogadores ativos reencaixados. Clique em <span className="text-white">Iniciar nova mão</span> para continuar o torneio na engine real.
                </div>
            )}
        </section>
    );
}
