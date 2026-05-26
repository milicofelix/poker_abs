import PlayingCard from './PlayingCard';
import type { PokerBestHandPayload, PokerCard } from '@/features/poker/types/poker';

type PlayerSeatProps = {
    cards: PokerCard[];
    bestHand: PokerBestHandPayload;
};

export default function PlayerSeat({ cards, bestHand }: PlayerSeatProps) {
    return (
        <section className="rounded-3xl border border-amber-300/40 bg-slate-950/70 p-6 shadow-xl">
            <div className="mb-4 text-center">
                <h2 className="text-lg font-bold text-white">Sua mão</h2>
                <p className="text-sm text-amber-200">Melhor combinação: {bestHand.name}</p>
            </div>

            <div className="flex justify-center gap-4">
                {cards.map((card) => (
                    <PlayingCard key={`${card.rank}-${card.suit}`} card={card} />
                ))}
            </div>
        </section>
    );
}
