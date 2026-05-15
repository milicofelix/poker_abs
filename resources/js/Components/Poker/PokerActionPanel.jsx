import React, { useEffect, useMemo, useState } from 'react';

function chipLabel(value) {
    return Number(value ?? 0).toLocaleString('pt-BR');
}

function normalizeAmount(value, fallback) {
    const number = Number(value);

    if (Number.isNaN(number)) {
        return fallback;
    }

    return Math.max(0, Math.floor(number));
}

function actionButtonClass(tone, isPrimary = false) {
    const tones = {
        neutral: 'border-slate-200/15 bg-slate-800/90 text-white hover:border-slate-200/35 hover:bg-slate-700',
        call: 'border-emerald-200/35 bg-emerald-400 text-emerald-950 hover:bg-emerald-300',
        raise: 'border-amber-100/50 bg-amber-300 text-amber-950 hover:bg-amber-200',
        danger: 'border-rose-200/30 bg-rose-600 text-white hover:bg-rose-500',
    };

    return [
        'group relative min-h-[74px] overflow-hidden rounded-2xl border px-3 py-3 text-left shadow-xl transition duration-200 sm:min-h-[92px] sm:px-4 sm:py-4',
        'focus:outline-none focus:ring-2 focus:ring-amber-200/80 focus:ring-offset-2 focus:ring-offset-slate-950',
        'disabled:cursor-not-allowed disabled:translate-y-0 disabled:opacity-45',
        isPrimary ? 'scale-[1.01] shadow-amber-950/30 hover:-translate-y-1' : 'hover:-translate-y-0.5',
        tones[tone],
    ].join(' ');
}

function ActionButton({ title, description, shortcut, disabled, active, tone, onClick }) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className={actionButtonClass(tone, active)}
        >
            <span className="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/18 via-transparent to-black/10 opacity-0 transition group-hover:opacity-100" />
            {active && (
                <span className="poker-action-shine pointer-events-none absolute inset-y-0 left-0 w-1/3 bg-white/30 blur-sm" />
            )}
            <span className="relative flex items-start justify-between gap-3">
                <span>
                    <span className="block text-sm font-black uppercase tracking-[0.16em]">{title}</span>
                    <span className="mt-1 block text-xs font-bold opacity-75">{description}</span>
                </span>

                {shortcut && (
                    <span className="rounded-lg border border-current/20 bg-black/10 px-2 py-1 text-[0.65rem] font-black uppercase tracking-[0.16em] opacity-80">
                        {shortcut}
                    </span>
                )}
            </span>
        </button>
    );
}

export default function PokerActionPanel({
    disabled,
    currentBet,
    amountToCall = currentBet,
    minimumRaise = 10,
    minimumRaiseTo = currentBet + minimumRaise,
    maximumRaiseTo = null,
    canCheck = amountToCall === 0,
    canCall = amountToCall > 0,
    canRaise = true,
    actingAction = null,
    errorMessage = null,
    onAction,
}) {
    const minRaiseTo = Math.max(Number(minimumRaiseTo ?? 0), 0);
    const maxRaiseTo = maximumRaiseTo ? Number(maximumRaiseTo) : null;
    const [raiseAmount, setRaiseAmount] = useState(Math.max(60, minRaiseTo));

    useEffect(() => {
        setRaiseAmount((current) => {
            const next = Math.max(normalizeAmount(current, minRaiseTo), minRaiseTo);

            if (maxRaiseTo) {
                return Math.min(next, maxRaiseTo);
            }

            return next;
        });
    }, [minRaiseTo, maxRaiseTo]);

    const quickRaises = useMemo(() => {
        const values = [
            minRaiseTo,
            Math.max(minRaiseTo, Number(currentBet ?? 0) * 2),
            maxRaiseTo,
        ].filter((value) => value !== null && value !== undefined && value > 0);

        return [...new Set(values.map((value) => Math.floor(value)))]
            .filter((value) => value >= minRaiseTo && (!maxRaiseTo || value <= maxRaiseTo))
            .slice(0, 3);
    }, [currentBet, minRaiseTo, maxRaiseTo]);

    const normalizedRaiseAmount = normalizeAmount(raiseAmount, minRaiseTo);
    const raiseIsInvalid = normalizedRaiseAmount < minRaiseTo || (maxRaiseTo && normalizedRaiseAmount > maxRaiseTo);
    const panelLocked = disabled || Boolean(actingAction);
    const canSubmitRaise = !panelLocked && canRaise && !raiseIsInvalid;
    const primaryAction = canCheck ? 'check' : 'call';

    function handleRaiseChange(value) {
        const nextAmount = normalizeAmount(value, minRaiseTo);

        if (maxRaiseTo) {
            setRaiseAmount(Math.min(Math.max(nextAmount, minRaiseTo), maxRaiseTo));
            return;
        }

        setRaiseAmount(Math.max(nextAmount, minRaiseTo));
    }

    return (
        <section className="poker-mobile-action-panel sticky bottom-3 z-20 overflow-hidden rounded-[1.5rem] border border-amber-200/20 bg-slate-950/94 shadow-2xl shadow-black/60 backdrop-blur transition-all duration-300 sm:bottom-4 sm:rounded-[2rem]">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(251,191,36,0.14),transparent_32%),linear-gradient(135deg,rgba(255,255,255,0.08),transparent_35%,rgba(16,185,129,0.08))]" />

            <div className="relative p-3 sm:p-5">
                <div className="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p className="text-xs font-black uppercase tracking-[0.28em] text-amber-200">Painel de ações</p>
                        <h2 className="mt-1 text-xl font-black text-white sm:text-2xl">Escolha sua jogada</h2>
                        <p className="mt-1 text-xs font-semibold text-slate-400 sm:text-sm">
                            {panelLocked
                                ? (actingAction ? `Executando ${actingAction}...` : 'Aguarde sua vez para agir.')
                                : 'Ação liberada: responda ao tamanho da aposta atual.'}
                        </p>
                    </div>

                    <div className="grid grid-cols-3 gap-1.5 text-[0.68rem] font-bold text-slate-300 sm:gap-2 sm:text-xs">
                        <span className="rounded-2xl border border-white/10 bg-white/5 px-2 py-2 sm:px-3">
                            Aposta atual
                            <strong className="block text-base text-white">{chipLabel(currentBet)}</strong>
                        </span>
                        <span className="rounded-2xl border border-white/10 bg-white/5 px-2 py-2 sm:px-3">
                            Para pagar
                            <strong className="block text-base text-emerald-200">{chipLabel(amountToCall)}</strong>
                        </span>
                        <span className="rounded-2xl border border-white/10 bg-white/5 px-2 py-2 sm:px-3">
                            Raise mín.
                            <strong className="block text-base text-amber-200">{chipLabel(minRaiseTo)}</strong>
                        </span>
                    </div>
                </div>

                {errorMessage && (
                    <div className="mb-4 rounded-2xl border border-rose-300/25 bg-rose-500/10 px-4 py-3 text-sm font-bold text-rose-100">
                        {errorMessage}
                    </div>
                )}

                <div className="grid grid-cols-2 gap-2 sm:gap-3 md:grid-cols-4">
                    <ActionButton
                        title="Check"
                        description="Passar sem apostar"
                        shortcut="safe"
                        disabled={panelLocked || !canCheck}
                        active={primaryAction === 'check'}
                        tone="neutral"
                        onClick={() => onAction('check')}
                    />
                    <ActionButton
                        title={amountToCall > 0 ? `Call ${chipLabel(amountToCall)}` : 'Call'}
                        description="Igualar aposta"
                        shortcut="main"
                        disabled={panelLocked || !canCall}
                        active={primaryAction === 'call'}
                        tone="call"
                        onClick={() => onAction('call')}
                    />
                    <ActionButton
                        title={`Raise ${chipLabel(normalizedRaiseAmount)}`}
                        description="Aumentar pressão"
                        shortcut="bet"
                        disabled={!canSubmitRaise}
                        active={false}
                        tone="raise"
                        onClick={() => onAction('raise', normalizedRaiseAmount)}
                    />
                    <ActionButton
                        title="Fold"
                        description="Abandonar mão"
                        shortcut="exit"
                        disabled={panelLocked}
                        active={false}
                        tone="danger"
                        onClick={() => onAction('fold')}
                    />
                </div>

                <div className="mt-3 rounded-[1.25rem] border border-white/10 bg-black/20 p-3 shadow-inner shadow-black/40 sm:mt-4 sm:rounded-[1.5rem] sm:p-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <label className="block flex-1 text-sm font-semibold text-slate-300">
                            Controle do raise
                            <input
                                type="range"
                                min={Math.max(minRaiseTo, 10)}
                                max={maxRaiseTo || Math.max(minRaiseTo, normalizedRaiseAmount)}
                                step={Math.max(Number(minimumRaise ?? 10), 1)}
                                value={normalizedRaiseAmount}
                                disabled={panelLocked || !canRaise}
                                onChange={(event) => handleRaiseChange(event.target.value)}
                                className="mt-3 w-full accent-amber-300 disabled:opacity-40"
                            />
                        </label>

                        <input
                            type="number"
                            min={Math.max(minRaiseTo, 10)}
                            max={maxRaiseTo || undefined}
                            step={Math.max(Number(minimumRaise ?? 10), 1)}
                            value={normalizedRaiseAmount}
                            disabled={panelLocked || !canRaise}
                            onChange={(event) => handleRaiseChange(event.target.value)}
                            className="w-full rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-lg font-black text-white outline-none transition focus:border-amber-300 disabled:opacity-40 lg:w-36"
                        />
                    </div>

                    {quickRaises.length > 0 && (
                        <div className="mt-3 flex flex-wrap gap-2">
                            {quickRaises.map((value, index) => (
                                <button
                                    key={`${value}-${index}`}
                                    type="button"
                                    disabled={panelLocked || !canRaise}
                                    onClick={() => handleRaiseChange(value)}
                                    className="rounded-xl border border-amber-200/20 bg-amber-300/10 px-3 py-2 text-xs font-black uppercase tracking-[0.14em] text-amber-100 transition hover:bg-amber-300/20 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    {index === quickRaises.length - 1 && maxRaiseTo === value ? 'All-in' : `Raise ${chipLabel(value)}`}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
