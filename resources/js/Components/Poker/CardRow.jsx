import React from 'react';
import PlayingCard from './PlayingCard';

export default function CardRow({ title, cards, hiddenCount = 0 }) {
    return (
        <section>
            <h2 className="mb-3 text-sm font-semibold uppercase tracking-[0.25em] text-emerald-200/80">
                {title}
            </h2>

            <div className="flex flex-wrap gap-3">
                {cards.map((card, index) => (
                    <PlayingCard key={`${card.label}-${index}`} card={card} />
                ))}

                {Array.from({ length: hiddenCount }).map((_, index) => (
                    <PlayingCard key={`hidden-${index}`} hidden />
                ))}
            </div>
        </section>
    );
}
