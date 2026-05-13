import React from 'react';

function seatRoleLabel(seat) {
    const roles = [];

    if (seat.isDealer) {
        roles.push('Dealer');
    }

    if (seat.isSmallBlind) {
        roles.push('Small blind');
    }

    if (seat.isBigBlind) {
        roles.push('Big blind');
    }

    return roles.length > 0 ? roles.join(' • ') : 'Assento';
}

export default function PokerTableStatus({ state }) {
    const seats = Array.isArray(state.tableSeats) ? state.tableSeats : [];

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

            {seats.length > 0 && (
                <div className="md:col-span-6">
                    <span className="text-sm text-emerald-200">Estrutura da mesa</span>
                    <div className="mt-2 grid gap-2 md:grid-cols-2">
                        {seats.map((seat) => (
                            <div
                                key={seat.id ?? seat.seatNumber}
                                className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3"
                            >
                                <strong className="block text-sm">
                                    Assento {seat.seatNumber} — {seat.status === 'occupied' ? 'Ocupado' : 'Livre'}
                                </strong>
                                <span className="text-xs text-emerald-100">
                                    {seatRoleLabel(seat)} • Stack {seat.stack}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </section>
    );
}
