import React, { useState } from 'react';

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
    onAction,
}) {
    const [raiseAmount, setRaiseAmount] = useState(Math.max(60, minimumRaiseTo));

    return (
        <section className="rounded-2xl border border-white/10 bg-slate-950/70 p-4 shadow-xl">
            <h2 className="mb-3 text-lg font-semibold text-white">Ações da rodada</h2>

            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                <button type="button" disabled={disabled || !canCheck} onClick={() => onAction('check')} className="rounded-xl bg-slate-700 px-4 py-3 font-semibold text-white transition hover:bg-slate-600 disabled:cursor-not-allowed disabled:opacity-40">Check</button>
                <button type="button" disabled={disabled || !canCall} onClick={() => onAction('call')} className="rounded-xl bg-emerald-700 px-4 py-3 font-semibold text-white transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-40">{amountToCall > 0 ? `Call ${amountToCall}` : 'Call'}</button>
                <button type="button" disabled={disabled || !canRaise} onClick={() => onAction('raise', raiseAmount)} className="rounded-xl bg-amber-600 px-4 py-3 font-semibold text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-40">Raise</button>
                <button type="button" disabled={disabled} onClick={() => onAction('fold')} className="rounded-xl bg-rose-700 px-4 py-3 font-semibold text-white transition hover:bg-rose-600 disabled:cursor-not-allowed disabled:opacity-40">Fold</button>
            </div>

            <label className="mt-4 block text-sm text-slate-300">
                Valor do raise mínimo: {minimumRaiseTo}
                <input
                    type="number"
                    min={Math.max(minimumRaiseTo, 10)}
                    max={maximumRaiseTo || undefined}
                    step={minimumRaise}
                    value={raiseAmount}
                    disabled={disabled || !canRaise}
                    onChange={(event) => setRaiseAmount(Number(event.target.value))}
                    className="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white outline-none focus:border-amber-400 disabled:opacity-40"
                />
            </label>
        </section>
    );
}
