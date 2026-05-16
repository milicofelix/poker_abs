import React from 'react';

function actorLabel(item) {
    if (item?.actorLabel) {
        return item.actorLabel;
    }

    return item?.actor === 'opponent' ? 'Oponente' : 'Você';
}

function chipLabel(value) {
    return Number(value ?? 0).toLocaleString('pt-BR');
}

export default function PokerActionHistory({ history, compact = false }) {
    if (!history || history.length === 0) {
        return (
            <section className="rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-4 text-sm text-slate-300 shadow-xl backdrop-blur">
                <div className="flex items-center justify-between gap-3">
                    <h2 className="text-xs font-black uppercase tracking-[0.24em] text-white">Histórico da mão</h2>
                    <span className="rounded-full border border-white/10 bg-white/5 px-2 py-1 text-[0.62rem] font-bold uppercase tracking-[0.12em] text-slate-400">
                        vazio
                    </span>
                </div>
                <div className="mt-3 rounded-2xl border border-dashed border-white/10 bg-white/[0.035] px-3 py-4 text-center">
                    <p className="font-bold text-slate-200">Nenhuma ação realizada ainda.</p>
                    <p className="mt-1 text-xs leading-snug text-slate-400">As jogadas aparecerão aqui em ordem, sem ocupar espaço da mesa.</p>
                </div>
            </section>
        );
    }

    if (compact) {
        return (
            <section className="rounded-[1.5rem] border border-amber-200/15 bg-slate-950/78 p-3 shadow-2xl shadow-black/40 backdrop-blur">
                <div className="flex items-center justify-between gap-3">
                    <h2 className="text-xs font-black uppercase tracking-[0.24em] text-white">Histórico da mão</h2>
                    <span className="rounded-full border border-white/10 bg-white/5 px-2 py-1 text-[0.62rem] font-bold text-slate-300">
                        {history.length} ações
                    </span>
                </div>

                <ol className="poker-compact-scroll mt-3 flex gap-2 overflow-x-auto pb-1">
                    {history.map((item, index) => (
                        <li
                            key={`${item.actor}-${item.action}-${index}`}
                            className="min-w-[185px] rounded-2xl border border-white/10 bg-white/[0.055] px-3 py-2.5 text-xs text-slate-200 shadow-inner shadow-black/25"
                        >
                            <strong className={item.actor === 'opponent' ? 'block truncate text-amber-200' : 'block truncate text-emerald-200'}>
                                {actorLabel(item)}
                            </strong>
                            <span className="mt-1 block truncate font-semibold text-white">
                                {item.action}
                            </span>
                            <p className="mt-1 line-clamp-2 min-h-[2rem] text-[0.68rem] leading-snug text-slate-300">
                                {item.message}
                            </p>
                            <div className="mt-2 flex items-center justify-between gap-2 text-[0.68rem] font-black text-amber-100">
                                <span>#{index + 1}</span>
                                <span>🪙 {chipLabel(item.pot)}</span>
                            </div>
                        </li>
                    ))}
                </ol>
            </section>
        );
    }

    return (
        <section className="poker-compact-scroll max-h-[360px] overflow-y-auto rounded-xl border border-white/10 bg-slate-950/80 p-3 shadow-xl sm:rounded-2xl">
            <h2 className="sticky -top-3 z-10 mb-2 rounded-t-lg bg-slate-950/95 py-1 text-sm font-black uppercase tracking-[0.18em] text-white">Histórico da mão</h2>

            <ol className="space-y-1.5">
                {history.map((item, index) => (
                    <li
                        key={`${item.actor}-${item.action}-${index}`}
                        className="rounded-xl border border-white/10 bg-white/5 px-2.5 py-2 text-xs leading-snug text-slate-200"
                    >
                        <div className="flex items-center justify-between gap-1">
                            <strong className={item.actor === 'opponent' ? 'text-amber-200' : 'text-emerald-200'}>
                                {index + 1}. {actorLabel(item)} — {item.street} — {item.action}
                            </strong>

                            <span className="shrink-0 text-slate-400">
                                Pote: {chipLabel(item.pot)}
                            </span>
                        </div>

                        <p className="mt-0.5 line-clamp-2 text-slate-300">{item.message}</p>
                    </li>
                ))}
            </ol>
        </section>
    );
}
