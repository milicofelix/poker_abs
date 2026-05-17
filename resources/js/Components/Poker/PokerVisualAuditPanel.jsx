import React from 'react';

function statusClasses(status) {
    const tones = {
        mapped: 'border-emerald-200/25 bg-emerald-300/10 text-emerald-100',
        protected: 'border-cyan-200/25 bg-cyan-300/10 text-cyan-100',
        'needs-polish': 'border-amber-200/30 bg-amber-300/10 text-amber-100',
        'lobby-only': 'border-violet-200/25 bg-violet-300/10 text-violet-100',
    };

    return tones[status] ?? 'border-white/10 bg-white/5 text-slate-100';
}

export default function PokerVisualAuditPanel({ audit = null }) {
    if (!audit) {
        return null;
    }

    const checklist = audit.checklist ?? [];
    const nextSteps = audit.nextSteps ?? [];
    const guardrails = audit.guardrails ?? [];

    return (
        <section className="overflow-hidden rounded-[1.5rem] border border-emerald-200/15 bg-slate-950/75 shadow-xl shadow-black/35 backdrop-blur">
            <div className="border-b border-white/10 bg-gradient-to-r from-emerald-300/12 via-cyan-300/8 to-transparent px-4 py-3">
                <p className="text-[0.62rem] font-black uppercase tracking-[0.24em] text-emerald-200">FASE {audit.phase}</p>
                <h2 className="mt-1 text-base font-black text-white">{audit.title}</h2>
                <p className="mt-1 text-xs font-semibold leading-relaxed text-slate-300">{audit.summary}</p>
            </div>

            <div className="space-y-3 p-3">
                <div className="grid gap-2 md:grid-cols-2">
                    {checklist.map((item) => (
                        <div
                            key={`${item.area}-${item.status}`}
                            className={`rounded-2xl border px-3 py-2 ${statusClasses(item.status)}`}
                        >
                            <span className="text-[0.55rem] font-black uppercase tracking-[0.2em] opacity-80">{item.area} · {item.status}</span>
                            <p className="mt-1 text-xs font-bold leading-relaxed">{item.label}</p>
                        </div>
                    ))}
                </div>

                <div className="grid gap-3 lg:grid-cols-2">
                    <div className="rounded-2xl border border-amber-200/15 bg-amber-300/10 p-3">
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.22em] text-amber-200">Próximos polimentos</p>
                        <ul className="mt-2 space-y-1.5 text-xs font-semibold text-amber-50/90">
                            {nextSteps.map((step) => <li key={step}>• {step}</li>)}
                        </ul>
                    </div>

                    <div className="rounded-2xl border border-cyan-200/15 bg-cyan-300/10 p-3">
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.22em] text-cyan-200">Travas de segurança</p>
                        <ul className="mt-2 space-y-1.5 text-xs font-semibold text-cyan-50/90">
                            {guardrails.map((guardrail) => <li key={guardrail}>• {guardrail}</li>)}
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    );
}
