import React from 'react';

const surfaceClasses = {
    default: 'border-white/10 bg-slate-950/70 shadow-2xl shadow-black/35',
    soft: 'border-white/10 bg-white/[0.06] shadow-xl shadow-black/20',
    emerald: 'border-emerald-200/20 bg-emerald-300/10 shadow-xl shadow-emerald-950/20',
    amber: 'border-amber-200/20 bg-amber-300/10 shadow-xl shadow-amber-950/20',
    cyan: 'border-cyan-200/20 bg-cyan-300/10 shadow-xl shadow-cyan-950/20',
    danger: 'border-red-300/25 bg-red-400/10 shadow-xl shadow-red-950/20',
};

const badgeClasses = {
    success: 'border-emerald-200/25 bg-emerald-300/10 text-emerald-100',
    warning: 'border-amber-200/25 bg-amber-300/10 text-amber-100',
    info: 'border-cyan-200/25 bg-cyan-300/10 text-cyan-100',
    neutral: 'border-white/10 bg-white/10 text-slate-200',
    danger: 'border-red-300/25 bg-red-400/10 text-red-100',
    violet: 'border-violet-200/25 bg-violet-300/10 text-violet-100',
    fuchsia: 'border-fuchsia-200/25 bg-fuchsia-300/10 text-fuchsia-100',
};

const buttonClasses = {
    primary: 'border-emerald-200/20 bg-emerald-300 text-emerald-950 hover:bg-emerald-200 focus-visible:ring-emerald-200',
    secondary: 'border-white/10 bg-white/10 text-white hover:bg-white/20 focus-visible:ring-white/40',
    warning: 'border-amber-200/25 bg-amber-300/15 text-amber-100 hover:bg-amber-300/25 focus-visible:ring-amber-200',
    danger: 'border-red-300/25 bg-red-400/15 text-red-100 hover:bg-red-400/25 focus-visible:ring-red-200',
    ghost: 'border-transparent bg-transparent text-slate-200 hover:bg-white/10 focus-visible:ring-white/30',
};

export function PokerSurface({ as: Component = 'section', tone = 'default', className = '', children, ...props }) {
    return (
        <Component
            className={`rounded-[1.6rem] border backdrop-blur ${surfaceClasses[tone] ?? surfaceClasses.default} ${className}`}
            {...props}
        >
            {children}
        </Component>
    );
}

export function PokerSectionHeader({ eyebrow, title, description, action = null, className = '' }) {
    return (
        <div className={`flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between ${className}`}>
            <div>
                {eyebrow ? <p className="text-[0.62rem] font-black uppercase tracking-[0.26em] text-emerald-200">{eyebrow}</p> : null}
                {title ? <h2 className="mt-1 text-base font-black text-white md:text-lg">{title}</h2> : null}
                {description ? <p className="mt-1 max-w-3xl text-xs font-semibold leading-relaxed text-slate-300 md:text-sm">{description}</p> : null}
            </div>

            {action ? <div className="flex shrink-0 flex-wrap gap-2">{action}</div> : null}
        </div>
    );
}

export function PokerBadge({ tone = 'neutral', children, className = '' }) {
    return (
        <span className={`inline-flex items-center rounded-full border px-3 py-1 text-[0.65rem] font-black uppercase tracking-[0.16em] ${badgeClasses[tone] ?? badgeClasses.neutral} ${className}`}>
            {children}
        </span>
    );
}

export function PokerButton({ as: Component = 'button', tone = 'secondary', children, className = '', ...props }) {
    return (
        <Component
            className={`inline-flex items-center justify-center rounded-2xl border px-4 py-2 text-sm font-black transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 disabled:cursor-not-allowed disabled:opacity-50 ${buttonClasses[tone] ?? buttonClasses.secondary} ${className}`}
            {...props}
        >
            {children}
        </Component>
    );
}

export function PokerEmptyState({ eyebrow = 'Estado vazio', title, description, action = null, tone = 'soft' }) {
    return (
        <PokerSurface tone={tone} className="p-5 text-center">
            <p className="text-[0.62rem] font-black uppercase tracking-[0.24em] text-slate-400">{eyebrow}</p>
            <h3 className="mt-2 text-lg font-black text-white">{title}</h3>
            {description ? <p className="mx-auto mt-2 max-w-xl text-sm font-semibold leading-relaxed text-slate-300">{description}</p> : null}
            {action ? <div className="mt-4 flex justify-center">{action}</div> : null}
        </PokerSurface>
    );
}

export const pokerDesignSystemClassNames = {
    surface: surfaceClasses,
    badge: badgeClasses,
    button: buttonClasses,
};
