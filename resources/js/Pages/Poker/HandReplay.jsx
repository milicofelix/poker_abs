import React, { useMemo, useState } from 'react';

function formatMoney(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

function actionBadgeClass(action) {
    if (action === 'Fold') {
        return 'border-red-400/40 bg-red-400/10 text-red-100';
    }

    if (action === 'Raise') {
        return 'border-orange-400/40 bg-orange-400/10 text-orange-100';
    }

    if (action === 'Call') {
        return 'border-sky-400/40 bg-sky-400/10 text-sky-100';
    }

    if (action === 'Check') {
        return 'border-emerald-400/40 bg-emerald-400/10 text-emerald-100';
    }

    return 'border-slate-400/40 bg-slate-400/10 text-slate-100';
}

export default function HandReplay({ replay }) {
    const steps = replay.steps || [];
    const [currentIndex, setCurrentIndex] = useState(0);

    const currentStep = steps[currentIndex] || null;
    const previousSteps = useMemo(
        () => steps.slice(0, currentIndex + 1),
        [steps, currentIndex],
    );

    function previous() {
        setCurrentIndex((index) => Math.max(0, index - 1));
    }

    function next() {
        setCurrentIndex((index) => Math.min(steps.length - 1, index + 1));
    }

    function restart() {
        setCurrentIndex(0);
    }

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <header className="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-200">
                            Poker ABS
                        </p>
                        <h1 className="mt-2 text-3xl font-black">Replay da mão</h1>
                        <p className="mt-2 max-w-2xl break-all text-sm text-slate-300">
                            Código: {replay.hand.code}
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <a
                            href={`/poker/hands/${replay.hand.id}`}
                            className="inline-flex w-fit rounded-xl border border-white/10 bg-white/10 px-5 py-3 font-bold text-white transition hover:bg-white/20"
                        >
                            Detalhe da mão
                        </a>
                        <a
                            href="/poker/hands"
                            className="inline-flex w-fit rounded-xl bg-white px-5 py-3 font-bold text-slate-950 transition hover:bg-emerald-100"
                        >
                            Histórico
                        </a>
                    </div>
                </header>

                <section className="grid gap-4 md:grid-cols-4">
                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Mesa</p>
                        <p className="mt-3 text-2xl font-black">{replay.hand.table}</p>
                    </div>
                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Status</p>
                        <p className="mt-3 text-2xl font-black">{replay.hand.statusLabel}</p>
                    </div>
                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Ações</p>
                        <p className="mt-3 text-2xl font-black">{replay.summary.totalActions}</p>
                    </div>
                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Pote final</p>
                        <p className="mt-3 text-2xl font-black">{formatMoney(replay.summary.finalPot)}</p>
                    </div>
                </section>

                {replay.summary.winnerLabel && (
                    <section className="rounded-3xl border border-amber-300/30 bg-amber-300/10 p-6 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-amber-100">Resultado final</p>
                        <h2 className="mt-2 text-2xl font-black text-amber-50">
                            Vencedor: {replay.summary.winnerLabel}
                        </h2>
                        {replay.summary.winningHandName && (
                            <p className="mt-1 text-sm font-semibold text-amber-100">
                                Mão vencedora: {replay.summary.winningHandName}
                            </p>
                        )}
                    </section>
                )}

                {steps.length === 0 ? (
                    <section className="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                        <h2 className="text-2xl font-black">Nenhuma ação para reproduzir</h2>
                        <p className="mt-2 text-sm text-slate-300">
                            Esta mão ainda não possui ações registradas no banco.
                        </p>
                    </section>
                ) : (
                    <section className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                        <article className="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                            <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p className="text-xs uppercase tracking-[0.2em] text-slate-400">
                                        Passo {currentStep.number} de {steps.length}
                                    </p>
                                    <h2 className="mt-1 text-2xl font-black">{currentStep.streetLabel}</h2>
                                </div>
                                <span className={`w-fit rounded-full border px-4 py-2 text-sm font-bold ${actionBadgeClass(currentStep.action)}`}>
                                    {currentStep.action}
                                </span>
                            </div>

                            <div className="mt-6 rounded-3xl border border-white/10 bg-slate-950/40 p-6">
                                <p className="text-sm text-slate-400">Jogador da ação</p>
                                <h3 className="mt-1 text-4xl font-black">{currentStep.player}</h3>
                                {currentStep.message && (
                                    <p className="mt-3 text-base text-slate-200">{currentStep.message}</p>
                                )}

                                <div className="mt-6 grid gap-4 md:grid-cols-3">
                                    <div className="rounded-2xl bg-white/10 p-4">
                                        <p className="text-xs uppercase tracking-[0.18em] text-slate-400">Valor</p>
                                        <p className="mt-2 text-2xl font-black">{formatMoney(currentStep.amount)}</p>
                                    </div>
                                    <div className="rounded-2xl bg-white/10 p-4">
                                        <p className="text-xs uppercase tracking-[0.18em] text-slate-400">Pote após ação</p>
                                        <p className="mt-2 text-2xl font-black text-emerald-100">
                                            {formatMoney(currentStep.potAfterAction)}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl bg-white/10 p-4">
                                        <p className="text-xs uppercase tracking-[0.18em] text-slate-400">Horário</p>
                                        <p className="mt-2 text-lg font-black">{currentStep.actedAt || '-'}</p>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-6 flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    onClick={previous}
                                    disabled={currentIndex === 0}
                                    className="rounded-xl border border-white/10 bg-white/10 px-5 py-3 font-bold text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Anterior
                                </button>
                                <button
                                    type="button"
                                    onClick={next}
                                    disabled={currentIndex === steps.length - 1}
                                    className="rounded-xl bg-emerald-300 px-5 py-3 font-bold text-emerald-950 transition hover:bg-emerald-200 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Próxima ação
                                </button>
                                <button
                                    type="button"
                                    onClick={restart}
                                    className="rounded-xl border border-white/10 bg-slate-950/40 px-5 py-3 font-bold text-white transition hover:bg-slate-950/70"
                                >
                                    Reiniciar replay
                                </button>
                            </div>
                        </article>

                        <aside className="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                            <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Linha do tempo reproduzida</p>
                            <h2 className="mt-1 text-2xl font-black">Ações até agora</h2>

                            <div className="mt-5 flex flex-col gap-3">
                                {previousSteps.map((step) => (
                                    <button
                                        type="button"
                                        key={step.id}
                                        onClick={() => setCurrentIndex(step.number - 1)}
                                        className={`rounded-2xl border p-4 text-left transition ${step.number === currentStep.number
                                            ? 'border-emerald-300/50 bg-emerald-300/10'
                                            : 'border-white/10 bg-slate-950/30 hover:bg-slate-950/50'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <p className="text-sm font-black">#{step.number} {step.player}</p>
                                            <span className={`rounded-full border px-3 py-1 text-xs font-bold ${actionBadgeClass(step.action)}`}>
                                                {step.action}
                                            </span>
                                        </div>
                                        <p className="mt-2 text-xs text-slate-400">
                                            {step.streetLabel} • Pote: {formatMoney(step.potAfterAction)}
                                        </p>
                                    </button>
                                ))}
                            </div>
                        </aside>
                    </section>
                )}

                <section className="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                    <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Resumo por street</p>
                    <div className="mt-4 grid gap-3 md:grid-cols-4">
                        {replay.streets.map((street) => (
                            <div key={street.street} className="rounded-2xl bg-slate-950/40 p-4">
                                <p className="text-lg font-black">{street.label}</p>
                                <p className="mt-1 text-sm text-slate-300">
                                    {street.actionsCount} ações • pote {formatMoney(street.potAfterStreet)}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </main>
    );
}
