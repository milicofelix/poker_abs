import React from 'react';

function suitSymbol(suit) {
    return {
        hearts: '♥',
        diamonds: '♦',
        clubs: '♣',
        spades: '♠',
    }[suit] ?? '';
}

function rankLabel(card) {
    return String(card?.rank ?? card?.label ?? '').replace(/[♥♦♣♠]/g, '').trim() || card?.label;
}

export default function PlayingCard({ card, hidden = false, compact = false, dealIndex = 0, animate = true }) {
    const sizeClass = compact
        ? 'h-20 w-14 shrink-0 rounded-2xl text-lg sm:h-28 sm:w-20 sm:text-2xl'
        : 'h-24 w-16 shrink-0 rounded-[1.15rem] text-xl sm:h-32 sm:w-24 sm:rounded-[1.35rem] sm:text-3xl';

    if (hidden) {
        return (
            <div
                style={animate ? { animationDelay: `${dealIndex * 70}ms` } : undefined}
                className={`relative flex ${sizeClass} items-center justify-center overflow-hidden border border-amber-200/40 bg-gradient-to-br from-emerald-900 via-emerald-700 to-slate-950 font-black text-amber-100 shadow-2xl shadow-black/40 ring-1 ring-white/10 ${animate ? 'poker-card-deal poker-hidden-card-pulse' : ''}`}
            >
                <div className="absolute inset-2 rounded-[1rem] border border-amber-100/20" />
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,191,36,0.20),transparent_55%)]" />
                <span className="relative text-base tracking-[0.25em] sm:text-lg">ABS</span>
            </div>
        );
    }

    const isRed = card?.suit === 'hearts' || card?.suit === 'diamonds';
    const symbol = suitSymbol(card?.suit);
    const rank = rankLabel(card);

    return (
        <div
            style={animate ? { animationDelay: `${dealIndex * 70}ms` } : undefined}
            className={`group relative flex ${sizeClass} flex-col justify-between overflow-hidden border border-white/70 bg-gradient-to-br from-white via-slate-50 to-slate-200 p-2 font-black shadow-2xl shadow-black/40 ring-1 ring-black/5 transition duration-200 hover:-translate-y-1 hover:shadow-amber-300/20 ${animate ? 'poker-card-deal' : ''} ${isRed ? 'text-red-600' : 'text-slate-950'}`}
        >
            <div className="flex items-start justify-between leading-none">
                <span>{rank}</span>
                <span className="text-base sm:text-xl">{symbol}</span>
            </div>

            <div className="flex flex-1 items-center justify-center text-3xl leading-none sm:text-5xl">
                {symbol || card?.label}
            </div>

            <div className="flex rotate-180 items-start justify-between leading-none">
                <span>{rank}</span>
                <span className="text-base sm:text-xl">{symbol}</span>
            </div>
        </div>
    );
}
