type PokerBotThinkingIndicatorProps = {
    active?: boolean;
    label?: string;
};

export default function PokerBotThinkingIndicator({ active = false, label = 'Oponente pensando...' }: PokerBotThinkingIndicatorProps) {
    if (!active) {
        return null;
    }

    return (
        <div className="overflow-hidden rounded-[1.25rem] border border-amber-200/30 bg-slate-950/90 px-3 py-2.5 shadow-2xl shadow-black/50 backdrop-blur sm:px-4 sm:py-3">
            <div className="flex items-center gap-3">
                <div className="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-amber-200/30 bg-amber-300/15 shadow-inner shadow-amber-950/30">
                    <span className="poker-bot-thinking-dot absolute h-2.5 w-2.5 rounded-full bg-amber-200" />
                    <span className="text-lg">🤖</span>
                </div>

                <div className="min-w-0 flex-1">
                    <p className="text-[0.62rem] font-black uppercase tracking-[0.22em] text-amber-200">Bot em ação</p>
                    <strong className="mt-0.5 block truncate text-sm font-black text-white sm:text-base">{label}</strong>
                    <p className="mt-0.5 text-xs font-semibold text-slate-300">A jogada será confirmada em alguns segundos para simular uma decisão real.</p>
                </div>

                <div className="hidden items-center gap-1 sm:flex" aria-hidden="true">
                    <span className="poker-bot-thinking-bubble h-2 w-2 rounded-full bg-amber-200" />
                    <span className="poker-bot-thinking-bubble h-2 w-2 rounded-full bg-amber-200 [animation-delay:160ms]" />
                    <span className="poker-bot-thinking-bubble h-2 w-2 rounded-full bg-amber-200 [animation-delay:320ms]" />
                </div>
            </div>
        </div>
    );
}
