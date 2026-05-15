import React from 'react';
import CardRow from './CardRow';

function visibleCommunityCards(state) {
    const amountByStreet = {
        pre_flop: 0,
        flop: 3,
        turn: 4,
        river: 5,
        showdown: 5,
    };

    const amount = amountByStreet[state.street] ?? 0;

    return {
        visible: (state.communityCards ?? []).slice(0, amount),
        hiddenCount: Math.max(0, 5 - amount),
    };
}

function opponentCardsTitle(state) {
    const opponent = state?.playersContext?.opponents?.[0];

    if (opponent?.nickname) {
        return `Cartas de ${opponent.nickname}`;
    }

    return 'Cartas do adversário';
}

function currentPlayerTitle(state) {
    const currentName = state?.playersContext?.current?.nickname;

    return currentName ? `Suas cartas (${currentName})` : 'Suas cartas';
}

function currentTurnLabel(state) {
    return state?.currentTurn?.actorLabel ?? 'Jogador';
}

function chipAmountParts(value) {
    const amount = Number(value ?? 0);

    if (amount <= 0) {
        return [1, 1, 1];
    }

    return [
        Math.max(1, Math.ceil(amount / 120)),
        Math.max(1, Math.ceil(amount / 220)),
        Math.max(1, Math.ceil(amount / 360)),
    ].slice(0, 3);
}

function currentTurnMessage(state) {
    if (state?.isFinished) {
        return 'Mão encerrada';
    }

    return state?.currentTurn?.message ?? 'Aguardando ação da mesa.';
}

function winnerPlayer(state) {
    return state?.conclusion?.winner?.player ?? null;
}

function isWinnerSeat(state, seat) {
    const winner = winnerPlayer(state);

    return state?.isFinished && (winner === seat || winner === 'tie');
}

function isLosingSeat(state, seat) {
    const winner = winnerPlayer(state);

    return state?.isFinished && winner && winner !== 'tie' && winner !== seat;
}

function winnerBadgeLabel(state, seat) {
    const winner = winnerPlayer(state);

    if (!state?.isFinished || !winner) {
        return null;
    }

    if (winner === 'tie') {
        return 'Empate';
    }

    return winner === seat ? 'Vencedor' : 'Derrotado';
}

function seatFrameClasses(state, seat) {
    if (isWinnerSeat(state, seat)) {
        return 'poker-winner-seat border-amber-200/70 bg-amber-300/15 shadow-[0_0_42px_rgba(251,191,36,0.28)]';
    }

    if (isLosingSeat(state, seat)) {
        return 'border-slate-500/20 bg-black/25 opacity-70 grayscale-[0.25]';
    }

    return 'border-white/10 bg-black/20 shadow-inner shadow-black/40';
}

function seatBadgeClasses(state, seat) {
    if (isWinnerSeat(state, seat)) {
        return 'border-amber-100/60 bg-amber-300 text-amber-950 shadow-lg shadow-amber-950/25';
    }

    return 'border-slate-400/30 bg-slate-950/80 text-slate-200';
}

export default function PokerTable({ state }) {
    const community = visibleCommunityCards(state);

    return (
        <section className="relative overflow-hidden rounded-[1.75rem] border border-amber-200/20 bg-[radial-gradient(circle_at_center,#166534_0%,#065f46_38%,#052e2b_68%,#020617_100%)] p-3 shadow-[0_30px_90px_rgba(0,0,0,0.55)] sm:rounded-[2.5rem] sm:p-6">
            <div className="pointer-events-none absolute inset-2 rounded-[1.35rem] border-[6px] border-amber-950/45 shadow-inner shadow-black/80 sm:inset-4 sm:rounded-[2rem] sm:border-[10px]" />
            <div className="pointer-events-none absolute inset-5 rounded-[1.25rem] border border-amber-200/20 sm:inset-8 sm:rounded-[1.75rem]" />
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,191,36,0.14),transparent_34%),linear-gradient(120deg,rgba(255,255,255,0.10),transparent_25%,transparent_75%,rgba(255,255,255,0.06))]" />

            <div className="relative z-10 grid min-h-[auto] gap-3 sm:gap-5 lg:min-h-[620px] lg:grid-rows-[auto_1fr_auto]">
                <div className="grid gap-3 sm:gap-4 lg:grid-cols-[1fr_auto] lg:items-start">
                    <div className={[
                        'relative overflow-hidden rounded-3xl border p-4 transition duration-300',
                        seatFrameClasses(state, 'opponent'),
                    ].join(' ')}>
                        {winnerBadgeLabel(state, 'opponent') && (
                            <span className={[
                                'absolute right-4 top-4 rounded-full border px-3 py-1 text-[0.65rem] font-black uppercase tracking-[0.22em]',
                                seatBadgeClasses(state, 'opponent'),
                            ].join(' ')}>
                                {winnerBadgeLabel(state, 'opponent')}
                            </span>
                        )}

                        {state.isFinished && state.opponentCards ? (
                            <CardRow title={opponentCardsTitle(state)} cards={state.opponentCards ?? []} align="left" />
                        ) : (
                            <div className="text-center">
                                <p className="text-xs font-black uppercase tracking-[0.32em] text-slate-300">Adversário</p>
                                <p className="mt-2 text-sm font-semibold text-slate-400">Cartas protegidas até o showdown</p>
                            </div>
                        )}

                        {state.isFinished && state.opponentBestHand?.name && (
                            <p className="mt-3 rounded-2xl border border-white/10 bg-white/10 px-3 py-2 text-center text-xs font-bold text-slate-100">
                                Melhor mão: {state.opponentBestHand.name}
                            </p>
                        )}
                    </div>

                    <div className="poker-turn-glow rounded-3xl border border-amber-200/30 bg-black/35 p-3 text-center shadow-2xl shadow-black/40 sm:p-4">
                        <p className="text-xs font-black uppercase tracking-[0.28em] text-amber-200">Turno atual</p>
                        <strong className="mt-2 block text-xl font-black text-white">{currentTurnLabel(state)}</strong>
                        <span className="mt-1 block text-xs font-semibold text-emerald-100/80">{currentTurnMessage(state)}</span>
                    </div>
                </div>

                <div className="flex items-center justify-center">
                    <div className="w-full min-w-0 max-w-3xl rounded-[1.5rem] border border-amber-200/25 bg-black/25 p-3 shadow-2xl shadow-black/50 backdrop-blur sm:rounded-[2rem] sm:p-5">
                        <div className="poker-pot-pulse relative mx-auto mb-4 w-fit rounded-full border border-amber-200/40 bg-amber-300/15 px-5 py-2.5 text-center shadow-xl shadow-amber-950/20 sm:mb-5 sm:px-6 sm:py-3">
                            <div className="pointer-events-none absolute -top-5 left-1/2 flex -translate-x-1/2 items-end gap-1">
                                {chipAmountParts(state.pot).map((height, index) => (
                                    <span
                                        key={`pot-chip-${index}`}
                                        style={{ animationDelay: `${index * 180}ms` }}
                                        className="poker-chip-float block h-7 w-7 rounded-full border-4 border-amber-100/80 bg-gradient-to-br from-red-500 via-red-700 to-red-950 shadow-lg shadow-black/35"
                                    >
                                        <span className="mx-auto mt-1 block h-2 w-2 rounded-full bg-amber-100/80" />
                                    </span>
                                ))}
                            </div>
                            <p className="text-xs font-black uppercase tracking-[0.32em] text-amber-100">Pote total</p>
                            <strong className="block text-3xl font-black text-white sm:text-5xl">{state.pot}</strong>
                        </div>

                        <CardRow
                            title="Board / Cartas comunitárias"
                            cards={community.visible}
                            hiddenCount={community.hiddenCount}
                            tone="hero"
                        />
                    </div>
                </div>

                <div className="grid gap-3 sm:gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                    <CardRow title={currentPlayerTitle(state)} cards={state.playerCards ?? []} />

                    <div className={[
                        'relative overflow-hidden rounded-3xl border p-4 text-center transition duration-300',
                        seatFrameClasses(state, 'player'),
                    ].join(' ')}>
                        {winnerBadgeLabel(state, 'player') && (
                            <span className={[
                                'mx-auto mb-3 inline-flex rounded-full border px-3 py-1 text-[0.65rem] font-black uppercase tracking-[0.22em]',
                                seatBadgeClasses(state, 'player'),
                            ].join(' ')}>
                                {winnerBadgeLabel(state, 'player')}
                            </span>
                        )}

                        <p className="text-xs font-black uppercase tracking-[0.28em] text-emerald-100">Melhor mão</p>
                        <strong className="mt-2 block text-lg font-black text-white">{state.bestHand?.name ?? 'Aguardando'}</strong>
                        <p className="mt-2 text-xs text-slate-300">Stack: {state.playerStack}</p>
                    </div>
                </div>
            </div>
        </section>
    );
}
