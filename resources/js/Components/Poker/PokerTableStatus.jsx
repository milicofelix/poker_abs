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

function metric(label, value, highlight = false) {
    return (
        <div className={`rounded-2xl border p-4 shadow-lg ${highlight ? 'border-amber-200/40 bg-amber-300/15' : 'border-white/10 bg-white/[0.06]'}`}>
            <span className="text-xs font-bold uppercase tracking-[0.2em] text-emerald-100/70">{label}</span>
            <strong className="mt-1 block text-2xl font-black text-white">{value}</strong>
        </div>
    );
}

export default function PokerTableStatus({ state }) {
    const seats = Array.isArray(state.tableSeats) ? state.tableSeats : [];

    return (
        <section className="rounded-[2rem] border border-emerald-200/20 bg-slate-950/65 p-5 shadow-2xl shadow-black/40 backdrop-blur">
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
                {metric('Street', state.streetLabel)}
                {metric('Pote', state.pot, true)}
                {metric('Seu stack', state.playerStack)}
                {metric('Stack oponente', state.opponentStack)}
                {metric('Para pagar', state.amountToCall ?? state.currentBet)}
                {metric('Melhor mão', state.bestHand?.name ?? 'Aguardando')}

                <div className="rounded-2xl border border-amber-200/35 bg-black/25 p-4 shadow-lg lg:col-span-1">
                    <span className="text-xs font-bold uppercase tracking-[0.2em] text-amber-100/80">Vez atual</span>
                    <strong className="mt-1 block text-xl font-black text-white">{state.currentTurn?.actorLabel ?? 'Jogador'}</strong>
                    <span className="mt-1 block text-xs font-semibold text-emerald-100/80">{state.currentTurn?.message ?? 'Aguardando ação.'}</span>
                </div>
            </div>

            {seats.length > 0 && (
                <div className="mt-5">
                    <span className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200/80">Estrutura da mesa</span>
                    <div className="mt-3 grid gap-3 md:grid-cols-2">
                        {seats.map((seat) => (
                            <div
                                key={seat.id ?? seat.seatNumber}
                                className="rounded-2xl border border-white/10 bg-white/[0.05] px-4 py-3 shadow-inner shadow-black/30"
                            >
                                <strong className="block text-sm text-white">
                                    Assento {seat.seatNumber} — {seat.status === 'occupied' ? 'Ocupado' : 'Livre'}
                                </strong>
                                <span className="text-xs text-emerald-100/80">
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
