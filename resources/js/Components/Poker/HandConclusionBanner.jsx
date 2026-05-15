import React from 'react';

function winnerTone(winner) {
    if (winner?.player === 'player') {
        return {
            shell: 'border-emerald-300/55 bg-emerald-400/15 text-emerald-50 shadow-emerald-950/30',
            ribbon: 'bg-emerald-300 text-emerald-950',
            glow: 'from-emerald-300/35',
            icon: '🏆',
            title: `Você venceu: ${winner.label}`,
            subtitle: 'Pote puxado para o seu lado da mesa.',
        };
    }

    if (winner?.player === 'opponent') {
        return {
            shell: 'border-rose-300/55 bg-rose-400/15 text-rose-50 shadow-rose-950/30',
            ribbon: 'bg-rose-300 text-rose-950',
            glow: 'from-rose-300/35',
            icon: '♠️',
            title: `Vencedor: ${winner.label}`,
            subtitle: 'O adversário levou a melhor nesta mão.',
        };
    }

    return {
        shell: 'border-amber-300/55 bg-amber-400/15 text-amber-50 shadow-amber-950/30',
        ribbon: 'bg-amber-300 text-amber-950',
        glow: 'from-amber-300/35',
        icon: '🤝',
        title: winner?.player === 'tie' ? 'Empate no showdown' : 'Mão finalizada',
        subtitle: winner?.player === 'tie' ? 'O pote foi dividido entre os jogadores.' : 'Resultado definido pela mesa.',
    };
}

export default function HandConclusionBanner({ conclusion }) {
    if (!conclusion || !conclusion.isFinished) {
        return null;
    }

    const winner = conclusion.winner;
    const tone = winnerTone(winner);

    return (
        <section
            className={[
                'poker-winner-pop relative overflow-hidden rounded-[2rem] border p-6 shadow-2xl',
                tone.shell,
            ].join(' ')}
        >
            <div className={`pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,var(--tw-gradient-from),transparent_34%)] ${tone.glow}`} />
            <div className="pointer-events-none absolute -right-10 -top-12 h-36 w-36 rounded-full border border-white/20 bg-white/10 blur-[1px]" />
            <div className="pointer-events-none absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-transparent via-white/45 to-transparent" />

            <div className="relative z-10 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <span className={`inline-flex rounded-full px-4 py-1 text-xs font-black uppercase tracking-[0.28em] shadow-lg ${tone.ribbon}`}>
                        Resultado da mão
                    </span>

                    <h2 className="mt-4 text-3xl font-black md:text-4xl">
                        {winner ? tone.title : conclusion.message}
                    </h2>

                    <p className="mt-2 text-sm font-semibold opacity-85">
                        {tone.subtitle}
                    </p>

                    {winner?.handName && (
                        <div className="mt-4 inline-flex rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-black shadow-inner shadow-black/20">
                            Mão vencedora: {winner.handName}
                        </div>
                    )}

                    {winner && conclusion.message && (
                        <p className="mt-3 max-w-2xl text-sm opacity-80">
                            {conclusion.message}
                        </p>
                    )}
                </div>

                <div className="poker-winner-crown flex h-28 w-28 shrink-0 items-center justify-center rounded-full border border-white/35 bg-white/10 text-6xl shadow-inner shadow-black/30">
                    {tone.icon}
                </div>
            </div>
        </section>
    );
}
