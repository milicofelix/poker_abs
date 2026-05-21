import React, { useMemo, useState } from 'react';
import {
    PokerBadge,
    PokerButton,
    PokerEmptyState,
    PokerSectionHeader,
    PokerSurface,
} from '@/Components/Poker/Ui/PokerDesignSystem';

function formatMoney(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

function actionTone(action) {
    if (action === 'Fold') {
        return 'danger';
    }

    if (action === 'Raise') {
        return 'warning';
    }

    if (action === 'Call') {
        return 'info';
    }

    if (action === 'Check') {
        return 'success';
    }

    return 'neutral';
}

function ReplayMetric({ label, value, helper = null, tone = 'soft' }) {
    return (
        <PokerSurface tone={tone} className="p-5">
            <p className="text-[0.62rem] font-black uppercase tracking-[0.22em] text-slate-400">{label}</p>
            <p className="mt-3 text-2xl font-black text-white">{value}</p>
            {helper ? <p className="mt-2 text-xs font-semibold text-slate-400">{helper}</p> : null}
        </PokerSurface>
    );
}

export default function HandReplay({ replay }) {
    const safeReplay = replay || { hand: {}, summary: {}, streets: [], steps: [] };
    const steps = safeReplay.steps || [];
    const [currentIndex, setCurrentIndex] = useState(0);

    const currentStep = steps[currentIndex] || null;
    const previousSteps = useMemo(
        () => steps.slice(0, currentIndex + 1),
        [steps, currentIndex],
    );

    const progress = steps.length > 0 ? Math.round(((currentIndex + 1) / steps.length) * 100) : 0;

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
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-4 text-white md:p-6">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <PokerSurface className="overflow-hidden p-0">
                    <div className="border-b border-white/10 bg-[radial-gradient(circle_at_top_left,rgba(52,211,153,0.18),transparent_34%),rgba(15,23,42,0.68)] p-6 md:p-7">
                        <PokerSectionHeader
                            eyebrow="Poker ABS • FASE 13.1.6"
                            title="Replay da mão"
                            description={`Revise cada ação registrada com timeline, progresso e resumo financeiro. Código: ${safeReplay.hand?.code || '-'}`}
                            action={(
                                <div className="flex flex-wrap gap-2">
                                    <PokerButton as="a" href="/poker/hands" tone="secondary">
                                        Histórico
                                    </PokerButton>
                                    <PokerButton as="a" href="/poker" tone="primary">
                                        Voltar para mesa
                                    </PokerButton>
                                </div>
                            )}
                        />
                    </div>

                    <div className="grid gap-4 p-5 md:grid-cols-4 md:p-6">
                        <ReplayMetric label="Mesa" value={safeReplay.hand?.table || '-'} />
                        <ReplayMetric label="Status" value={safeReplay.hand?.statusLabel || '-'} />
                        <ReplayMetric label="Ações" value={safeReplay.summary?.totalActions || 0} />
                        <ReplayMetric label="Pote final" value={formatMoney(safeReplay.summary?.finalPot)} tone="emerald" />
                    </div>
                </PokerSurface>

                {safeReplay.summary?.winnerLabel ? (
                    <PokerSurface tone="amber" className="p-5 md:p-6">
                        <PokerSectionHeader
                            eyebrow="Resultado final"
                            title={`Vencedor: ${safeReplay.summary.winnerLabel}`}
                            description={safeReplay.summary.winningHandName ? `Mão vencedora: ${safeReplay.summary.winningHandName}` : 'Resultado registrado no encerramento da mão.'}
                        />
                    </PokerSurface>
                ) : null}

                {steps.length === 0 ? (
                    <PokerEmptyState
                        eyebrow="Replay indisponível"
                        title="Nenhuma ação para reproduzir"
                        description="Esta mão ainda não possui ações registradas no banco. Finalize uma mão com ações para habilitar a linha do tempo."
                        action={(
                            <PokerButton as="a" href="/poker/hands" tone="primary">
                                Voltar ao histórico
                            </PokerButton>
                        )}
                    />
                ) : (
                    <section className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                        <PokerSurface as="article" className="p-5 md:p-6">
                            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p className="text-[0.62rem] font-black uppercase tracking-[0.22em] text-slate-400">
                                        Passo {currentStep.number} de {steps.length}
                                    </p>
                                    <h2 className="mt-1 text-2xl font-black text-white">{currentStep.streetLabel}</h2>
                                </div>
                                <PokerBadge tone={actionTone(currentStep.action)}>
                                    {currentStep.action}
                                </PokerBadge>
                            </div>

                            <div className="mt-5 h-2 overflow-hidden rounded-full bg-slate-950/70">
                                <div
                                    className="h-full rounded-full bg-emerald-300 transition-all"
                                    style={{ width: `${progress}%` }}
                                />
                            </div>
                            <p className="mt-2 text-xs font-semibold text-slate-400">{progress}% do replay reproduzido</p>

                            <div className="mt-6 rounded-[1.5rem] border border-white/10 bg-slate-950/45 p-5 md:p-6">
                                <p className="text-sm font-semibold text-slate-400">Jogador da ação</p>
                                <h3 className="mt-1 text-3xl font-black text-white md:text-4xl">{currentStep.player}</h3>
                                {currentStep.message ? (
                                    <p className="mt-3 text-sm font-semibold leading-relaxed text-slate-200 md:text-base">
                                        {currentStep.message}
                                    </p>
                                ) : null}

                                <div className="mt-6 grid gap-4 md:grid-cols-3">
                                    <ReplayMetric label="Valor" value={formatMoney(currentStep.amount)} />
                                    <ReplayMetric label="Pote após ação" value={formatMoney(currentStep.potAfterAction)} tone="emerald" />
                                    <ReplayMetric label="Horário" value={currentStep.actedAt || '-'} />
                                </div>
                            </div>

                            <div className="mt-6 flex flex-wrap gap-3">
                                <PokerButton type="button" onClick={previous} disabled={currentIndex === 0} tone="secondary">
                                    Anterior
                                </PokerButton>
                                <PokerButton type="button" onClick={next} disabled={currentIndex === steps.length - 1} tone="primary">
                                    Próxima ação
                                </PokerButton>
                                <PokerButton type="button" onClick={restart} tone="ghost">
                                    Reiniciar replay
                                </PokerButton>
                            </div>
                        </PokerSurface>

                        <PokerSurface as="aside" className="p-5 md:p-6">
                            <PokerSectionHeader
                                eyebrow="Linha do tempo"
                                title="Ações reproduzidas"
                                description="Toque em uma ação para voltar diretamente ao ponto desejado do replay."
                            />

                            <div className="mt-5 flex max-h-[34rem] flex-col gap-3 overflow-y-auto pr-1">
                                {previousSteps.map((step) => (
                                    <button
                                        type="button"
                                        key={step.id}
                                        onClick={() => setCurrentIndex(step.number - 1)}
                                        className={`rounded-2xl border p-4 text-left transition ${step.number === currentStep.number
                                            ? 'border-emerald-300/50 bg-emerald-300/10 shadow-lg shadow-emerald-950/20'
                                            : 'border-white/10 bg-slate-950/30 hover:bg-slate-950/50'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <p className="text-sm font-black text-white">#{step.number} {step.player}</p>
                                            <PokerBadge tone={actionTone(step.action)}>{step.action}</PokerBadge>
                                        </div>
                                        <p className="mt-2 text-xs font-semibold text-slate-400">
                                            {step.streetLabel} • Pote: {formatMoney(step.potAfterAction)}
                                        </p>
                                    </button>
                                ))}
                            </div>
                        </PokerSurface>
                    </section>
                )}

                <PokerSurface className="p-5 md:p-6">
                    <PokerSectionHeader
                        eyebrow="Resumo por street"
                        title="Evolução da mão"
                        description="Resumo compacto para conferir a quantidade de ações e o pote final registrado em cada street."
                    />
                    <div className="mt-4 grid gap-3 md:grid-cols-4">
                        {(safeReplay.streets || []).map((street) => (
                            <div key={street.street} className="rounded-2xl border border-white/10 bg-slate-950/45 p-4">
                                <p className="text-lg font-black text-white">{street.label}</p>
                                <p className="mt-1 text-sm font-semibold text-slate-300">
                                    {street.actionsCount} ações • pote {formatMoney(street.potAfterStreet)}
                                </p>
                            </div>
                        ))}
                    </div>
                </PokerSurface>
            </div>
        </main>
    );
}
