import React from 'react';

function hasFailure(status = null) {
    const label = String(status?.label ?? '').toLowerCase();

    return label.includes('falha') || label.includes('erro') || label.includes('indisponível');
}

function badgeLabel(status = null) {
    if (status?.loading) {
        return 'sync';
    }

    if (hasFailure(status)) {
        return 'atenção';
    }

    return status?.enabled ? 'online' : 'standby';
}

function containerTone(status = null) {
    if (status?.loading) {
        return 'border-sky-200/20 bg-sky-400/10 text-sky-50';
    }

    if (hasFailure(status)) {
        return 'border-amber-200/25 bg-amber-300/10 text-amber-50';
    }

    if (status?.enabled) {
        return 'border-emerald-200/20 bg-emerald-400/10 text-emerald-50';
    }

    return 'border-white/10 bg-white/10 text-slate-100';
}

function badgeTone(status = null) {
    if (status?.loading) {
        return 'bg-sky-400/20 text-sky-100 ring-1 ring-sky-300/40';
    }

    if (hasFailure(status)) {
        return 'bg-amber-300/20 text-amber-100 ring-1 ring-amber-200/40';
    }

    if (status?.enabled) {
        return 'bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/40';
    }

    return 'bg-slate-900/50 text-slate-300 ring-1 ring-white/10';
}

export default function PokerRealtimeStatus({ status, title = 'Multiplayer' }) {
    return (
        <div className={[
            'flex items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-sm shadow-xl backdrop-blur transition duration-200',
            containerTone(status),
        ].join(' ')}>
            <div className="min-w-0">
                <p className="text-xs font-bold uppercase tracking-[0.25em] text-current/75">
                    {title}
                </p>
                <p className="truncate font-semibold text-white">
                    {status?.label ?? 'Tempo real indisponível'}
                </p>
            </div>

            <span
                className={[
                    'shrink-0 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.12em]',
                    badgeTone(status),
                ].join(' ')}
            >
                {badgeLabel(status)}
            </span>
        </div>
    );
}
