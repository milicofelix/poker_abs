import React from 'react';

const handRanks = [
    {
        name: 'Royal flush',
        label: 'Mais forte',
        description: 'Sequência do 10 ao Ás, todas do mesmo naipe.',
        cards: ['10♥', 'J♥', 'Q♥', 'K♥', 'A♥'],
    },
    {
        name: 'Straight flush',
        description: 'Cinco cartas em sequência, todas do mesmo naipe.',
        cards: ['4♣', '5♣', '6♣', '7♣', '8♣'],
    },
    {
        name: 'Quadra',
        description: 'Quatro cartas do mesmo valor.',
        cards: ['A♥', 'A♠', 'A♣', 'A♦', '8♠'],
    },
    {
        name: 'Full house',
        description: 'Uma trinca mais um par.',
        cards: ['Q♠', 'Q♥', 'Q♣', '6♠', '6♦'],
    },
    {
        name: 'Flush',
        description: 'Cinco cartas do mesmo naipe, sem sequência.',
        cards: ['4♦', '8♦', '6♦', '7♦', '2♦'],
    },
    {
        name: 'Sequência',
        description: 'Cinco cartas em ordem, com naipes diferentes.',
        cards: ['3♠', '4♥', '5♦', '6♣', '7♥'],
    },
    {
        name: 'Trinca',
        description: 'Três cartas do mesmo valor.',
        cards: ['K♠', 'K♥', 'K♣', '5♠', '8♦'],
    },
    {
        name: 'Dois pares',
        description: 'Dois pares diferentes.',
        cards: ['3♣', '9♠', '9♥', '5♦', '5♣'],
    },
    {
        name: 'Par',
        description: 'Duas cartas do mesmo valor.',
        cards: ['9♠', '10♥', '6♣', '6♠', '2♦'],
    },
    {
        name: 'Carta alta',
        label: 'Mais fraca',
        description: 'Nenhuma combinação. Vale a carta mais alta.',
        cards: ['A♦', '6♠', 'K♣', '8♥', '3♠'],
    },
];

function getSuitColor(card) {
    return card.includes('♥') || card.includes('♦') ? 'text-red-500' : 'text-slate-950';
}

function CardPreview({ card }) {
    return (
        <span className="flex h-11 w-8 -rotate-3 items-center justify-center rounded-md border border-slate-200 bg-white text-sm font-black shadow-lg shadow-black/20 first:ml-0 sm:h-12 sm:w-9 sm:text-base">
            <span className={getSuitColor(card)}>{card}</span>
        </span>
    );
}

export default function PokerHandRankCheatSheet({ open, onClose }) {
    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-3 backdrop-blur-sm" role="dialog" aria-modal="true">
            <button
                type="button"
                className="absolute inset-0 cursor-default"
                aria-label="Fechar colinha de mãos"
                onClick={onClose}
            />

            <section className="relative flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-[2rem] border border-amber-200/25 bg-[radial-gradient(circle_at_top,rgba(16,185,129,0.22),transparent_34%),linear-gradient(145deg,#020617,#031f18_48%,#020617)] shadow-2xl shadow-black/70">
                <header className="flex items-start justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                    <div>
                        <p className="text-[0.65rem] font-black uppercase tracking-[0.28em] text-amber-200">Colinha para iniciantes</p>
                        <h2 className="mt-1 text-xl font-black text-white sm:text-2xl">Hierarquia das mãos</h2>
                        <p className="mt-1 text-sm text-slate-300">Da combinação mais forte para a mais fraca no Texas Hold’em.</p>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-black text-white transition hover:bg-white/15"
                    >
                        Fechar
                    </button>
                </header>

                <div className="overflow-y-auto px-4 py-4 sm:px-6 sm:py-5">
                    <div className="grid gap-3 md:grid-cols-2">
                        {handRanks.map((rank, index) => (
                            <article
                                key={rank.name}
                                className="rounded-3xl border border-white/10 bg-white/[0.06] p-4 shadow-xl shadow-black/25"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <span className="flex h-7 w-7 items-center justify-center rounded-full bg-amber-300 text-xs font-black text-amber-950">
                                                {index + 1}
                                            </span>
                                            <h3 className="text-base font-black uppercase tracking-[0.08em] text-white">
                                                {rank.name}
                                            </h3>
                                        </div>
                                        <p className="mt-2 text-sm leading-relaxed text-slate-300">{rank.description}</p>
                                    </div>

                                    {rank.label && (
                                        <span className="shrink-0 rounded-full border border-amber-200/30 bg-amber-300/15 px-3 py-1 text-[0.62rem] font-black uppercase tracking-[0.18em] text-amber-100">
                                            {rank.label}
                                        </span>
                                    )}
                                </div>

                                <div className="mt-4 flex min-w-0 items-center justify-center gap-1 rounded-2xl border border-white/10 bg-slate-950/45 px-3 py-4">
                                    {rank.cards.map((card) => (
                                        <CardPreview key={`${rank.name}-${card}`} card={card} />
                                    ))}
                                </div>
                            </article>
                        ))}
                    </div>

                    <div className="mt-4 rounded-2xl border border-emerald-200/20 bg-emerald-300/10 px-4 py-3 text-sm text-emerald-50">
                        Dica: quando dois jogadores têm a mesma combinação, vence quem tiver as cartas mais altas dentro daquela combinação.
                    </div>
                </div>
            </section>
        </div>
    );
}
