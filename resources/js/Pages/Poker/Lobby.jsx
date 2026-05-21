import React, { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PokerBadge, PokerButton, PokerEmptyState, PokerSurface } from '../../Components/Poker/Ui/PokerDesignSystem';
import { PokerFilterPill, PokerFlashMessage, PokerNavButton, PokerPageHero, PokerPageShell } from '../../Components/Poker/Ui/PokerPageLayout';

function statusLabel(status) {
    const labels = {
        waiting: 'Aguardando jogadores',
        playing: 'Em andamento',
        finished: 'Finalizada',
    };

    return labels[status] ?? status;
}

function statusTone(status) {
    const tones = {
        waiting: 'success',
        playing: 'warning',
        finished: 'neutral',
    };

    return tones[status] ?? 'neutral';
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
        <PokerPageShell tone="emerald" maxWidth="max-w-6xl">
            <PokerPageHero
                eyebrow="Poker ABS"
                title="Lobby de mesas"
                description="Crie uma mesa pública ou privada, entre em uma sala aberta e use código/convite quando a mesa não deve aparecer no lobby público."
                actions={(
                    <>
                        <PokerNavButton href="/poker/hands">Histórico</PokerNavButton>
                        <PokerNavButton href="/poker/statistics">Estatísticas</PokerNavButton>
                        <PokerNavButton href="/poker/ranking" tone="primary">Ranking</PokerNavButton>
                        <PokerNavButton href="/poker/tournaments" tone="warning">Torneios</PokerNavButton>
                        {user && <PokerNavButton href="/poker/bankroll" tone="warning">Minhas fichas</PokerNavButton>}
                    </>
                )}
                meta={user ? (
                    <>
                        <PokerBadge tone="success">Fichas: {formatChips(user.pokerBankroll)}</PokerBadge>
                        <PokerBadge tone="neutral">Logado como {user.name}</PokerBadge>
                    </>
                ) : (
                    <>
                        <PokerNavButton href="/login">Entrar</PokerNavButton>
                        <PokerNavButton href="/register" tone="primary">Criar conta</PokerNavButton>
                    </>
                )}
            />

            <PokerFlashMessage success={flash.success} error={flash.error} />

            <section className="grid gap-4 xl:grid-cols-[1fr_340px_340px]">
                <PokerSurface className="p-5" tone="soft">
                    <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-300">Filtros</p>
                    <div className="mt-4 flex flex-wrap gap-3">
                        {[
                            ['all', 'Todas'],
                            ['waiting', 'Aguardando'],
                            ['playing', 'Em andamento'],
                            ['finished', 'Finalizadas'],
                        ].map(([value, label]) => (
                            <PokerFilterPill key={value} active={statusFilter === value} onClick={() => setStatusFilter(value)}>
                                {label}
                            </PokerFilterPill>
                        ))}
                    </div>
                </PokerSurface>

                <PokerSurface as="form" onSubmit={createTable} className="p-5" tone="emerald">
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
                            <PokerFilterPill
                                key={option}
                                active={Number(buyIn) === Number(option)}
                                onClick={() => setBuyIn(option)}
                                className="px-3 py-1 text-xs"
                            >
                                {option} fichas
                            </PokerFilterPill>
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

                    <PokerButton type="submit" tone="primary" className="mt-4 w-full" disabled={!user}>
                        {privateTable ? 'Criar privada e entrar' : 'Criar e entrar'}
                    </PokerButton>
                    {!user && (
                        <p className="mt-3 text-xs text-slate-300">Faça login para criar ou entrar em uma mesa real.</p>
                    )}
                </PokerSurface>

                <PokerSurface as="form" onSubmit={joinPrivateTable} className="p-5" tone="soft">
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
                    <PokerButton type="submit" tone="secondary" className="mt-4 w-full" disabled={!user || !inviteCode.trim()}>
                        Entrar por código
                    </PokerButton>
                    <p className="mt-3 text-xs text-slate-300">Mesas privadas ficam fora da lista pública, mas continuam usando a mesma mesa estabilizada.</p>
                </PokerSurface>
            </section>

            <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {visibleTables.map((table) => {
                    const isFull = table.playersCount >= table.maxPlayers;
                    const canJoin = user && !isFull;

                    return (
                        <PokerSurface key={table.id} as="article" tone="default" className="p-5">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h2 className="text-xl font-black">{table.name}</h2>
                                    <p className="mt-1 text-sm text-slate-400">
                                        Blinds {table.smallBlind}/{table.bigBlind} • Buy-in {table.buyIn ?? table.capacity?.buyIn ?? table.capacity?.defaultBuyIn ?? 1000} • {table.playersCount}/{table.maxPlayers} jogadores
                                    </p>
                                </div>

                                <div className="flex shrink-0 flex-col items-end gap-2">
                                    <PokerBadge tone={statusTone(table.status)}>{statusLabel(table.status)}</PokerBadge>
                                    {table.capacity?.isMultiSeatCandidate && (
                                        <PokerBadge tone="warning">3+ preparado</PokerBadge>
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
                                <PokerButton type="button" onClick={() => joinTable(table)} disabled={!canJoin} tone="primary" className="w-full">
                                    {isFull ? 'Mesa cheia' : 'Entrar como jogador'}
                                </PokerButton>

                                <PokerButton as="a" href={table.url} tone="secondary" className="w-full">
                                    Assistir / abrir mesa
                                </PokerButton>
                            </div>
                        </PokerSurface>
                    );
                })}
            </section>

            {visibleTables.length === 0 && (
                <PokerEmptyState
                    eyebrow="Lobby vazio"
                    title="Nenhuma mesa pública encontrada"
                    description="Troque o filtro atual ou entre em uma mesa privada usando código/link de convite."
                />
            )}
        </PokerPageShell>
    );
}
