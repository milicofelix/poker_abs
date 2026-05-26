import React from 'react';
import PlayingCard from './PlayingCard';

export default function CommunityCards({ cards }) {
    return (
        <section className="rounded-3xl border border-emerald-500/30 bg-emerald-950/50 p-6 shadow-xl">
            <h2 className="mb-4 text-center text-sm font-semibold uppercase tracking-[0.3em] text-emerald-100">
                Cartas Comunitárias
            </h2>

            <div className="flex flex-wrap justify-center gap-4">
                {cards.map((card) => (
                    <PlayingCard key={`${card.rank}-${card.suit}`} card={card} />
                ))}
            </div>
        </section>
    );
}
