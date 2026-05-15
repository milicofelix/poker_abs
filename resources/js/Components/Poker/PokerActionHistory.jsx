import React from 'react';

function actorLabel(item) {
    if (item?.actorLabel) {
        return item.actorLabel;
    }

    return item?.actor === 'opponent' ? 'Oponente' : 'Você';
}

export default function PokerActionHistory({ history }) {
    if (!history || history.length === 0) {
        return (
            <section className="rounded-2xl border border-white/10 bg-slate-950/60 p-3 text-slate-300 shadow-xl sm:p-4">
                <h2 className="mb-2 text-lg font-semibold text-white">Histórico da mão</h2>
                <p>Nenhuma ação realizada ainda.</p>
            </section>
        );
    }

    return (
        <section className="rounded-2xl border border-white/10 bg-slate-950/60 p-3 shadow-xl sm:p-4 xl:max-h-[760px] xl:overflow-y-auto">
            <h2 className="mb-4 text-lg font-semibold text-white">Histórico da mão</h2>

            <ol className="space-y-2 sm:space-y-3">
                {history.map((item, index) => (
                    <li
                        key={`${item.actor}-${item.action}-${index}`}
                        className="rounded-xl border border-white/10 bg-white/5 p-2.5 text-xs text-slate-200 sm:p-3 sm:text-sm"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <strong className={item.actor === 'opponent' ? 'text-amber-200' : 'text-emerald-200'}>
                                {index + 1}. {actorLabel(item)} — {item.street} — {item.action}
                            </strong>

                            <span className="text-slate-400">
                                Pote: {item.pot}
                            </span>
                        </div>

                        <p className="mt-1 text-slate-300">{item.message}</p>
                    </li>
                ))}
            </ol>
        </section>
    );
}
