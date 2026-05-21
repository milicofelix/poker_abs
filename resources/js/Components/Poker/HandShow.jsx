import React from 'react';

function formatMoney(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

function statusClass(status) {
    return status === 'finished'
        ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-100'
        : 'border-yellow-400/40 bg-yellow-400/10 text-yellow-100';
}

function actionBadgeClass(action) {
    if (action === 'Fold') return 'border-red-400/40 bg-red-400/10 text-red-100';
    if (action === 'Raise') return 'border-orange-400/40 bg-orange-400/10 text-orange-100';
    if (action === 'Call') return 'border-sky-400/40 bg-sky-400/10 text-sky-100';
    if (action === 'All-in') return 'border-amber-400/40 bg-amber-400/10 text-amber-100';

    return 'border-slate-400/40 bg-slate-400/10 text-slate-100';
}

function CardBadge({ card }) {
    return (
        <span className="inline-flex min-w-12 justify-center rounded-xl border border-white/10 bg-white px-3 py-2 text-sm font-black text-slate-950 shadow">
            {card.label}
        </span>
    );
}

export default function HandShow({ hand }) {
    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <header className="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-200">
                            Poker ABS • FASE 12.10
                        </p>
                        <h1 className="mt-2 text-3xl font-black">Detalhe da mão</h1>
                        <p className="mt-2 max-w-2xl break-all text-sm text-slate-300">
                            Código: {hand.code}
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <a
                            href="/poker/hands"
                            className="inline-flex w-fit rounded-xl border border-white/10 bg-white/10 px-5 py-3 font-bold text-white transition hover:bg-white/20"
                        >
                            Histórico
                        </a>
                        <a
                            href={`/poker/hands/${hand.id}/replay`}
                            className="inline-flex w-fit rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-5 py-3 font-bold text-emerald-50 transition hover:bg-emerald-300/20"
                        >
                            Replay da mão
                        </a>
                        <a
                            href="/poker"
                            className="inline-flex w-fit rounded-xl bg-white px-5 py-3 font-bold text-slate-950 transition hover:bg-emerald-100"
                        >
                            Voltar para mesa
                        </a>
                    </div>
                </header>

                <section className="grid gap-4 md:grid-cols-4">
                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Status</p>
                        <span className={`mt-3 inline-flex rounded-full border px-3 py-1 text-xs font-bold ${statusClass(hand.status)}`}>
                            {hand.statusLabel}
                        </span>
                    </div>

                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Street</p>
                        <p className="mt-3 text-2xl font-black">{hand.streetLabel}</p>
                    </div>

                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Pote</p>
                        <p className="mt-3 text-2xl font-black">{formatMoney(hand.pot)}</p>
                    </div>

                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Jogadores</p>
                        <p className="mt-3 text-2xl font-black">{hand.players?.length || 0}</p>
                    </div>
                </section>

                {hand.winner && (
                    <section className="rounded-3xl border border-amber-300/30 bg-amber-300/10 p-6 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-amber-100">Resultado</p>
                        <h2 className="mt-2 text-2xl font-black text-amber-50">
                            {hand.winner.player === 'tie' ? 'Empate' : `Vencedor: ${hand.winner.label}`}
                        </h2>
                        {hand.winner.handName && (
                            <p className="mt-1 text-sm font-semibold text-amber-100">
                                Mão vencedora: {hand.winner.handName}
                            </p>
                        )}
                    </section>
                )}

                <section className="grid gap-4 md:grid-cols-2">
                    <article className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Board</p>
                        <h2 className="mt-1 text-2xl font-black">Cartas comunitárias</h2>
                        <div className="mt-4 flex flex-wrap gap-2">
                            {(hand.board ?? []).length > 0 ? (
                                hand.board.map((card, index) => <CardBadge key={`${card.label}-${index}`} card={card} />)
                            ) : (
                                <p className="text-sm text-slate-300">Nenhuma carta comunitária registrada.</p>
                            )}
                        </div>
                    </article>

                    <article className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Blinds e dealer</p>
                        <h2 className="mt-1 text-2xl font-black">Posições da mão</h2>
                        <div className="mt-4 grid gap-2 text-sm text-slate-200">
                            <p>Dealer: <strong>{hand.stateHighlights?.dealerSeat ?? '-'}</strong></p>
                            <p>Small blind: <strong>{hand.stateHighlights?.smallBlindSeat ?? '-'}</strong></p>
                            <p>Big blind: <strong>{hand.stateHighlights?.bigBlindSeat ?? '-'}</strong></p>
                        </div>
                    </article>
                </section>

                <section className="grid gap-4 md:grid-cols-2">
                    {(hand.players ?? []).map((player) => (
                        <article
                            key={`${player.id}-${player.seat}`}
                            className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Assento {player.seat}</p>
                                    <h2 className="mt-2 text-2xl font-black">{player.name}</h2>
                                    <p className="mt-1 text-sm text-slate-300">{player.typeLabel}</p>
                                </div>
                                {player.isAllIn && (
                                    <span className="rounded-full border border-amber-300/30 bg-amber-300/10 px-3 py-1 text-xs font-bold text-amber-100">
                                        All-in
                                    </span>
                                )}
                            </div>

                            <div className="mt-4 grid gap-3 md:grid-cols-2">
                                <div className="rounded-2xl bg-slate-950/40 p-4">
                                    <p className="text-xs text-slate-400">Stack registrado</p>
                                    <p className="mt-1 text-2xl font-black">{formatMoney(player.stack)}</p>
                                </div>
                                <div className="rounded-2xl bg-slate-950/40 p-4">
                                    <p className="text-xs text-slate-400">Contribuição</p>
                                    <p className="mt-1 text-2xl font-black">{formatMoney(player.contribution)}</p>
                                </div>
                            </div>

                            <div className="mt-4 flex flex-wrap gap-2">
                                {(player.cards ?? []).length > 0 ? (
                                    player.cards.map((card, index) => <CardBadge key={`${player.id}-${card.label}-${index}`} card={card} />)
                                ) : (
                                    <p className="text-sm text-slate-300">Cartas privadas não registradas no snapshot.</p>
                                )}
                            </div>
                        </article>
                    ))}
                </section>

                {hand.sidePots?.hasSidePot && (
                    <section className="rounded-3xl border border-amber-300/30 bg-amber-300/10 p-6 shadow-2xl backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.2em] text-amber-100">Side pots</p>
                        <h2 className="mt-1 text-2xl font-black text-amber-50">Distribuição de potes</h2>
                        <p className="mt-2 text-sm font-bold text-amber-100">Total: {formatMoney(hand.sidePots.total)}</p>

                        <div className="mt-4 grid gap-3 md:grid-cols-2">
                            {(hand.sidePots.pots ?? []).map((pot, index) => (
                                <div key={index} className="rounded-2xl border border-amber-200/20 bg-slate-950/40 p-4">
                                    <p className="text-xs uppercase tracking-[0.2em] text-amber-100">Pote {index + 1}</p>
                                    <p className="mt-1 text-xl font-black">{formatMoney(pot.amount ?? pot.total ?? 0)}</p>
                                    <p className="mt-2 text-sm text-amber-50">
                                        Elegíveis: {(pot.eligibleSeats ?? pot.eligible_seats ?? []).join(', ') || '-'}
                                    </p>
                                    <p className="mt-1 text-sm text-amber-50">
                                        Vencedores: {(pot.winnerSeats ?? pot.winner_seats ?? []).join(', ') || '-'}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>
                )}

                <section className="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                    <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Linha do tempo</p>
                            <h2 className="mt-1 text-2xl font-black">Ações da mão</h2>
                        </div>
                        <p className="text-sm text-slate-300">{hand.actions.length} ações registradas</p>
                    </div>

                    {hand.actions.length === 0 ? (
                        <div className="mt-5 rounded-2xl bg-slate-950/40 p-5 text-sm text-slate-300">
                            Nenhuma ação registrada para esta mão.
                        </div>
                    ) : (
                        <div className="mt-5 overflow-hidden rounded-2xl border border-white/10">
                            <table className="w-full border-collapse text-left text-sm">
                                <thead className="bg-slate-950/60 text-xs uppercase tracking-[0.18em] text-slate-400">
                                    <tr>
                                        <th className="px-4 py-3">Hora</th>
                                        <th className="px-4 py-3">Street</th>
                                        <th className="px-4 py-3">Jogador</th>
                                        <th className="px-4 py-3">Ação</th>
                                        <th className="px-4 py-3 text-right">Valor</th>
                                        <th className="px-4 py-3 text-right">Pote após ação</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-white/10">
                                    {hand.actions.map((action) => (
                                        <tr key={action.id} className="bg-slate-950/30 align-top">
                                            <td className="px-4 py-4 text-slate-300">{action.actedAt || '-'}</td>
                                            <td className="px-4 py-4 font-bold text-slate-100">{action.streetLabel}</td>
                                            <td className="px-4 py-4 text-slate-100">{action.player}</td>
                                            <td className="px-4 py-4">
                                                <span className={`rounded-full border px-3 py-1 text-xs font-bold ${actionBadgeClass(action.action)}`}>
                                                    {action.action}
                                                </span>
                                                {action.message && (
                                                    <p className="mt-2 max-w-xs text-xs text-slate-400">{action.message}</p>
                                                )}
                                            </td>
                                            <td className="px-4 py-4 text-right font-bold text-slate-100">
                                                {formatMoney(action.amount)}
                                            </td>
                                            <td className="px-4 py-4 text-right font-bold text-emerald-100">
                                                {formatMoney(action.potAfterAction)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </main>
    );
}
