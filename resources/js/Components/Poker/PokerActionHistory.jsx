import React from 'react';

function actorLabel(actor) {
    return actor === 'opponent' ? 'Oponente' : 'Você';
}

export default function PokerActionHistory({ history }) {
    if (!history || history.length === 0) {
        return (
            <section className="rounded-2xl border border-white/10 bg-slate-950/60 p-4 text-slate-300 shadow-xl">
                <h2 className="mb-2 text-lg font-semibold text-white">Histórico da mão</h2>
                <p>Nenhuma ação realizada ainda.</p>
            </section>
        );
    }

    return (
        <section className="rounded-2xl border border-white/10 bg-slate-950/60 p-4 shadow-xl">
            <h2 className="mb-4 text-lg font-semibold text-white">Histórico da mão</h2>

            <ol className="space-y-3">
                {history.map((item, index) => (
                    <li
                        key={`${item.actor}-${item.action}-${index}`}
                        className="rounded-xl border border-white/10 bg-white/5 p-3 text-sm text-slate-200"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <strong className={item.actor === 'opponent' ? 'text-amber-200' : 'text-emerald-200'}>
                                {index + 1}. {actorLabel(item.actor)} — {item.street} — {item.action}
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
