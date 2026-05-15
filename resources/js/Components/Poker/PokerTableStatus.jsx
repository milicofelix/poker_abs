import React from 'react';

function chipLabel(value) {
    return Number(value ?? 0).toLocaleString('pt-BR');
}

function metric(label, value, highlight = false) {
    return (
        <div className={[
            'rounded-2xl border px-3 py-2 shadow-inner shadow-black/30',
            highlight
                ? 'border-amber-200/35 bg-amber-300/15 text-amber-50'
                : 'border-white/10 bg-white/[0.055] text-white',
        ].join(' ')}>
            <span className="block text-[0.58rem] font-black uppercase tracking-[0.18em] text-slate-300">
                {label}
            </span>
            <strong className="mt-1 block truncate text-lg font-black leading-none">
                {value}
            </strong>
        </div>
    );
}

export default function PokerTableStatus({ state, compact = false }) {
    const actorLabel = state.currentTurn?.actorLabel ?? 'Jogador';
    const turnMessage = state.isFinished
        ? 'Mão finalizada.'
        : (state.currentTurn?.message ?? 'Aguardando ação.');

    if (compact) {
        return (
            <section className="rounded-[1.5rem] border border-amber-200/20 bg-slate-950/82 p-3 shadow-2xl shadow-black/45 backdrop-blur">
                <div className="rounded-2xl border border-emerald-300/20 bg-emerald-400/10 p-3">
                    <p className="text-[0.65rem] font-black uppercase tracking-[0.28em] text-amber-200">
                        Turno atual
                    </p>
                    <div className="mt-2 flex items-start justify-between gap-3">
                        <div className="min-w-0">
                            <strong className="block truncate text-2xl font-black text-white">
                                {actorLabel}
                            </strong>
                            <span className="mt-1 block text-sm font-semibold text-emerald-50/85">
                                {turnMessage}
                            </span>
                        </div>

                        <span className="shrink-0 rounded-full border border-emerald-200/30 bg-emerald-300/10 px-3 py-1 text-xs font-black uppercase tracking-[0.16em] text-emerald-100">
                            {state.streetLabel ?? 'Mesa'}
                        </span>
                    </div>
                </div>

                <div className="mt-3 grid grid-cols-2 gap-2">
                    {metric('Pote', chipLabel(state.pot), true)}
                    {metric('Pagar', chipLabel(state.amountToCall ?? state.currentBet))}
                    {metric('Stack', chipLabel(state.playerStack))}
                    {metric('Mão', state.bestHand?.name ?? 'Aguardando')}
                </div>
            </section>
        );
    }

    return (
        <section className="rounded-[1.5rem] border border-emerald-200/20 bg-slate-950/70 p-3 shadow-2xl shadow-black/40 backdrop-blur">
            <div className="grid grid-cols-2 gap-2 md:grid-cols-4 xl:grid-cols-6">
                {metric('Street', state.streetLabel)}
                {metric('Pote', chipLabel(state.pot), true)}
                {metric('Stack', chipLabel(state.playerStack))}
                {metric('Oponente', chipLabel(state.opponentStack))}
                {metric('Pagar', chipLabel(state.amountToCall ?? state.currentBet))}
                {metric('Mão', state.bestHand?.name ?? 'Aguardando')}
            </div>
        </section>
    );
}
