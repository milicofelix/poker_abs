import React from 'react';
import { router } from '@inertiajs/react';

function statusClass(status) {
    return status === 'finished'
        ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-100'
        : 'border-yellow-400/40 bg-yellow-400/10 text-yellow-100';
}

function formatMoney(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

function dataFromPaginator(hands) {
    return Array.isArray(hands) ? hands : hands?.data ?? [];
}

export default function History({ hands = [], filters = {}, tables = [], players = [] }) {
    const items = dataFromPaginator(hands);

    function updateFilter(field, value) {
        router.get('/poker/hands', { ...filters, [field]: value }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <header className="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-200">
                            Poker ABS • FASE 12.10
                        </p>
                        <h1 className="mt-2 text-3xl font-black">Histórico detalhado de mãos</h1>
                        <p className="mt-2 max-w-2xl text-sm text-slate-300">
                            Consulte mãos finalizadas ou em andamento com jogadores, mesa, board,
                            pote, vencedor, side pots e linha do tempo das ações.
                        </p>
                    </div>

                    <a
                        href="/poker"
                        className="inline-flex w-fit rounded-xl bg-white px-5 py-3 font-bold text-slate-950 transition hover:bg-emerald-100"
                    >
                        Voltar para mesa
                    </a>
                </header>

                <section className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                    <div className="grid gap-3 md:grid-cols-4">
                        <label className="grid gap-2 text-sm font-bold text-slate-200">
                            Jogador
                            <select
                                value={filters.player || ''}
                                onChange={(event) => updateFilter('player', event.target.value)}
                                className="rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-white outline-none"
                            >
                                <option value="">Todos</option>
                                {players.map((player) => (
                                    <option key={player.id} value={player.id}>{player.name}</option>
                                ))}
                            </select>
                        </label>

                        <label className="grid gap-2 text-sm font-bold text-slate-200">
                            Mesa
                            <select
                                value={filters.table_id || ''}
                                onChange={(event) => updateFilter('table_id', event.target.value)}
                                className="rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-white outline-none"
                            >
                                <option value="">Todas</option>
                                {tables.map((table) => (
                                    <option key={table.id} value={table.id}>{table.name}</option>
                                ))}
                            </select>
                        </label>

                        <label className="grid gap-2 text-sm font-bold text-slate-200">
                            Resultado
                            <select
                                value={filters.result || 'all'}
                                onChange={(event) => updateFilter('result', event.target.value)}
                                className="rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-white outline-none"
                            >
                                <option value="all">Todos</option>
                                <option value="won">Somente vencidas</option>
                                <option value="lost">Somente perdidas</option>
                            </select>
                        </label>

                        <label className="grid gap-2 text-sm font-bold text-slate-200">
                            Período
                            <select
                                value={filters.period || 'all'}
                                onChange={(event) => updateFilter('period', event.target.value)}
                                className="rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-white outline-none"
                            >
                                <option value="all">Todo período</option>
                                <option value="today">Hoje</option>
                                <option value="7d">Últimos 7 dias</option>
                                <option value="30d">Últimos 30 dias</option>
                            </select>
                        </label>
                    </div>
                </section>

                {items.length === 0 ? (
                    <section className="rounded-3xl border border-white/10 bg-white/10 p-8 text-center shadow-2xl backdrop-blur">
                        <h2 className="text-xl font-bold">Nenhuma mão encontrada</h2>
                        <p className="mt-2 text-sm text-slate-300">
                            Inicie uma mão ou ajuste os filtros para visualizar o histórico detalhado.
                        </p>
                    </section>
                ) : (
                    <section className="grid gap-4">
                        {items.map((hand) => (
                            <article
                                key={hand.id}
                                className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur"
                            >
                                <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-3">
                                            <h2 className="text-xl font-black">{hand.table}</h2>
                                            <span className={`rounded-full border px-3 py-1 text-xs font-bold ${statusClass(hand.status)}`}>
                                                {hand.statusLabel}
                                            </span>
                                            <span className="rounded-full border border-white/10 bg-slate-950/40 px-3 py-1 text-xs font-bold text-slate-200">
                                                {hand.streetLabel}
                                            </span>
                                            {hand.hasSidePot && (
                                                <span className="rounded-full border border-amber-300/30 bg-amber-300/10 px-3 py-1 text-xs font-bold text-amber-100">
                                                    Side pot
                                                </span>
                                            )}
                                        </div>

                                        <p className="mt-2 max-w-2xl break-all text-xs text-slate-400">
                                            Código: {hand.code}
                                        </p>

                                        {hand.winnerLabel && (
                                            <p className="mt-2 text-sm font-bold text-amber-100">
                                                Vencedor: {hand.winnerLabel}
                                                {hand.winningHandName ? ` • ${hand.winningHandName}` : ''}
                                            </p>
                                        )}

                                        <a
                                            href={`/poker/hands/${hand.id}`}
                                            className="mt-4 inline-flex rounded-xl bg-emerald-400 px-4 py-2 text-sm font-bold text-emerald-950 transition hover:bg-emerald-300"
                                        >
                                            Ver detalhes
                                        </a>
                                    </div>

                                    <div className="text-left md:text-right">
                                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Início</p>
                                        <p className="font-bold text-slate-100">{hand.startedAt || '-'}</p>
                                    </div>
                                </div>

                                <div className="mt-5 grid gap-3 md:grid-cols-5">
                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Pote</p>
                                        <p className="mt-1 text-2xl font-black">{formatMoney(hand.pot)}</p>
                                    </div>

                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Jogadores</p>
                                        <p className="mt-1 text-2xl font-black">{hand.playersCount}</p>
                                    </div>

                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Board</p>
                                        <p className="mt-1 text-2xl font-black">{hand.boardCount}/5</p>
                                    </div>

                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Ações</p>
                                        <p className="mt-1 text-2xl font-black">{hand.actionsCount}</p>
                                    </div>

                                    <div className="rounded-2xl bg-slate-950/40 p-4">
                                        <p className="text-xs text-slate-400">Fim</p>
                                        <p className="mt-1 text-sm font-bold text-slate-100">{hand.finishedAt || 'Ainda em andamento'}</p>
                                    </div>
                                </div>

                                {hand.lastAction && (
                                    <div className="mt-4 rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Última ação</p>
                                        <p className="mt-2 font-bold text-slate-100">
                                            {hand.lastAction.player}: {hand.lastAction.action}
                                            {hand.lastAction.amount > 0 ? ` ${formatMoney(hand.lastAction.amount)}` : ''}
                                        </p>
                                        {hand.lastAction.message && (
                                            <p className="mt-1 text-sm text-slate-300">{hand.lastAction.message}</p>
                                        )}
                                    </div>
                                )}
                            </article>
                        ))}
                    </section>
                )}
            </div>
        </main>
    );
}
