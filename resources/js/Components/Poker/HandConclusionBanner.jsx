import React from 'react';

export default function HandConclusionBanner({ conclusion }) {
    if (!conclusion || !conclusion.isFinished) {
        return null;
    }

    const winner = conclusion.winner;
    const isPlayerWin = winner?.player === 'player';
    const isOpponentWin = winner?.player === 'opponent';
    const isTie = winner?.player === 'tie';

    return (
        <section
            className={[
                'rounded-3xl border p-5 shadow-2xl',
                isPlayerWin ? 'border-emerald-300/40 bg-emerald-400/15 text-emerald-50' : '',
                isOpponentWin ? 'border-rose-300/40 bg-rose-400/15 text-rose-50' : '',
                isTie ? 'border-amber-300/40 bg-amber-400/15 text-amber-50' : '',
                !winner ? 'border-amber-300/40 bg-amber-400/15 text-amber-50' : '',
            ].join(' ')}
        >
            <p className="text-sm font-semibold uppercase tracking-[0.25em] opacity-80">
                Mão finalizada
            </p>

            <h2 className="mt-2 text-2xl font-black">
                {winner
                    ? winner.player === 'tie'
                        ? 'Empate no showdown'
                        : `Vencedor: ${winner.label}`
                    : conclusion.message}
            </h2>

            {winner?.handName && (
                <p className="mt-2 text-base font-semibold opacity-90">
                    Mão vencedora: {winner.handName}
                </p>
            )}

            {winner && (
                <p className="mt-3 text-sm opacity-80">
                    {conclusion.message}
                </p>
            )}
        </section>
    );
}
