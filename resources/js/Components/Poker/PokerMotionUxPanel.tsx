type MotionChecklistItem = {
    area: string;
    label: string;
    status: string;
    note: string;
};

type PokerMotionUxPanelProps = {
    motion?: {
        phase?: string | number | null;
        title?: string | null;
        summary?: string | null;
        checklist?: MotionChecklistItem[] | null;
    } | null;
};

export default function PokerMotionUxPanel({ motion = null }: PokerMotionUxPanelProps) {
    const items = motion?.checklist ?? [];

    if (!motion || items.length === 0) {
        return null;
    }

    return (
        <section className="poker-soft-enter rounded-[1.5rem] border border-violet-200/20 bg-violet-300/10 p-3 text-violet-50 shadow-xl shadow-black/35 backdrop-blur sm:p-4">
            <div className="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                <div>
                    <p className="text-[0.62rem] font-black uppercase tracking-[0.24em] text-violet-200">FASE {motion.phase}</p>
                    <h2 className="mt-1 text-base font-black text-white sm:text-lg">{motion.title ?? 'Animações leves'}</h2>
                    <p className="mt-1 text-xs font-semibold text-violet-50/80 sm:text-sm">{motion.summary}</p>
                </div>

                <span className="w-fit rounded-full border border-violet-100/25 bg-black/25 px-3 py-1 text-[0.58rem] font-black uppercase tracking-[0.2em] text-violet-100">
                    Somente visual
                </span>
            </div>

            <div className="mt-3 grid gap-2 sm:grid-cols-2">
                {items.map((item) => (
                    <div key={item.area} className="rounded-2xl border border-white/10 bg-black/20 px-3 py-2 shadow-inner shadow-black/25">
                        <div className="flex items-center justify-between gap-2">
                            <strong className="text-xs font-black uppercase tracking-[0.16em] text-white">{item.label}</strong>
                            <span className="rounded-full border border-current/20 px-2 py-0.5 text-[0.55rem] font-black uppercase tracking-[0.14em] text-violet-100">
                                {item.status}
                            </span>
                        </div>
                        <p className="mt-1 text-[0.68rem] font-semibold text-violet-50/75 sm:text-xs">{item.note}</p>
                    </div>
                ))}
            </div>
        </section>
    );
}
