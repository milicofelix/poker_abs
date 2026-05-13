import React from 'react';

export default function PokerTableStatus({ state }) {
    return (
        <section className="grid gap-4 rounded-3xl border border-emerald-300/20 bg-emerald-900/40 p-6 shadow-2xl md:grid-cols-6">
            <div>
                <span className="text-sm text-emerald-200">Street</span>
                <strong className="block text-2xl">{state.streetLabel}</strong>
            </div>

            <div>
                <span className="text-sm text-emerald-200">Pote</span>
                <strong className="block text-2xl">{state.pot}</strong>
            </div>

            <div>
                <span className="text-sm text-emerald-200">Seu stack</span>
                <strong className="block text-2xl">{state.playerStack}</strong>
            </div>

            <div>
                <span className="text-sm text-emerald-200">Stack oponente</span>
                <strong className="block text-2xl">{state.opponentStack}</strong>
            </div>

            <div>
                <span className="text-sm text-emerald-200">Para pagar</span>
                <strong className="block text-2xl">{state.amountToCall ?? state.currentBet}</strong>
            </div>

            <div>
                <span className="text-sm text-emerald-200">Melhor mão</span>
                <strong className="block text-2xl">{state.bestHand.name}</strong>
            </div>
        </section>
    );
}
