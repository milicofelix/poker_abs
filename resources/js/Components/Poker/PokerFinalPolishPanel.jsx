import React from 'react';

export default function PokerFinalPolishPanel({ polish = null }) {
    const items = polish?.checklist ?? [];

    if (!polish || items.length === 0) {
        return null;
    }

    return (
        <section className="rounded-[1.5rem] border border-amber-200/25 bg-gradient-to-br from-amber-300/15 via-emerald-300/10 to-slate-950/70 p-3 text-amber-50 shadow-xl shadow-black/35 backdrop-blur sm:p-4">
            <div className="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p className="text-[0.62rem] font-black uppercase tracking-[0.24em] text-amber-200">FASE {polish.phase}</p>
                    <h2 className="mt-1 text-base font-black text-white sm:text-lg">{polish.title ?? 'Polimento final'}</h2>
                    <p className="mt-1 text-xs font-semibold text-amber-50/80 sm:text-sm">{polish.summary}</p>
                </div>

                {polish.nextPhase && (
                    <div className="rounded-2xl border border-emerald-200/20 bg-emerald-300/10 px-3 py-2 text-xs font-bold text-emerald-50 lg:max-w-xs">
                        <span className="block text-[0.56rem] font-black uppercase tracking-[0.2em] text-emerald-200">Próxima fase</span>
                        <strong className="mt-1 block text-white">FASE {polish.nextPhase.phase} — {polish.nextPhase.title}</strong>
                        <span className="mt-1 block text-emerald-50/75">{polish.nextPhase.note}</span>
                    </div>
                )}
            </div>

            <div className="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                {items.map((item) => (
                    <div key={item.area} className="rounded-2xl border border-white/10 bg-black/20 px-3 py-2 shadow-inner shadow-black/25">
                        <div className="flex items-center justify-between gap-2">
                            <strong className="text-xs font-black uppercase tracking-[0.16em] text-white">{item.label}</strong>
                            <span className="rounded-full border border-current/20 px-2 py-0.5 text-[0.55rem] font-black uppercase tracking-[0.14em] text-amber-100">
                                {item.status}
                            </span>
                        </div>
                        <p className="mt-1 text-[0.68rem] font-semibold text-amber-50/75 sm:text-xs">{item.note}</p>
                    </div>
                ))}
            </div>
        </section>
    );
}
