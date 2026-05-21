import React from 'react';
import { PokerBadge, PokerSectionHeader, PokerSurface } from './Ui/PokerDesignSystem';

function statusTone(status) {
    const tones = {
        mapped: 'success',
        protected: 'info',
        'needs-polish': 'warning',
        'local-safe': 'violet',
        'realtime-safe': 'info',
    };

    return tones[status] ?? 'neutral';
}

function surfaceTone(status) {
    const tones = {
        mapped: 'emerald',
        protected: 'cyan',
        'needs-polish': 'amber',
        'local-safe': 'soft',
        'realtime-safe': 'cyan',
    };

    return tones[status] ?? 'soft';
}

function tokenEntries(tokens = {}) {
    return Object.entries(tokens).flatMap(([group, values]) => (
        Object.entries(values ?? {}).map(([name, value]) => ({ group, name, value }))
    ));
}

export default function PokerPhaseThirteenDesignAuditPanel({ audit = null }) {
    if (!audit) {
        return null;
    }

    const checklist = audit.checklist ?? [];
    const nextSteps = audit.nextSteps ?? [];
    const guardrails = audit.guardrails ?? [];
    const tokens = tokenEntries(audit.designTokens ?? {});
    const foundation = audit.foundation ?? [];

    return (
        <PokerSurface tone="default" className="overflow-hidden">
            <div className="border-b border-white/10 bg-gradient-to-r from-fuchsia-300/15 via-emerald-300/10 to-transparent px-4 py-3">
                <PokerSectionHeader
                    eyebrow={`FASE ${audit.phase}`}
                    title={audit.title}
                    description={audit.summary}
                    action={<PokerBadge tone="fuchsia">Modo {audit.mode}</PokerBadge>}
                />
            </div>

            <div className="space-y-3 p-3">
                <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    {checklist.map((item) => (
                        <PokerSurface
                            key={`${item.area}-${item.status}`}
                            as="article"
                            tone={surfaceTone(item.status)}
                            className="p-3"
                        >
                            <PokerBadge tone={statusTone(item.status)}>{item.area} · {item.status}</PokerBadge>
                            <p className="mt-2 text-xs font-bold leading-relaxed text-slate-100">{item.label}</p>
                        </PokerSurface>
                    ))}
                </div>

                <div className="grid gap-3 xl:grid-cols-[1fr_1fr_1fr_0.9fr]">
                    <PokerSurface tone="emerald" className="p-3">
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.22em] text-emerald-200">Componentes base</p>
                        <div className="mt-2 space-y-2">
                            {foundation.map((item) => (
                                <div key={item.component} className="rounded-2xl border border-white/10 bg-black/15 p-2">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <strong className="text-xs font-black text-white">{item.component}</strong>
                                        <PokerBadge tone="success">{item.status}</PokerBadge>
                                    </div>
                                    <p className="mt-1 text-[0.7rem] font-semibold leading-relaxed text-emerald-50/80">{item.purpose}</p>
                                </div>
                            ))}
                        </div>
                    </PokerSurface>

                    <PokerSurface tone="soft" className="p-3">
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.22em] text-slate-300">Tokens visuais base</p>
                        <div className="mt-2 grid gap-1.5 text-[0.68rem] font-bold text-slate-100/90">
                            {tokens.map((token) => (
                                <div key={`${token.group}-${token.name}`} className="rounded-xl border border-white/10 bg-black/15 px-2 py-1.5">
                                    <span className="text-emerald-200">{token.group}.{token.name}</span>
                                    <span className="ml-1 text-slate-100/70">{token.value}</span>
                                </div>
                            ))}
                        </div>
                    </PokerSurface>

                    <PokerSurface tone="amber" className="p-3">
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.22em] text-amber-200">Próximas etapas</p>
                        <ul className="mt-2 space-y-1.5 text-xs font-semibold text-amber-50/90">
                            {nextSteps.map((step) => <li key={step}>• {step}</li>)}
                        </ul>
                    </PokerSurface>

                    <PokerSurface tone="cyan" className="p-3">
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.22em] text-cyan-200">Travas de segurança</p>
                        <ul className="mt-2 space-y-1.5 text-xs font-semibold text-cyan-50/90">
                            {guardrails.map((guardrail) => <li key={guardrail}>• {guardrail}</li>)}
                        </ul>
                    </PokerSurface>
                </div>
            </div>
        </PokerSurface>
    );
}
