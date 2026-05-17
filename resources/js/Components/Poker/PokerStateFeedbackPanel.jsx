import React from 'react';

function toneClasses(tone) {
    const tones = {
        success: 'border-emerald-200/30 bg-emerald-300/10 text-emerald-50',
        warning: 'border-amber-200/35 bg-amber-300/10 text-amber-50',
        danger: 'border-rose-200/30 bg-rose-300/10 text-rose-50',
        neutral: 'border-slate-200/15 bg-slate-900/70 text-slate-50',
    };

    return tones[tone] ?? tones.neutral;
}

function resolveWinnerLabel(state) {
    const winner = state?.conclusion?.winner;

    if (!winner) {
        return null;
    }

    if (winner.player === 'tie') {
        return 'Empate na mão';
    }

    return winner.label ? `Vencedor: ${winner.label}` : 'Vencedor definido';
}

function resolveStatusCards(state) {
    const currentTurn = state?.currentTurn?.actorLabel ?? 'Jogador';
    const currentMessage = state?.currentTurn?.message ?? 'Aguardando a próxima ação válida.';

    if (state?.isFinished) {
        return [
            {
                key: 'showdown',
                title: 'Showdown finalizado',
                value: resolveWinnerLabel(state) ?? 'Resultado disponível',
                description: state?.conclusion?.message ?? 'Confira as cartas e inicie uma nova mão quando estiver pronto.',
                tone: 'success',
            },
            {
                key: 'street',
                title: 'Última street',
                value: state?.streetLabel ?? 'Showdown',
                description: 'A mão está encerrada e as cartas podem ser conferidas com segurança.',
                tone: 'warning',
            },
        ];
    }

    if (state?.isWaitingForPlayers) {
        return [
            {
                key: 'waiting',
                title: 'Aguardando jogadores',
                value: state?.waitingForPlayers?.message ?? 'Mesa incompleta',
                description: 'Sente em um assento, adicione um bot ou aguarde outro jogador entrar.',
                tone: 'neutral',
            },
        ];
    }

    return [
        {
            key: 'turn',
            title: state?.canAct ? 'Sua vez' : 'Turno atual',
            value: currentTurn,
            description: state?.canAct ? 'Os botões de ação estão liberados para sua jogada.' : currentMessage,
            tone: state?.canAct ? 'success' : 'neutral',
        },
        {
            key: 'street',
            title: 'Rodada',
            value: state?.streetLabel ?? 'Pré-flop',
            description: `Pote atual: ${state?.pot ?? 0}`,
            tone: 'warning',
        },
    ];
}

export default function PokerStateFeedbackPanel({ state, feedback }) {
    if (!feedback) {
        return null;
    }

    const cards = resolveStatusCards(state);

    return (
        <section className="rounded-[1.35rem] border border-white/10 bg-slate-950/70 p-3 shadow-xl shadow-black/35 backdrop-blur sm:p-4">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p className="text-[0.62rem] font-black uppercase tracking-[0.24em] text-amber-200">FASE {feedback.phase} · Estados da mesa</p>
                    <h2 className="mt-1 text-lg font-black text-white">Turno, showdown e vencedor mais claros</h2>
                    <p className="mt-1 text-sm text-slate-300">{feedback.summary}</p>
                </div>
                <span className="w-fit rounded-full border border-emerald-200/25 bg-emerald-300/10 px-3 py-1 text-[0.62rem] font-black uppercase tracking-[0.18em] text-emerald-100">
                    UI segura
                </span>
            </div>

            <div className="mt-3 grid gap-2 md:grid-cols-2">
                {cards.map((card) => (
                    <article key={card.key} className={`rounded-2xl border p-3 ${toneClasses(card.tone)}`}>
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.22em] opacity-80">{card.title}</p>
                        <strong className="mt-1 block text-base font-black sm:text-lg">{card.value}</strong>
                        <p className="mt-1 text-xs font-semibold opacity-80">{card.description}</p>
                    </article>
                ))}
            </div>
        </section>
    );
}
