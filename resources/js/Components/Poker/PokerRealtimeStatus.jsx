import React from 'react';

export default function PokerRealtimeStatus({ status, title = 'Multiplayer' }) {
    const active = Boolean(status?.enabled);

    return (
        <div className="flex items-center justify-between rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm shadow-xl backdrop-blur">
            <div>
                <p className="text-xs font-bold uppercase tracking-[0.25em] text-emerald-200">
                    {title}
                </p>
                <p className="font-semibold text-white">
                    {status?.label ?? 'Tempo real indisponível'}
                </p>
            </div>

            <span
                className={[
                    'rounded-full px-3 py-1 text-xs font-bold',
                    active
                        ? 'bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/40'
                        : 'bg-slate-900/50 text-slate-300 ring-1 ring-white/10',
                ].join(' ')}
            >
                {active ? 'online' : 'standby'}
            </span>
        </div>
    );
}
