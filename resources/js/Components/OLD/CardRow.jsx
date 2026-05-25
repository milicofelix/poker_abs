import React from 'react';
import PlayingCard from './PlayingCard';

function cardKey(card) {
    return `${String(card?.rank ?? '').trim()}-${String(card?.suit ?? '').trim()}`;
}

function isWinningCard(card, winningCards = []) {
    if (!card || !Array.isArray(winningCards) || winningCards.length === 0) {
        return false;
    }

    const target = cardKey(card);

    return winningCards.some((winningCard) => cardKey(winningCard) === target);
}

export default function CardRow({
    title,
    cards = [],
    hiddenCount = 0,
    align = 'center',
    tone = 'default',
    animate = true,
    dealStartIndex = 0,
    dealStepMs = 170,
    dealFrom = 'dealer',
    winningCards = [],
}) {
    const alignment = align === 'left' ? 'justify-start text-left' : 'justify-center text-center';
    const rowFlow = tone === 'hero'
        ? 'flex-nowrap overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible sm:pb-0'
        : 'flex-nowrap overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible sm:pb-0';
    const toneClass = tone === 'hero'
        ? 'border-amber-300/20 bg-black/20 p-1.5 shadow-inner shadow-black/40 sm:p-3'
        : 'border-white/10 bg-white/[0.04] p-1.5 sm:p-3';

    return (
        <section className={`rounded-2xl border ${toneClass} min-w-0`}>
            <h2 className={`mb-1 text-[0.56rem] font-black uppercase tracking-[0.16em] text-amber-100/90 sm:mb-2 sm:text-[0.65rem] sm:tracking-[0.24em] ${align === 'left' ? '' : 'text-center'}`}>
                {title}
            </h2>

            <div className={`poker-card-scroll flex ${rowFlow} gap-1 sm:gap-2 ${alignment}`}>
                {cards.map((card, index) => (
                    <PlayingCard
                        key={`${card.label}-${index}`}
                        card={card}
                        dealIndex={dealStartIndex + index}
                        dealStepMs={dealStepMs}
                        dealFrom={dealFrom}
                        animate={animate}
                        isWinningCard={isWinningCard(card, winningCards)}
                    />
                ))}

                {Array.from({ length: hiddenCount }).map((_, index) => (
                    <PlayingCard
                        key={`hidden-${index}`}
                        hidden
                        dealIndex={dealStartIndex + cards.length + index}
                        dealStepMs={dealStepMs}
                        dealFrom={dealFrom}
                        animate={animate}
                    />
                ))}
            </div>
        </section>
    );
}
