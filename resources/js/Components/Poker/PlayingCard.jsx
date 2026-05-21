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

function dealOrigin(dealFrom) {
    return {
        dealer: { '--poker-deal-x': '0px', '--poker-deal-y': '-92px', '--poker-deal-rotate': '-9deg' },
        left: { '--poker-deal-x': '-90px', '--poker-deal-y': '-58px', '--poker-deal-rotate': '-13deg' },
        right: { '--poker-deal-x': '90px', '--poker-deal-y': '-58px', '--poker-deal-rotate': '13deg' },
        bottom: { '--poker-deal-x': '0px', '--poker-deal-y': '82px', '--poker-deal-rotate': '8deg' },
    }[dealFrom] ?? { '--poker-deal-x': '0px', '--poker-deal-y': '-92px', '--poker-deal-rotate': '-9deg' };
}

function dealStyle(animate, dealIndex, dealStepMs, dealFrom, useCombinedAnimation = false) {
    if (!animate) {
        return undefined;
    }

    const delay = `${dealIndex * dealStepMs}ms`;

    return {
        ...(useCombinedAnimation ? {} : { animationDelay: delay }),
        '--poker-card-delay': delay,
        ...dealOrigin(dealFrom),
    };
}

export default function PlayingCard({
    card,
    hidden = false,
    compact = false,
    dealIndex = 0,
    dealStepMs = 170,
    dealFrom = 'dealer',
    animate = true,
}) {
    const sizeClass = compact
        ? 'h-12 w-9 shrink-0 rounded-lg text-xs sm:h-20 sm:w-14 sm:rounded-xl sm:text-xl'
        : 'h-14 w-10 shrink-0 rounded-lg text-sm sm:h-24 sm:w-16 sm:rounded-2xl sm:text-2xl';

    if (hidden) {
        return (
            <div
                style={dealStyle(animate, dealIndex, dealStepMs, dealFrom, true)}
                className={`relative flex ${sizeClass} items-center justify-center overflow-hidden border border-amber-200/40 bg-gradient-to-br from-emerald-900 via-emerald-700 to-slate-950 font-black text-amber-100 shadow-2xl shadow-black/40 ring-1 ring-white/10 ${animate ? 'poker-card-deal poker-hidden-card-pulse' : ''}`}
            >
                <div className="absolute inset-2 rounded-[1rem] border border-amber-100/20" />
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,191,36,0.20),transparent_55%)]" />
                <span className="relative text-[0.65rem] tracking-[0.18em] sm:text-base">ABS</span>
            </div>
        );
    }

    const isRed = card?.suit === 'hearts' || card?.suit === 'diamonds';
    const symbol = suitSymbol(card?.suit);
    const rank = rankLabel(card);

    return (
        <div
            style={dealStyle(animate, dealIndex, dealStepMs, dealFrom)}
            className={`group relative flex ${sizeClass} flex-col justify-between overflow-hidden border border-white/70 bg-gradient-to-br from-white via-slate-50 to-slate-200 p-1 font-black sm:p-1.5 shadow-2xl shadow-black/40 ring-1 ring-black/5 transition duration-200 hover:-translate-y-1 hover:shadow-amber-300/20 ${animate ? 'poker-card-deal' : ''} ${isRed ? 'text-red-600' : 'text-slate-950'}`}
        >
            <div className="flex items-start justify-between leading-none">
                <span>{rank}</span>
                <span className="text-xs sm:text-lg">{symbol}</span>
            </div>

            <div className="flex flex-1 items-center justify-center text-xl leading-none sm:text-4xl">
                {symbol || card?.label}
            </div>

            <div className="flex rotate-180 items-start justify-between leading-none">
                <span>{rank}</span>
                <span className="text-xs sm:text-lg">{symbol}</span>
            </div>
        </div>
    );
}
