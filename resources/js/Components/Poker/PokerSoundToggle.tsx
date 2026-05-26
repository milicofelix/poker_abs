type PokerSoundToggleProps = {
    enabled: boolean;
    onToggle: () => void;
};

export default function PokerSoundToggle({ enabled, onToggle }: PokerSoundToggleProps) {
    return (
        <button
            type="button"
            onClick={onToggle}
            aria-pressed={enabled}
            className={[
                'group inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-black uppercase tracking-[0.14em] shadow-lg transition',
                enabled
                    ? 'border-amber-200/50 bg-amber-300 text-amber-950 shadow-amber-950/20 hover:bg-amber-200'
                    : 'border-white/15 bg-white/10 text-white hover:bg-white/20',
            ].join(' ')}
        >
            <span
                aria-hidden="true"
                className={[
                    'flex h-7 w-7 items-center justify-center rounded-full border text-base transition',
                    enabled
                        ? 'border-amber-950/20 bg-amber-100/80'
                        : 'border-white/15 bg-slate-950/60 group-hover:bg-slate-900',
                ].join(' ')}
            >
                {enabled ? '🔊' : '🔇'}
            </span>
            <span className="hidden sm:inline">Sons</span>
            <span>{enabled ? 'On' : 'Off'}</span>
        </button>
    );
}
