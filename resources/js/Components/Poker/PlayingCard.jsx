import React from 'react';

export default function PlayingCard({ card, hidden = false }) {
    if (hidden) {
        return (
            <div className="flex h-28 w-20 items-center justify-center rounded-2xl border border-emerald-200/30 bg-gradient-to-br from-emerald-800 to-slate-950 text-xl font-black text-emerald-200 shadow-lg">
                ABS
            </div>
        );
    }

    const isRed = card.suit === 'hearts' || card.suit === 'diamonds';

    return (
        <div className={`flex h-28 w-20 items-center justify-center rounded-2xl border bg-white text-2xl font-black shadow-lg ${isRed ? 'text-red-600' : 'text-slate-950'}`}>
            {card.label}
        </div>
    );
}
