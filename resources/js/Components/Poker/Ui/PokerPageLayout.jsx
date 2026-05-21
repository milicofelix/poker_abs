import React from 'react';
import { PokerBadge, PokerButton, PokerSectionHeader, PokerSurface } from './PokerDesignSystem';

export function PokerPageShell({ tone = 'emerald', maxWidth = 'max-w-7xl', children }) {
    const gradients = {
        emerald: 'from-slate-950 via-emerald-950 to-slate-900',
        violet: 'from-slate-950 via-violet-950 to-slate-900',
        amber: 'from-slate-950 via-amber-950/80 to-slate-900',
        slate: 'from-slate-950 via-slate-900 to-slate-950',
    };

    return (
        <main className={`min-h-screen bg-gradient-to-br ${gradients[tone] ?? gradients.emerald} p-4 text-white sm:p-6`}>
            <div className={`mx-auto flex ${maxWidth} flex-col gap-6`}>
                {children}
            </div>
        </main>
    );
}

export function PokerPageHero({ eyebrow, title, description, tone = 'emerald', actions = null, meta = null }) {
    const accent = { emerald: 'bg-emerald-300/60', violet: 'bg-violet-300/60', amber: 'bg-amber-300/60', cyan: 'bg-cyan-300/60' }[tone] ?? 'bg-emerald-300/60';

    return (
        <PokerSurface className="relative overflow-hidden p-5 sm:p-6" tone="soft">
            <div className={`absolute inset-x-0 top-0 h-1 ${accent}`} />
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <PokerSectionHeader eyebrow={eyebrow} title={title} description={description} />
                {actions ? <nav className="flex flex-wrap gap-2 lg:justify-end">{actions}</nav> : null}
            </div>
            {meta ? <div className="mt-4 flex flex-wrap gap-2">{meta}</div> : null}
        </PokerSurface>
    );
}

export function PokerNavButton({ href, tone = 'secondary', children }) {
    return (
        <PokerButton as="a" href={href} tone={tone} className="min-h-11 px-4 text-xs sm:text-sm">
            {children}
        </PokerButton>
    );
}

export function PokerFlashMessage({ success = null, error = null }) {
    const message = error || success;

    if (!message) {
        return null;
    }

    return (
        <PokerSurface tone={error ? 'danger' : 'emerald'} className="p-4">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-sm font-bold text-white">{message}</p>
                <PokerBadge tone={error ? 'danger' : 'success'}>{error ? 'Atenção' : 'Sucesso'}</PokerBadge>
            </div>
        </PokerSurface>
    );
}

export function PokerMetricCard({ label, value, description = null, tone = 'soft' }) {
    return (
        <PokerSurface as="article" tone={tone} className="p-4 sm:p-5">
            <p className="text-[0.62rem] font-black uppercase tracking-[0.24em] text-slate-400">{label}</p>
            <strong className="mt-2 block text-2xl font-black text-white sm:text-3xl">{value}</strong>
            {description ? <p className="mt-1 text-xs font-semibold text-slate-300">{description}</p> : null}
        </PokerSurface>
    );
}

export function PokerFilterPill({ active = false, children, className = '', ...props }) {
    return (
        <button
            type="button"
            className={`rounded-full border px-4 py-2 text-sm font-black transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 ${active ? 'border-emerald-200 bg-emerald-300 text-emerald-950' : 'border-white/10 bg-white/10 text-slate-200 hover:bg-white/20'} ${className}`}
            {...props}
        >
            {children}
        </button>
    );
}
