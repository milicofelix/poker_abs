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
        'group relative min-h-[44px] overflow-hidden rounded-xl border px-2 py-2 text-center shadow-lg transition duration-200 sm:min-h-[52px] sm:px-3 sm:py-2.5 sm:text-left',
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
            <span className="relative flex items-center justify-center gap-1 sm:items-start sm:justify-between sm:gap-3">
                <span>
                    <span className="block text-center text-[0.58rem] font-black uppercase leading-tight tracking-[0.06em] sm:text-left sm:text-sm sm:tracking-[0.12em]">{title}</span>
                    <span className="mt-0.5 hidden text-[0.68rem] font-bold opacity-75 sm:block">{description}</span>
                </span>

                {shortcut && (
                    <span className="hidden rounded-lg border border-current/20 bg-black/10 px-2 py-1 text-[0.6rem] sm:inline-flex font-black uppercase tracking-[0.16em] opacity-80">
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
        <section className="poker-mobile-action-panel sticky bottom-0 z-30 overflow-hidden rounded-t-[1.5rem] border border-amber-200/20 bg-slate-950/96 shadow-2xl shadow-black/60 backdrop-blur transition-all duration-300 sm:rounded-[1.5rem] lg:static">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(251,191,36,0.14),transparent_32%),linear-gradient(135deg,rgba(255,255,255,0.08),transparent_35%,rgba(16,185,129,0.08))]" />

            <div className="relative p-1.5 sm:p-3">
                <div className="mb-1 grid grid-cols-[minmax(0,1fr)_auto] items-center gap-1.5 sm:mb-2 lg:flex lg:items-end lg:justify-between">
                    <div className="min-w-0">
                        <p className="text-[0.56rem] font-black uppercase tracking-[0.14em] text-amber-200 sm:text-[0.65rem]">Ações</p>
                        <h2 className="hidden text-base font-black text-white sm:mt-0.5 sm:block sm:text-lg">Escolha sua jogada</h2>
                        <p className="truncate text-[0.6rem] font-semibold text-slate-400 sm:mt-0.5 sm:text-xs">
                            {panelLocked
                                ? (actingAction ? `Executando ${actingAction}...` : 'Aguarde sua vez.')
                                : 'Sua vez: escolha uma ação.'}
                        </p>
                    </div>

                    <div className="grid grid-cols-3 gap-1 text-[0.5rem] font-bold text-slate-300 sm:gap-1.5 sm:text-[0.68rem]">
                        <span className="rounded-md border border-white/10 bg-white/5 px-1 py-0.5 sm:rounded-xl sm:px-2 sm:py-1.5">
                            Aposta
                            <strong className="block text-[0.68rem] text-white sm:text-sm">{chipLabel(currentBet)}</strong>
                        </span>
                        <span className="rounded-md border border-white/10 bg-white/5 px-1 py-0.5 sm:rounded-xl sm:px-2 sm:py-1.5">
                            Pagar
                            <strong className="block text-[0.68rem] text-emerald-200 sm:text-sm">{chipLabel(amountToCall)}</strong>
                        </span>
                        <span className="rounded-md border border-white/10 bg-white/5 px-1 py-0.5 sm:rounded-xl sm:px-2 sm:py-1.5">
                            Raise
                            <strong className="block text-[0.68rem] text-amber-200 sm:text-sm">{chipLabel(minRaiseTo)}</strong>
                        </span>
                    </div>
                </div>

                {errorMessage && (
                    <div className="mb-2 rounded-xl border border-rose-300/25 bg-rose-500/10 px-3 py-2 text-xs font-bold text-rose-100">
                        {errorMessage}
                    </div>
                )}

                <div className="grid grid-cols-4 gap-1 sm:gap-2 lg:grid-cols-1">
                    <ActionButton
                        title="Fold"
                        description="Abandonar mão"
                        shortcut="exit"
                        disabled={panelLocked}
                        active={false}
                        tone="danger"
                        onClick={() => onAction('fold')}
                    />
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
                </div>

                <div className="mt-1 rounded-xl border border-white/10 bg-black/20 p-1 shadow-inner shadow-black/40 sm:mt-2 sm:p-2.5">
                    <div className="flex items-center gap-1.5 sm:gap-2">
                        <label className="block flex-1 text-[0.56rem] font-semibold text-slate-300 sm:text-[0.7rem]">
                            Controle do raise
                            <input
                                type="range"
                                min={Math.max(minRaiseTo, 10)}
                                max={maxRaiseTo || Math.max(minRaiseTo, normalizedRaiseAmount)}
                                step={Math.max(Number(minimumRaise ?? 10), 1)}
                                value={normalizedRaiseAmount}
                                disabled={panelLocked || !canRaise}
                                onChange={(event) => handleRaiseChange(event.target.value)}
                                className="mt-0.5 h-4 w-full accent-amber-300 disabled:opacity-40 sm:mt-1.5 sm:h-auto"
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
                            className="w-16 rounded-lg border border-white/10 bg-slate-900 px-1.5 py-1 text-[0.68rem] font-black text-white outline-none transition focus:border-amber-300 disabled:opacity-40 sm:w-28 sm:rounded-xl sm:px-2 sm:py-2 sm:text-sm"
                        />
                    </div>

                    {quickRaises.length > 0 && (
                        <div className="mt-2 hidden flex-wrap gap-1.5 sm:flex">
                            {quickRaises.map((value, index) => (
                                <button
                                    key={`${value}-${index}`}
                                    type="button"
                                    disabled={panelLocked || !canRaise}
                                    onClick={() => handleRaiseChange(value)}
                                    className="rounded-lg border border-amber-200/20 bg-amber-300/10 px-2 py-1.5 text-[0.65rem] font-black uppercase tracking-[0.14em] text-amber-100 transition hover:bg-amber-300/20 disabled:cursor-not-allowed disabled:opacity-40"
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
