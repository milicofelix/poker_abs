import React from 'react';

function resolveToneClasses(tone) {
    const tones = {
        error: 'border-rose-300/35 bg-rose-500/15 text-rose-50 shadow-rose-950/30',
        warning: 'border-amber-200/35 bg-amber-300/15 text-amber-50 shadow-amber-950/30',
        success: 'border-emerald-200/35 bg-emerald-400/15 text-emerald-50 shadow-emerald-950/30',
        info: 'border-sky-200/25 bg-sky-400/10 text-sky-50 shadow-sky-950/20',
        neutral: 'border-white/10 bg-white/[0.07] text-slate-100 shadow-black/30',
    };

    return tones[tone] ?? tones.neutral;
}

function resolveDotClasses(tone) {
    const tones = {
        error: 'bg-rose-300 shadow-[0_0_14px_rgba(253,164,175,0.8)]',
        warning: 'bg-amber-200 shadow-[0_0_14px_rgba(253,230,138,0.8)]',
        success: 'bg-emerald-300 shadow-[0_0_14px_rgba(110,231,183,0.8)]',
        info: 'bg-sky-300 shadow-[0_0_14px_rgba(125,211,252,0.8)]',
        neutral: 'bg-slate-300 shadow-[0_0_14px_rgba(203,213,225,0.45)]',
    };

    return tones[tone] ?? tones.neutral;
}

function stateIcon(tone) {
    const icons = {
        error: '⚠️',
        warning: '⏳',
        success: '✅',
        info: 'ℹ️',
        neutral: '🎲',
    };

    return icons[tone] ?? icons.neutral;
}

export default function PokerInterfaceStateBanner({ state }) {
    if (!state?.visible) {
        return null;
    }

    const tone = state.tone ?? 'neutral';

    return (
        <section
            className={[
                'relative overflow-hidden rounded-[1.35rem] border px-4 py-3 shadow-2xl backdrop-blur-md transition duration-300',
                resolveToneClasses(tone),
            ].join(' ')}
            role={tone === 'error' ? 'alert' : 'status'}
            aria-live={tone === 'error' ? 'assertive' : 'polite'}
        >
            <div className="pointer-events-none absolute inset-0 bg-[linear-gradient(120deg,rgba(255,255,255,0.11),transparent_34%,transparent_72%,rgba(255,255,255,0.06))]" />

            <div className="relative z-10 flex items-start gap-3">
                <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-black/25 text-lg shadow-inner shadow-black/25">
                    {stateIcon(tone)}
                </span>

                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className={['h-2.5 w-2.5 rounded-full', resolveDotClasses(tone)].join(' ')} />
                        <h2 className="text-xs font-black uppercase tracking-[0.22em] text-current sm:text-sm">
                            {state.title}
                        </h2>
                    </div>

                    {state.description && (
                        <p className="mt-1 text-sm font-semibold leading-snug text-current/85">
                            {state.description}
                        </p>
                    )}
                </div>

                {state.badge && (
                    <span className="shrink-0 rounded-full border border-white/15 bg-black/25 px-3 py-1 text-[0.65rem] font-black uppercase tracking-[0.18em] text-current/85">
                        {state.badge}
                    </span>
                )}
            </div>
        </section>
    );
}
