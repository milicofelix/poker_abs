import React from 'react';

const statusStyles = {
    ok: 'border-emerald-200/25 bg-emerald-300/10 text-emerald-50',
    lobby: 'border-cyan-200/25 bg-cyan-300/10 text-cyan-50',
    warning: 'border-amber-200/25 bg-amber-300/10 text-amber-50',
};

const statusLabels = {
    ok: 'validado',
    lobby: 'lobby',
    warning: 'atenção',
};

export default function PokerFinalReviewChecklist({ table = null }) {
    const closure = table?.phaseClosure ?? null;
    const items = table?.reviewChecklist ?? closure?.checklist ?? [];

    if (!items.length) {
        return null;
    }

    return (
        <section className="rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-3 shadow-xl shadow-black/35 backdrop-blur">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p className="text-[0.64rem] font-black uppercase tracking-[0.24em] text-amber-200">
                        {closure?.phase ? `Fechamento ${closure.phase}` : 'Fechamento FASE 8'}
                    </p>
                    <h2 className="mt-1 text-base font-black text-white">
                        {closure?.title ?? 'Checklist final da mesa'}
                    </h2>
                    <p className="mt-1 text-xs font-semibold text-slate-300">
                        {closure?.summary ?? (table?.isLocalMode
                            ? 'Modo local validado como fluxo clássico, sem recursos multiplayer em tempo real.'
                            : 'Mesa do lobby validada para fluxo multiplayer atual antes da próxima fase.')}
                    </p>
                </div>

                <span className="w-fit rounded-full border border-white/10 bg-white/10 px-3 py-1 text-[0.62rem] font-black uppercase tracking-[0.16em] text-slate-200">
                    {table?.modeLabel ?? 'Mesa'}
                </span>
            </div>

            <div className="mt-3 grid gap-2 sm:grid-cols-2">
                {items.map((item) => {
                    const status = item.status ?? 'ok';

                    return (
                        <div
                            key={`${item.label}-${status}`}
                            className={[
                                'rounded-2xl border px-3 py-2 text-sm font-bold shadow-inner shadow-black/25',
                                statusStyles[status] ?? statusStyles.ok,
                            ].join(' ')}
                        >
                            <div className="flex items-center justify-between gap-3">
                                <span className="min-w-0 truncate">{item.label}</span>
                                <span className="shrink-0 rounded-full border border-white/10 bg-white/10 px-2 py-1 text-[0.56rem] font-black uppercase tracking-[0.12em]">
                                    {statusLabels[status] ?? status}
                                </span>
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
