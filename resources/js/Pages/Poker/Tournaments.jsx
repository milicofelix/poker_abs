import React, { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

function formatChips(value) {
    return new Intl.NumberFormat('pt-BR').format(Number(value ?? 0));
}


function occupancyPercent(tournament) {
    if (tournament?.lobbySummary?.occupancyPercent !== undefined) {
        return Math.max(0, Math.min(100, Number(tournament.lobbySummary.occupancyPercent ?? 0)));
    }

    const registered = Number(tournament?.registeredPlayers ?? 0);
    const maxPlayers = Math.max(1, Number(tournament?.maxPlayers ?? 1));

    return Math.round((registered / maxPlayers) * 100);
}

function tournamentMomentum(tournament) {
    if (tournament?.status === 'running') {
        return 'Mesa em andamento';
    }

    if (tournament?.status === 'finished') {
        return 'Resultado fechado';
    }

    const missing = Number(tournament?.lobbySummary?.playersNeededToStart ?? 0);

    if (missing <= 0) {
        return 'Pronto para iniciar';
    }

    return `Faltam ${formatChips(missing)} jogador(es)`;
}

function tournamentAccent(tournament) {
    return {
        registering: 'from-emerald-400/20 via-cyan-400/10 to-slate-950/30 border-emerald-200/25',
        running: 'from-amber-400/20 via-orange-400/10 to-slate-950/30 border-amber-200/25',
        finished: 'from-slate-300/15 via-slate-500/10 to-slate-950/30 border-slate-200/15',
    }[tournament?.status] ?? 'from-violet-400/20 via-slate-500/10 to-slate-950/30 border-white/10';
}

function statusDotClass(status) {
    return {
        registering: 'bg-emerald-300 shadow-emerald-300/60',
        running: 'bg-amber-300 shadow-amber-300/60',
        finished: 'bg-slate-300 shadow-slate-300/40',
    }[status] ?? 'bg-violet-300 shadow-violet-300/60';
}

function statusTone(status) {
    return {
        registering: 'border-emerald-300/30 bg-emerald-300/10 text-emerald-100',
        running: 'border-amber-300/30 bg-amber-300/10 text-amber-100',
        finished: 'border-slate-300/20 bg-slate-300/10 text-slate-200',
    }[status] ?? 'border-white/10 bg-white/10 text-slate-200';
}

export default function Tournaments({ tournamentCenter = {} }) {
    const { auth, flash = {} } = usePage().props;
    const user = auth?.user;
    const defaults = tournamentCenter.defaults ?? {};
    const tournaments = tournamentCenter.tournaments ?? [];
    const summary = tournamentCenter.summary ?? {};
    const lobby = tournamentCenter.lobby ?? {};
    const quickFilters = lobby.quickFilters ?? [
        { value: 'all', label: 'Todos', count: summary.total ?? 0 },
        { value: 'registering', label: 'Abertos', count: summary.registering ?? 0 },
        { value: 'running', label: 'Em andamento', count: summary.running ?? 0 },
        { value: 'finished', label: 'Finalizados', count: summary.finished ?? 0 },
    ];

    const [name, setName] = useState('Torneio Sit & Go ABS');
    const [buyIn, setBuyIn] = useState(defaults.buyIn ?? 1000);
    const [startingStack, setStartingStack] = useState(defaults.startingStack ?? 5000);
    const [maxPlayers, setMaxPlayers] = useState(defaults.maxPlayers ?? 9);
    const [statusFilter, setStatusFilter] = useState('all');

    const visibleTournaments = useMemo(() => {
        if (statusFilter === 'all') {
            return tournaments;
        }

        return tournaments.filter((tournament) => tournament.status === statusFilter);
    }, [statusFilter, tournaments]);

    function createTournament(event) {
        event.preventDefault();

        if (! user) {
            router.visit('/login');
            return;
        }

        router.post('/poker/tournaments', {
            name,
            buy_in: Number(buyIn),
            starting_stack: Number(startingStack),
            max_players: Number(maxPlayers),
        });
    }

    function register(tournament) {
        if (! user) {
            router.visit('/login');
            return;
        }

        router.post(`/poker/tournaments/${tournament.id}/register`);
    }

    function startTournament(tournament) {
        router.post(`/poker/tournaments/${tournament.id}/start`);
    }

    function registerBot(tournament) {
        router.post(`/poker/tournaments/${tournament.id}/bots`);
    }

    function eliminateParticipant(tournament, participant) {
        router.post(`/poker/tournaments/${tournament.id}/participants/${participant.id}/eliminate`);
    }

    function advanceBlindLevel(tournament) {
        router.post(`/poker/tournaments/${tournament.id}/blind-level`);
    }

    function prepareFinalTable(tournament) {
        router.post(`/poker/tournaments/${tournament.id}/final-table`);
    }

    function reenterParticipant(tournament, participant) {
        router.post(`/poker/tournaments/${tournament.id}/participants/${participant.id}/reentry`);
    }

    function addonParticipant(tournament, participant) {
        router.post(`/poker/tournaments/${tournament.id}/participants/${participant.id}/addon`);
    }

    function closeOfficially(tournament) {
        router.post(`/poker/tournaments/${tournament.id}/official-close`);
    }

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-violet-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-7xl flex-col gap-6">
                <header className="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="text-sm font-black uppercase tracking-[0.35em] text-violet-200">Poker ABS · FASE {tournamentCenter.phase ?? '12.12.10'}</p>
                        <h1 className="mt-2 text-3xl font-black">Central de torneios</h1>
                        <p className="mt-2 max-w-3xl text-sm text-slate-300">
                            Torneios com inscrição, buy-in, ranking, eliminação manual, blinds progressivos, premiação automática, mesa final, reentrada, add-on e lobby avançado, persistência, retomada segura e encerramento oficial.
                        </p>
                    </div>

                    <nav className="flex flex-wrap gap-3">
                        <a href="/poker/lobby" className="rounded-xl border border-white/10 bg-white/10 px-5 py-3 text-sm font-black text-white transition hover:bg-white/20">Lobby</a>
                        <a href="/poker/ranking" className="rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-5 py-3 text-sm font-black text-emerald-100 transition hover:bg-emerald-300/20">Ranking</a>
                        <a href="/poker/profile" className="rounded-xl border border-amber-300/30 bg-amber-300/10 px-5 py-3 text-sm font-black text-amber-100 transition hover:bg-amber-300/20">Meu perfil</a>
                    </nav>
                </header>

                {(flash.success || flash.error) && (
                    <div className={`rounded-2xl border px-5 py-4 text-sm font-bold ${flash.error ? 'border-red-300/30 bg-red-500/10 text-red-100' : 'border-emerald-300/30 bg-emerald-500/10 text-emerald-100'}`}>
                        {flash.error || flash.success}
                    </div>
                )}

                <section className="grid gap-4 md:grid-cols-4">
                    {[
                        ['Total', summary.total, '♠', 'Todos os eventos cadastrados'],
                        ['Inscrições abertas', summary.registering, '♣', 'Torneios recebendo jogadores'],
                        ['Em andamento', summary.running, '♥', 'Mesas ativas agora'],
                        ['Finalizados', summary.finished, '♦', 'Resultados disponíveis'],
                    ].map(([label, value, icon, hint]) => (
                        <article key={label} className="poker-premium-stat-card poker-soft-enter rounded-[2rem] border border-white/10 bg-slate-950/55 p-5 shadow-2xl">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs font-black uppercase tracking-[0.25em] text-slate-400">{label}</p>
                                    <strong className="mt-2 block text-4xl font-black text-white">{formatChips(value)}</strong>
                                    <span className="mt-2 block text-xs font-bold text-slate-400">{hint}</span>
                                </div>
                                <span className="grid h-12 w-12 place-items-center rounded-2xl border border-white/10 bg-white/10 text-2xl shadow-inner">{icon}</span>
                            </div>
                        </article>
                    ))}
                </section>

                <section className="poker-tournament-command-center overflow-hidden rounded-[2rem] border border-sky-200/20 bg-sky-300/10 p-5 shadow-2xl">
                    <div className="relative z-10 grid gap-5 lg:grid-cols-[1fr_360px]">
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.25em] text-sky-100">{lobby.title ?? 'Lobby avançado de torneios'}</p>
                            <h2 className="mt-1 text-2xl font-black">Central visual de torneios</h2>
                            <p className="mt-2 max-w-3xl text-sm text-sky-50/80">
                                Esta etapa deixa a tela de torneios mais evidente: cards com ocupação visual, destaque do próximo torneio, mesa ilustrativa e ações mais fáceis de encontrar.
                            </p>
                            <div className="mt-4 flex flex-wrap gap-2 text-xs font-black text-sky-50">
                                <span className="rounded-full border border-white/10 bg-white/10 px-3 py-2">Ocupação em tempo real</span>
                                <span className="rounded-full border border-white/10 bg-white/10 px-3 py-2">CTA destacado</span>
                                <span className="rounded-full border border-white/10 bg-white/10 px-3 py-2">Mobile first</span>
                            </div>
                        </div>
                        <div className="rounded-[1.75rem] border border-white/10 bg-slate-950/55 p-4 text-sm text-sky-50 shadow-2xl">
                            <p className="text-xs font-black uppercase tracking-[0.2em] text-sky-200">Próximo destaque</p>
                            {lobby.nextToStart ? (
                                <>
                                    <strong className="mt-2 block text-xl text-white">{lobby.nextToStart.name}</strong>
                                    <div className="mt-3 h-3 overflow-hidden rounded-full bg-white/10">
                                        <div className="h-full rounded-full bg-gradient-to-r from-emerald-300 to-sky-300" style={{ width: `${Math.max(4, Number(lobby.nextToStart.occupancyPercent ?? 0))}%` }} />
                                    </div>
                                    <span className="mt-2 block text-xs text-sky-100/80">
                                        {lobby.nextToStart.occupancyPercent}% ocupado · faltam {lobby.nextToStart.playersNeeded} jogador(es)
                                    </span>
                                </>
                            ) : (
                                <p className="mt-2 text-sky-100/80">Nenhum torneio aberto no momento.</p>
                            )}
                        </div>
                    </div>
                </section>

                <section className="grid gap-6 lg:grid-cols-[1fr_360px]">
                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                            <div>
                                <p className="text-xs font-black uppercase tracking-[0.25em] text-violet-200">Torneios</p>
                                <h2 className="mt-1 text-2xl font-black">Salas disponíveis</h2>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                {quickFilters.map((filter) => (
                                    <button
                                        key={filter.value}
                                        type="button"
                                        onClick={() => setStatusFilter(filter.value)}
                                        className={`rounded-full px-4 py-2 text-xs font-black transition ${statusFilter === filter.value ? 'bg-violet-300 text-violet-950' : 'bg-white/10 text-slate-200 hover:bg-white/20'}`}
                                    >
                                        {filter.label} ({formatChips(filter.count)})
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="mt-5 grid gap-4">
                            {visibleTournaments.length === 0 ? (
                                <div className="rounded-2xl border border-white/10 bg-slate-950/40 p-6 text-center text-sm text-slate-300">
                                    Nenhum torneio encontrado para este filtro.
                                </div>
                            ) : visibleTournaments.map((tournament) => (
                                <article key={tournament.id} className={`poker-tournament-card poker-soft-enter relative overflow-hidden rounded-[2rem] border bg-gradient-to-br ${tournamentAccent(tournament)} p-5 shadow-2xl`}>
                                    <div className="pointer-events-none absolute inset-x-6 top-0 h-px bg-gradient-to-r from-transparent via-white/35 to-transparent" />
                                    <div className="relative z-10 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className={`inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-black ${statusTone(tournament.status)}`}>
                                                    <span className={`h-2 w-2 rounded-full shadow-lg ${statusDotClass(tournament.status)}`} />
                                                    {tournament.statusLabel}
                                                </span>
                                                <span className="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-black text-white">
                                                    {tournamentMomentum(tournament)}
                                                </span>
                                            </div>
                                            <h3 className="mt-3 text-3xl font-black tracking-tight text-white">{tournament.name}</h3>
                                            <p className="mt-2 text-sm text-slate-300">
                                                Buy-in {formatChips(tournament.buyIn)} · Stack inicial {formatChips(tournament.startingStack)} · Prize pool {formatChips(tournament.prizePool)}
                                            </p>
                                            <div className="mt-4 grid gap-3 md:grid-cols-[1fr_180px] md:items-center">
                                                <div>
                                                    <div className="flex items-center justify-between text-xs font-black uppercase tracking-[0.18em] text-slate-400">
                                                        <span>Ocupação</span>
                                                        <span>{formatChips(tournament.registeredPlayers)} / {formatChips(tournament.maxPlayers)}</span>
                                                    </div>
                                                    <div className="mt-2 h-3 overflow-hidden rounded-full bg-black/30 ring-1 ring-white/10">
                                                        <div className="h-full rounded-full bg-gradient-to-r from-emerald-300 via-sky-300 to-violet-300 shadow-[0_0_22px_rgba(125,211,252,0.35)]" style={{ width: `${Math.max(3, occupancyPercent(tournament))}%` }} />
                                                    </div>
                                                </div>
                                                <div className="rounded-2xl border border-white/10 bg-black/25 px-4 py-3 text-right">
                                                    <span className="block text-xs font-black uppercase tracking-[0.2em] text-slate-400">Ocupado</span>
                                                    <strong className="text-2xl font-black text-white">{occupancyPercent(tournament)}%</strong>
                                                </div>
                                            </div>
                                            <p className="mt-2 text-xs text-slate-400">
                                                {tournament.startsAt ? `Início previsto: ${tournament.startsAt}` : 'Início definido pelo estado do torneio'}
                                            </p>
                                            {tournament.lobbySummary && (
                                                <div className="mt-3 grid gap-2 rounded-2xl border border-sky-300/20 bg-sky-300/10 p-3 text-xs text-sky-50 sm:grid-cols-4">
                                                    <span><strong>Status do lobby</strong><br />{tournament.lobbySummary.headline}</span>
                                                    <span><strong>Ocupação</strong><br />{tournament.lobbySummary.occupancyPercent}%</span>
                                                    <span><strong>Vagas</strong><br />{formatChips(tournament.lobbySummary.availableSeats)}</span>
                                                    <span><strong>Para iniciar</strong><br />{formatChips(tournament.lobbySummary.playersNeededToStart)} jogador(es)</span>
                                                </div>
                                            )}
                                            {tournament.blindStructure && (
                                                <div className="mt-3 grid gap-2 rounded-2xl border border-amber-300/20 bg-amber-300/10 p-3 text-xs text-amber-50 sm:grid-cols-4">
                                                    <span><strong>Nível</strong><br />{tournament.blindStructure.currentLevel}</span>
                                                    <span><strong>SB / BB</strong><br />{formatChips(tournament.blindStructure.smallBlind)} / {formatChips(tournament.blindStructure.bigBlind)}</span>
                                                    <span><strong>Duração</strong><br />{tournament.blindStructure.levelMinutes} min</span>
                                                    <span><strong>Próximo</strong><br />{tournament.blindStructure.nextBlindAt ?? 'Ao iniciar'}</span>
                                                </div>
                                            )}
                                            {Array.isArray(tournament.payoutPlan) && tournament.payoutPlan.length > 0 && (
                                                <div className="mt-3 rounded-2xl border border-emerald-300/20 bg-emerald-300/10 p-3 text-xs text-emerald-50">
                                                    <p className="font-black uppercase tracking-[0.18em] text-emerald-100">Premiação</p>
                                                    <div className="mt-2 grid gap-2 sm:grid-cols-3">
                                                        {tournament.payoutPlan.map((payout) => (
                                                            <span key={`${tournament.id}-${payout.position}`} className="rounded-xl bg-slate-950/35 px-3 py-2">
                                                                <strong>{payout.position}º lugar</strong><br />
                                                                {payout.percent}% · {formatChips(payout.amount)} fichas
                                                            </span>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                            {tournament.reentryAddon && (
                                                <div className="mt-3 rounded-2xl border border-cyan-300/20 bg-cyan-300/10 p-3 text-xs text-cyan-50">
                                                    <p className="font-black uppercase tracking-[0.18em] text-cyan-100">Reentrada / Add-on</p>
                                                    <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                                        <span className="rounded-xl bg-slate-950/35 px-3 py-2">
                                                            <strong>Reentrada</strong><br />
                                                            {tournament.reentryAddon.allowReentry ? `${formatChips(tournament.reentryAddon.reentryBuyIn)} · ${formatChips(tournament.reentryAddon.reentryStack)} fichas · máx. ${tournament.reentryAddon.maxReentriesPerPlayer}` : 'Desativada'}
                                                        </span>
                                                        <span className="rounded-xl bg-slate-950/35 px-3 py-2">
                                                            <strong>Add-on</strong><br />
                                                            {tournament.reentryAddon.addonEnabled ? `${formatChips(tournament.reentryAddon.addonBuyIn)} · +${formatChips(tournament.reentryAddon.addonStack)} fichas · até nível ${tournament.reentryAddon.addonAvailableUntilBlindLevel}` : 'Desativado'}
                                                        </span>
                                                    </div>
                                                </div>
                                            )}
                                            {tournament.resumeState && (
                                                <div className="mt-3 rounded-2xl border border-indigo-300/20 bg-indigo-300/10 p-3 text-xs text-indigo-50">
                                                    <p className="font-black uppercase tracking-[0.18em] text-indigo-100">Retomada do torneio</p>
                                                    <div className="mt-2 grid gap-2 sm:grid-cols-4">
                                                        <span className="rounded-xl bg-slate-950/35 px-3 py-2"><strong>Status</strong><br />{tournament.resumeState.message}</span>
                                                        <span className="rounded-xl bg-slate-950/35 px-3 py-2"><strong>Snapshot</strong><br />{tournament.resumeState.lastSnapshotAt ?? 'Gerado ao abrir'}</span>
                                                        <span className="rounded-xl bg-slate-950/35 px-3 py-2"><strong>Blinds</strong><br />{formatChips(tournament.resumeState.snapshot?.smallBlind ?? tournament.blindStructure?.smallBlind)} / {formatChips(tournament.resumeState.snapshot?.bigBlind ?? tournament.blindStructure?.bigBlind)}</span>
                                                        <span className="rounded-xl bg-slate-950/35 px-3 py-2"><strong>Retomável</strong><br />{tournament.resumeState.isRestorable ? 'Sim' : 'Histórico'}</span>
                                                    </div>
                                                </div>
                                            )}
                                            {tournament.officialResult && (
                                                <div className="mt-3 rounded-2xl border border-lime-300/20 bg-lime-300/10 p-3 text-xs text-lime-50">
                                                    <p className="font-black uppercase tracking-[0.18em] text-lime-100">Resultado oficial</p>
                                                    <p className="mt-1 text-lime-50/80">{tournament.officialResult.summaryLabel}</p>
                                                    <div className="mt-2 grid gap-2 sm:grid-cols-4">
                                                        <span className="rounded-xl bg-slate-950/35 px-3 py-2"><strong>Campeão</strong><br />{tournament.officialResult.champion?.name ?? 'A definir'}</span>
                                                        <span className="rounded-xl bg-slate-950/35 px-3 py-2"><strong>Prize pool</strong><br />{formatChips(tournament.officialResult.prizePool)}</span>
                                                        <span className="rounded-xl bg-slate-950/35 px-3 py-2"><strong>Pago</strong><br />{formatChips(tournament.officialResult.totalPaid)}</span>
                                                        <span className="rounded-xl bg-slate-950/35 px-3 py-2"><strong>Finalizado</strong><br />{tournament.officialResult.finishedAt ?? 'Pendente'}</span>
                                                    </div>
                                                    {Array.isArray(tournament.officialResult.podium) && tournament.officialResult.podium.length > 0 && (
                                                        <div className="mt-2 grid gap-2 sm:grid-cols-3">
                                                            {tournament.officialResult.podium.map((result) => (
                                                                <span key={`${tournament.id}-official-${result.position}`} className="rounded-xl bg-slate-950/35 px-3 py-2">
                                                                    <strong>{result.position}º lugar</strong><br />
                                                                    {result.name} · {formatChips(result.prizeAmount)}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                            {tournament.finalTable?.enabled && (
                                                <div className="mt-3 rounded-2xl border border-fuchsia-300/20 bg-fuchsia-300/10 p-3 text-xs text-fuchsia-50">
                                                    <p className="font-black uppercase tracking-[0.18em] text-fuchsia-100">Mesa final</p>
                                                    <p className="mt-1 text-fuchsia-50/80">Organizada em {tournament.finalTable.startedAt ?? 'agora'} · até {tournament.finalTable.maxPlayers ?? 9} jogadores.</p>
                                                    {Array.isArray(tournament.finalTable.seatMap) && tournament.finalTable.seatMap.length > 0 && (
                                                        <div className="mt-2 grid gap-2 sm:grid-cols-3">
                                                            {tournament.finalTable.seatMap.map((seat) => (
                                                                <span key={`${tournament.id}-final-seat-${seat.seat}`} className="rounded-xl bg-slate-950/35 px-3 py-2">
                                                                    <strong>Assento {seat.seat}</strong><br />
                                                                    {seat.name} · {formatChips(seat.stack)} fichas
                                                                </span>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                            {tournament.runtimeTable?.enabled && (
                                                <div className="mt-3 rounded-2xl border border-emerald-300/20 bg-emerald-300/10 p-3 text-xs text-emerald-50">
                                                    <p className="font-black uppercase tracking-[0.18em] text-emerald-100">Mesa real conectada</p>
                                                    <p className="mt-1 text-emerald-50/80">
                                                        {tournament.runtimeTable.message} · {formatChips(tournament.runtimeTable.playersSeated)} jogadores sentados · blinds {formatChips(tournament.runtimeTable.smallBlind)} / {formatChips(tournament.runtimeTable.bigBlind)}.
                                                    </p>
                                                </div>
                                            )}
                                        </div>

                                        <div className="rounded-[1.5rem] border border-white/10 bg-black/25 p-3 shadow-inner xl:min-w-56">
                                            <p className="mb-2 text-xs font-black uppercase tracking-[0.2em] text-slate-400">Ações</p>
                                            <div className="flex flex-col gap-2 xl:items-stretch">
                                            {tournament.isRegistered && (
                                                <span className="rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-4 py-2 text-sm font-black text-emerald-100">
                                                    Você está inscrito
                                                </span>
                                            )}
                                            <button
                                                type="button"
                                                disabled={! tournament.canRegister}
                                                onClick={() => register(tournament)}
                                                className={`rounded-2xl px-5 py-3 text-sm font-black shadow-lg transition ${tournament.canRegister ? 'bg-gradient-to-r from-emerald-300 to-sky-300 text-slate-950 hover:scale-[1.02]' : 'cursor-not-allowed bg-white/10 text-slate-500'}`}
                                            >
                                                {tournament.canRegister ? 'Inscrever-se' : 'Inscrição indisponível'}
                                            </button>
                                            {tournament.canRegisterBot && (
                                                <button
                                                    type="button"
                                                    onClick={() => registerBot(tournament)}
                                                    className="rounded-xl border border-sky-300/30 bg-sky-300/10 px-5 py-3 text-sm font-black text-sky-100 transition hover:bg-sky-300/20"
                                                >
                                                    Adicionar bot
                                                </button>
                                            )}
                                            {tournament.canStart && (
                                                <button
                                                    type="button"
                                                    onClick={() => startTournament(tournament)}
                                                    className="rounded-xl border border-amber-300/30 bg-amber-300/10 px-5 py-3 text-sm font-black text-amber-100 transition hover:bg-amber-300/20"
                                                >
                                                    Iniciar torneio
                                                </button>
                                            )}
                                            {tournament.runtimeTable?.enabled && tournament.runtimeTable?.tableUrl && (
                                                <a
                                                    href={tournament.runtimeTable.tableUrl}
                                                    className="rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-5 py-3 text-center text-sm font-black text-emerald-100 transition hover:bg-emerald-300/20"
                                                >
                                                    Abrir mesa real
                                                </a>
                                            )}
                                            {tournament.canAdvanceBlind && (
                                                <button
                                                    type="button"
                                                    onClick={() => advanceBlindLevel(tournament)}
                                                    className="rounded-xl border border-orange-300/30 bg-orange-300/10 px-5 py-3 text-sm font-black text-orange-100 transition hover:bg-orange-300/20"
                                                >
                                                    Avançar blinds
                                                </button>
                                            )}
                                            {tournament.canPrepareFinalTable && (
                                                <button
                                                    type="button"
                                                    onClick={() => prepareFinalTable(tournament)}
                                                    className="rounded-xl border border-fuchsia-300/30 bg-fuchsia-300/10 px-5 py-3 text-sm font-black text-fuchsia-100 transition hover:bg-fuchsia-300/20"
                                                >
                                                    Organizar mesa final
                                                </button>
                                            )}
                                            {tournament.canCloseOfficially && (
                                                <button
                                                    type="button"
                                                    onClick={() => closeOfficially(tournament)}
                                                    className="rounded-xl border border-lime-300/30 bg-lime-300/10 px-5 py-3 text-sm font-black text-lime-100 transition hover:bg-lime-300/20"
                                                >
                                                    Encerrar oficialmente
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                    </div>

                                    <div className="relative z-10 mt-4 rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <p className="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Participantes</p>
                                        {tournament.participants.length === 0 ? (
                                            <p className="mt-3 text-sm text-slate-400">Ainda não há inscritos.</p>
                                        ) : (
                                            <div className="mt-3 flex flex-wrap gap-2">
                                                {tournament.participants.map((participant) => (
                                                    <span key={participant.id} className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-slate-950/60 px-3 py-2 text-xs font-bold text-slate-200">
                                                        <span>{participant.finishPosition ? `${participant.finishPosition}º · ` : ''}{participant.name} · {participant.statusLabel ?? participant.status} · {formatChips(participant.stack)} fichas{participant.prizeAmount > 0 ? ` · prêmio ${formatChips(participant.prizeAmount)}` : ''}</span>
                                                        {participant.canAddon && (
                                                            <button type="button" onClick={() => addonParticipant(tournament, participant)} className="rounded-full bg-cyan-400/20 px-2 py-1 text-[10px] font-black text-cyan-100 hover:bg-cyan-400/30">
                                                                Add-on
                                                            </button>
                                                        )}
                                                        {participant.canReenter && (
                                                            <button type="button" onClick={() => reenterParticipant(tournament, participant)} className="rounded-full bg-indigo-400/20 px-2 py-1 text-[10px] font-black text-indigo-100 hover:bg-indigo-400/30">
                                                                Reentrada
                                                            </button>
                                                        )}
                                                        {tournament.status === 'running' && participant.status === 'active' && (
                                                            <button type="button" onClick={() => eliminateParticipant(tournament, participant)} className="rounded-full bg-red-400/20 px-2 py-1 text-[10px] font-black text-red-100 hover:bg-red-400/30">
                                                                Eliminar
                                                            </button>
                                                        )}
                                                    </span>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </article>
                            ))}
                        </div>
                    </div>

                    <form onSubmit={createTournament} className="rounded-3xl border border-violet-300/20 bg-violet-300/10 p-5 shadow-2xl">
                        <p className="text-xs font-black uppercase tracking-[0.25em] text-violet-100">Criar torneio</p>
                        <h2 className="mt-1 text-xl font-black">Sit & Go básico</h2>
                        <p className="mt-2 text-sm text-violet-100/80">
                            Nesta etapa, a criação deixa o torneio em inscrições abertas. O lobby mostra ocupação, vagas, status, premiação, reentrada e add-on em tempo real.
                        </p>

                        <label className="mt-4 block text-sm font-bold text-slate-200" htmlFor="tournament-name">Nome</label>
                        <input id="tournament-name" value={name} onChange={(event) => setName(event.target.value)} className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none focus:border-violet-300" />

                        <label className="mt-4 block text-sm font-bold text-slate-200" htmlFor="tournament-buy-in">Buy-in</label>
                        <input id="tournament-buy-in" type="number" min="100" step="100" value={buyIn} onChange={(event) => setBuyIn(event.target.value)} className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none focus:border-violet-300" />

                        <label className="mt-4 block text-sm font-bold text-slate-200" htmlFor="tournament-stack">Stack inicial</label>
                        <input id="tournament-stack" type="number" min="500" step="500" value={startingStack} onChange={(event) => setStartingStack(event.target.value)} className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none focus:border-violet-300" />

                        <label className="mt-4 block text-sm font-bold text-slate-200" htmlFor="tournament-max-players">Máximo de jogadores</label>
                        <input id="tournament-max-players" type="number" min="2" max="200" value={maxPlayers} onChange={(event) => setMaxPlayers(event.target.value)} className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none focus:border-violet-300" />

                        <div className="mt-4 rounded-2xl border border-white/10 bg-slate-950/40 p-4 text-sm text-slate-300">
                            Blinds padrão: {formatChips(defaults.smallBlind ?? 25)} / {formatChips(defaults.bigBlind ?? 50)} · níveis de {defaults.blindLevelMinutes ?? 10} min · payout padrão 70/20/10 · mesa final até {defaults.finalTableMaxPlayers ?? 9} jogadores · reentrada máx. {defaults.maxReentriesPerPlayer ?? 1}x · add-on até nível {defaults.addonAvailableUntilBlindLevel ?? 3}
                        </div>

                        <button type="submit" className="mt-5 w-full rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950 transition hover:bg-violet-100">
                            Criar torneio
                        </button>
                    </form>
                </section>
            </div>
        </main>
    );
}
