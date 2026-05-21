import React from 'react';

export default function PokerPhaseTenAuditPanel({ audit = null }) {
    const blockers = audit?.blockers ?? [];
    const nextSteps = audit?.safeNextSteps ?? [];

    if (!audit || blockers.length === 0) {
        return null;
    }

    return (
        <section className="rounded-[1.5rem] border border-cyan-200/25 bg-gradient-to-br from-cyan-300/15 via-blue-300/10 to-slate-950/75 p-3 text-cyan-50 shadow-xl shadow-black/35 backdrop-blur sm:p-4">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p className="text-[0.62rem] font-black uppercase tracking-[0.24em] text-cyan-200">FASE {audit.phase}</p>
                    <h2 className="mt-1 text-base font-black text-white sm:text-lg">{audit.title ?? 'Auditoria heads-up'}</h2>
                    <p className="mt-1 text-xs font-semibold text-cyan-50/80 sm:text-sm">{audit.summary}</p>
                </div>

                <div className="grid grid-cols-2 gap-2 text-xs font-black sm:grid-cols-4 lg:min-w-[26rem]">
                    <div className="rounded-2xl border border-white/10 bg-black/20 px-3 py-2">
                        <span className="block text-[0.55rem] uppercase tracking-[0.16em] text-cyan-200">Motor</span>
                        <strong className="mt-1 block text-white">{audit.activeEngine}</strong>
                    </div>
                    <div className="rounded-2xl border border-white/10 bg-black/20 px-3 py-2">
                        <span className="block text-[0.55rem] uppercase tracking-[0.16em] text-cyan-200">Alvo</span>
                        <strong className="mt-1 block text-white">{audit.targetEngine}</strong>
                    </div>
                    <div className="rounded-2xl border border-white/10 bg-black/20 px-3 py-2">
                        <span className="block text-[0.55rem] uppercase tracking-[0.16em] text-cyan-200">Limite atual</span>
                        <strong className="mt-1 block text-white">{audit.currentEngineMaxPlayers}</strong>
                    </div>
                    <div className="rounded-2xl border border-amber-200/20 bg-amber-300/10 px-3 py-2 text-amber-50">
                        <span className="block text-[0.55rem] uppercase tracking-[0.16em] text-amber-200">3+ ativo?</span>
                        <strong className="mt-1 block">{audit.threePlusEnabled ? 'sim' : 'não'}</strong>
                    </div>
                </div>
            </div>

            <div className="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                {blockers.map((item) => (
                    <div key={item.area} className="rounded-2xl border border-white/10 bg-black/20 px-3 py-2 shadow-inner shadow-black/25">
                        <div className="flex items-center justify-between gap-2">
                            <strong className="text-xs font-black uppercase tracking-[0.16em] text-white">{item.label}</strong>
                            <span className="rounded-full border border-current/20 px-2 py-0.5 text-[0.55rem] font-black uppercase tracking-[0.14em] text-cyan-100">
                                {item.status}
                            </span>
                        </div>
                        <p className="mt-1 text-[0.68rem] font-semibold text-cyan-50/75 sm:text-xs">{item.note}</p>
                    </div>
                ))}
            </div>

            {nextSteps.length > 0 && (
                <div className="mt-3 rounded-2xl border border-emerald-200/20 bg-emerald-300/10 p-3">
                    <p className="text-[0.6rem] font-black uppercase tracking-[0.22em] text-emerald-200">Próximas etapas seguras</p>
                    <div className="mt-2 grid gap-2 md:grid-cols-3">
                        {nextSteps.map((step) => (
                            <div key={step.phase} className="rounded-xl border border-white/10 bg-black/15 px-3 py-2">
                                <strong className="block text-xs font-black text-white">{step.phase} — {step.title}</strong>
                                <span className="mt-1 block text-[0.68rem] font-semibold text-emerald-50/75">{step.goal}</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </section>
    );
}
