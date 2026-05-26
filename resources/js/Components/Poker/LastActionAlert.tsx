type BotContext = {
    potPressure?: string | null;
    spr?: number | string | null;
    stackPressure?: string | null;
    opponentAggressionRate?: number | string | null;
    memory?: BotMemory | null;
};

type BotMemory = {
    opponentModel?: string | null;
    opponentModelLabel?: string | null;
    notes?: string[] | null;
};

type BotStats = {
    tendency?: string | null;
    aggressionRate?: number | string | null;
    raiseRate?: number | string | null;
    totalDecisions?: number | string | null;
};

type LastAction = {
    message?: string | null;
    botStats?: BotStats | null;
    botContext?: BotContext | null;
    botMemory?: BotMemory | null;
};

type LastActionAlertProps = {
    action?: LastAction | null;
};

export default function LastActionAlert({ action }: LastActionAlertProps) {
    if (! action) {
        return null;
    }

    const botStats = action.botStats ?? null;
    const botContext = action.botContext ?? null;
    const botMemory = action.botMemory ?? botContext?.memory ?? null;

    return (
        <div className="rounded-2xl border border-amber-300/30 bg-amber-500/10 p-4 text-amber-100">
            <p className="font-bold">{action.message}</p>

            {botContext && (
                <div className="mt-3 grid gap-2 text-xs text-amber-50/90 md:grid-cols-4">
                    <span className="rounded-xl bg-black/20 px-3 py-2 font-bold">
                        Pote: {botContext.potPressure === 'large_pot' ? 'grande' : botContext.potPressure === 'small_pot' ? 'pequeno' : 'normal'}
                    </span>
                    <span className="rounded-xl bg-black/20 px-3 py-2 font-bold">
                        SPR: {botContext.spr}
                    </span>
                    <span className="rounded-xl bg-black/20 px-3 py-2 font-bold">
                        Stack: {botContext.stackPressure === 'short_stack' ? 'curto' : botContext.stackPressure === 'deep_stack' ? 'deep' : 'confortável'}
                    </span>
                    <span className="rounded-xl bg-black/20 px-3 py-2 font-bold">
                        Vilão agressivo: {botContext.opponentAggressionRate ?? 0}%
                    </span>
                </div>
            )}

            {botMemory && botMemory.opponentModel !== 'unknown' && (
                <div className="mt-3 rounded-xl bg-black/20 px-3 py-2 text-xs font-bold text-amber-50/90">
                    Memória do bot: {botMemory.opponentModelLabel}
                    {Array.isArray(botMemory.notes) && botMemory.notes.length > 0 ? ` — ${botMemory.notes[0]}` : ''}
                </div>
            )}

            {botStats && (
                <div className="mt-3 grid gap-2 text-xs text-amber-50/90 md:grid-cols-4">
                    <span className="rounded-xl bg-black/20 px-3 py-2 font-bold">
                        Tendência: {botStats.tendency}
                    </span>
                    <span className="rounded-xl bg-black/20 px-3 py-2 font-bold">
                        Agressão: {botStats.aggressionRate}%
                    </span>
                    <span className="rounded-xl bg-black/20 px-3 py-2 font-bold">
                        Raises: {botStats.raiseRate}%
                    </span>
                    <span className="rounded-xl bg-black/20 px-3 py-2 font-bold">
                        Decisões: {botStats.totalDecisions}
                    </span>
                </div>
            )}
        </div>
    );
}
