import React, { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

function statusLabel(status) {
    const labels = {
        waiting: 'Aguardando jogadores',
        playing: 'Em andamento',
        finished: 'Finalizada',
    };

    return labels[status] ?? status;
}

function formatChips(value) {
    return new Intl.NumberFormat('pt-BR').format(Number(value ?? 0));
}

function streetLabel(street) {
    const labels = {
        pre_flop: 'Pré-flop',
        flop: 'Flop',
        turn: 'Turn',
        river: 'River',
        showdown: 'Showdown',
    };

    return labels[street] ?? street;
}

export default function Lobby({ tables = [], tableCreation = {} }) {
    const { auth, flash = {} } = usePage().props;
    const user = auth?.user;
    const [tableName, setTableName] = useState('');
    const [privateTable, setPrivateTable] = useState(false);
    const maxPlayersOptions = tableCreation.maxPlayersOptions ?? [
        { value: 2, label: '2 jogadores · motor atual', isCurrentEngine: true, isMultiSeatCandidate: false },
        { value: 3, label: '3 jogadores · preparação multi-seat', isCurrentEngine: false, isMultiSeatCandidate: true },
        { value: 4, label: '4 jogadores · preparação multi-seat', isCurrentEngine: false, isMultiSeatCandidate: true },
        { value: 5, label: '5 jogadores · preparação multi-seat', isCurrentEngine: false, isMultiSeatCandidate: true },
        { value: 6, label: '6 jogadores · preparação multi-seat', isCurrentEngine: false, isMultiSeatCandidate: true },
    ];
    const [maxPlayers, setMaxPlayers] = useState(tableCreation.defaultMaxPlayers ?? 2);
    const [buyIn, setBuyIn] = useState(tableCreation.defaultBuyIn ?? 1000);
    const buyInOptions = tableCreation.buyInOptions ?? [500, 1000, 2000, 5000, 10000];
    const [inviteCode, setInviteCode] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');

    const visibleTables = useMemo(() => {
        if (statusFilter === 'all') {
            return tables;
        }

        return tables.filter((table) => table.status === statusFilter);
    }, [statusFilter, tables]);

    function createTable(event) {
        event.preventDefault();

        router.post('/poker/tables', {
            name: tableName,
            is_private: privateTable,
            max_players: Number(maxPlayers),
            buy_in: Number(buyIn),
        });
    }

    function joinPrivateTable(event) {
        event.preventDefault();

        if (! user) {
            router.visit('/login');
            return;
        }

        router.post('/poker/private-tables/join', {
            invite_code: inviteCode,
        });
    }

    function joinTable(table) {
        if (! user) {
            router.visit('/login');
            return;
        }

        router.post(`/poker/tables/${table.id}/join`);
    }

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <header className="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-sm font-bold uppercase tracking-[0.3em] text-emerald-300">Poker ABS</p>
                        <h1 className="mt-2 text-3xl font-black">Lobby de mesas</h1>
                        <p className="mt-2 max-w-2xl text-sm text-slate-300">
                            Crie uma mesa pública ou privada, entre em uma sala aberta e use código/convite quando a mesa não deve aparecer no lobby público.
                        </p>
                    </div>

                    <div className="flex flex-col gap-3 md:items-end">
                        <nav className="flex flex-wrap gap-2 md:justify-end">
                            <a
                                href="/poker/hands"
                                className="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-black text-white transition hover:bg-white/20"
                            >
                                Histórico
                            </a>
                            <a
                                href="/poker/statistics"
                                className="rounded-xl border border-cyan-300/30 bg-cyan-300/10 px-3 py-2 text-xs font-black text-cyan-100 transition hover:bg-cyan-300/20"
                            >
                                Estatísticas
                            </a>
                            <a
                                href="/poker/ranking"
                                className="rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-3 py-2 text-xs font-black text-emerald-100 transition hover:bg-emerald-300/20"
                            >
                                Ranking
                            </a>
                            <a
                                href="/poker/tournaments"
                                className="rounded-xl border border-violet-300/30 bg-violet-300/10 px-3 py-2 text-xs font-black text-violet-100 transition hover:bg-violet-300/20"
                            >
                                Torneios
                            </a>
                            {user && (
                                <a
                                    href="/poker/bankroll"
                                    className="rounded-xl border border-amber-300/30 bg-amber-300/10 px-3 py-2 text-xs font-black text-amber-100 transition hover:bg-amber-300/20"
                                >
                                    Minhas fichas
                                </a>
                            )}
                        </nav>

                        {user ? (
                            <div className="flex flex-wrap items-center gap-2 md:justify-end">
                                <div className="rounded-xl border border-emerald-300/20 bg-emerald-300/10 px-4 py-2 text-sm text-emerald-100">
                                    Fichas: <strong className="text-white">{formatChips(user.pokerBankroll)} </strong>
                                </div>
                                <div className="rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm text-slate-200">
                                    Logado como <strong className="text-white">{user.name}</strong>
                                </div>
                            </div>
                        ) : (
                            <div className="flex flex-wrap gap-3">
                                <a
                                    href="/login"
                                    className="rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20"
                                >
                                    Entrar
                                </a>
                                <a
                                    href="/register"
                                    className="rounded-xl bg-white px-4 py-2 text-sm font-black text-slate-950 transition hover:bg-emerald-100"
                                >
                                    Criar conta
                                </a>
                            </div>
                        )}
                    </div>
                </header>

                {(flash.success || flash.error) && (
                    <div className={`rounded-2xl border px-5 py-4 text-sm font-bold ${flash.error ? 'border-red-300/30 bg-red-500/10 text-red-100' : 'border-emerald-300/30 bg-emerald-500/10 text-emerald-100'}`}>
                        {flash.error || flash.success}
                    </div>
                )}

                <section className="grid gap-4 xl:grid-cols-[1fr_340px_340px]">
                    <div className="rounded-3xl border border-white/10 bg-slate-950/60 p-5 shadow-xl">
                        <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-300">Filtros</p>
                        <div className="mt-4 flex flex-wrap gap-3">
                            {[
                                ['all', 'Todas'],
                                ['waiting', 'Aguardando'],
                                ['playing', 'Em andamento'],
                                ['finished', 'Finalizadas'],
                            ].map(([value, label]) => (
                                <button
                                    key={value}
                                    type="button"
                                    onClick={() => setStatusFilter(value)}
                                    className={`rounded-full px-4 py-2 text-sm font-black transition ${statusFilter === value ? 'bg-emerald-300 text-emerald-950' : 'bg-white/10 text-slate-200 hover:bg-white/20'}`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <form onSubmit={createTable} className="rounded-3xl border border-emerald-300/20 bg-emerald-300/10 p-5 shadow-xl">
                        <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200">Criar mesa</p>
                        <label className="mt-4 block text-sm font-bold text-slate-200" htmlFor="table-name">
                            Nome da mesa
                        </label>
                        <input
                            id="table-name"
                            value={tableName}
                            onChange={(event) => setTableName(event.target.value)}
                            placeholder="Ex: Mesa do Adriano"
                            className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-emerald-300"
                        />

                        <label className="mt-4 block text-sm font-bold text-slate-200" htmlFor="table-max-players">
                            Capacidade da mesa
                        </label>
                        <select
                            id="table-max-players"
                            value={maxPlayers}
                            onChange={(event) => setMaxPlayers(Number(event.target.value))}
                            className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-emerald-300"
                        >
                            {maxPlayersOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        {Number(maxPlayers) > (tableCreation.currentEngineMaxPlayers ?? 2) && (
                            <div className="mt-3 rounded-2xl border border-amber-200/25 bg-amber-300/10 px-3 py-2 text-xs font-bold leading-relaxed text-amber-100">
                                Entrada e assentos 3+ ficam liberados agora. A mão ainda roda no motor heads-up até as próximas fases da FASE 10.
                            </div>
                        )}

                        <label className="mt-4 block text-sm font-bold text-slate-200" htmlFor="table-buy-in">
                            Buy-in da mesa
                        </label>
                        <input
                            id="table-buy-in"
                            type="number"
                            min={tableCreation.minBuyIn ?? 200}
                            max={tableCreation.maxBuyIn ?? 10000}
                            step={tableCreation.buyInStep ?? 100}
                            list="table-buy-in-options"
                            value={buyIn}
                            onChange={(event) => setBuyIn(Number(event.target.value))}
                            className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-emerald-300"
                        />
                        <datalist id="table-buy-in-options">
                            {buyInOptions.map((option) => (
                                <option key={option} value={option} />
                            ))}
                        </datalist>
                        <div className="mt-3 flex flex-wrap gap-2">
                            {buyInOptions.map((option) => (
                                <button
                                    key={option}
                                    type="button"
                                    onClick={() => setBuyIn(option)}
                                    className={`rounded-full px-3 py-1 text-xs font-black transition ${Number(buyIn) === Number(option) ? 'bg-emerald-300 text-emerald-950' : 'bg-white/10 text-emerald-100 hover:bg-white/20'}`}
                                >
                                    {option} fichas
                                </button>
                            ))}
                        </div>
                        <p className="mt-2 text-xs font-semibold text-emerald-100/75">
                            Valor debitado do bankroll quando o jogador senta na mesa. Permitido de {tableCreation.minBuyIn ?? 200} até {tableCreation.maxBuyIn ?? 10000}, em múltiplos de {tableCreation.buyInStep ?? 100}.
                        </p>

                        <label className="mt-4 flex cursor-pointer items-start gap-3 rounded-2xl border border-white/10 bg-slate-950/40 p-3 text-sm text-slate-200">
                            <input
                                type="checkbox"
                                checked={privateTable}
                                onChange={(event) => setPrivateTable(event.target.checked)}
                                className="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950"
                            />
                            <span>
                                <strong className="block text-white">Mesa privada</strong>
                                <span className="text-xs text-slate-400">Não aparece no lobby público e gera código/link de convite.</span>
                            </span>
                        </label>

                        <button
                            type="submit"
                            className="mt-4 w-full rounded-2xl bg-emerald-400 px-5 py-3 font-black text-emerald-950 shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-60"
                            disabled={!user}
                        >
                            {privateTable ? 'Criar privada e entrar' : 'Criar e entrar'}
                        </button>
                        {!user && (
                            <p className="mt-3 text-xs text-slate-300">Faça login para criar ou entrar em uma mesa real.</p>
                        )}
                    </form>

                    <form onSubmit={joinPrivateTable} className="rounded-3xl border border-violet-300/20 bg-violet-300/10 p-5 shadow-xl">
                        <p className="text-xs font-black uppercase tracking-[0.25em] text-violet-200">Mesa privada</p>
                        <label className="mt-4 block text-sm font-bold text-slate-200" htmlFor="invite-code">
                            Código de convite
                        </label>
                        <input
                            id="invite-code"
                            value={inviteCode}
                            onChange={(event) => setInviteCode(event.target.value.toUpperCase())}
                            placeholder="Ex: A1B2C3D4"
                            className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm uppercase tracking-[0.18em] text-white outline-none transition placeholder:normal-case placeholder:tracking-normal placeholder:text-slate-500 focus:border-violet-300"
                        />
                        <button
                            type="submit"
                            className="mt-4 w-full rounded-2xl bg-violet-300 px-5 py-3 font-black text-violet-950 shadow-lg shadow-violet-950/30 transition hover:bg-violet-200 disabled:cursor-not-allowed disabled:opacity-60"
                            disabled={!user || !inviteCode.trim()}
                        >
                            Entrar por código
                        </button>
                        <p className="mt-3 text-xs text-slate-300">Mesas privadas ficam fora da lista pública, mas continuam usando a mesma mesa estabilizada.</p>
                    </form>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {visibleTables.map((table) => {
                        const isFull = table.playersCount >= table.maxPlayers;
                        const canJoin = user && !isFull;

                        return (
                            <article key={table.id} className="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-xl">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h2 className="text-xl font-black">{table.name}</h2>
                                        <p className="mt-1 text-sm text-slate-400">
                                            Blinds {table.smallBlind}/{table.bigBlind} • Buy-in {table.buyIn ?? table.capacity?.buyIn ?? table.capacity?.defaultBuyIn ?? 1000} • {table.playersCount}/{table.maxPlayers} jogadores
                                        </p>
                                    </div>

                                    <div className="flex shrink-0 flex-col items-end gap-2">
                                        <span className="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-bold text-emerald-200">
                                            {statusLabel(table.status)}
                                        </span>
                                        {table.capacity?.isMultiSeatCandidate && (
                                            <span className="rounded-full border border-amber-200/25 bg-amber-300/10 px-3 py-1 text-[0.62rem] font-black uppercase tracking-[0.14em] text-amber-100">
                                                3+ preparado
                                            </span>
                                        )}
                                    </div>
                                </div>

                                <div className="mt-5 rounded-2xl bg-white/5 p-4 text-sm text-slate-300">
                                    {table.latestHand ? (
                                        <div className="space-y-1">
                                            <p>
                                                Mão: <strong className="text-white">{statusLabel(table.latestHand.status)}</strong>
                                            </p>
                                            <p>
                                                Rodada: <strong className="text-white">{streetLabel(table.latestHand.street)}</strong>
                                            </p>
                                            <p>
                                                Pote: <strong className="text-white">{table.latestHand.pot}</strong>
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="space-y-2">
                                            <p>Nenhuma mão criada nesta mesa ainda.</p>
                                            {table.capacity?.isMultiSeatCandidate && (
                                                <p className="rounded-xl border border-amber-200/20 bg-amber-300/10 px-3 py-2 text-xs font-bold text-amber-100">
                                                    Contrato 3+ liberado para lobby/entrada/assentos. Motor da mão segue heads-up por segurança.
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>

                                <div className="mt-5 grid gap-3">
                                    <button
                                        type="button"
                                        onClick={() => joinTable(table)}
                                        disabled={!canJoin}
                                        className="inline-flex w-full justify-center rounded-2xl bg-white px-5 py-3 font-black text-slate-950 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:bg-white/20 disabled:text-slate-400"
                                    >
                                        {isFull ? 'Mesa cheia' : 'Entrar como jogador'}
                                    </button>

                                    <a
                                        href={table.url}
                                        className="inline-flex w-full justify-center rounded-2xl border border-white/10 bg-white/5 px-5 py-3 font-black text-white transition hover:bg-white/10"
                                    >
                                        Assistir / abrir mesa
                                    </a>
                                </div>
                            </article>
                        );
                    })}
                </section>

                {visibleTables.length === 0 && (
                    <div className="rounded-3xl border border-dashed border-white/20 bg-white/5 p-8 text-center text-slate-300">
                        Nenhuma mesa pública encontrada para este filtro. Mesas privadas entram apenas por código ou link de convite.
                    </div>
                )}
            </div>
        </main>
    );
}
