import React from 'react';

function statusLabel(status) {
    const labels = {
        online: 'Online',
        offline: 'Offline',
        bot_online: 'Bot',
    };

    return labels[status] ?? status;
}

function playerHasSeat(player) {
    return player?.seatNumber !== null && player?.seatNumber !== undefined;
}

function countSeatedPlayers(players) {
    return players.filter((player) => playerHasSeat(player)).length;
}

function countSpectators(players) {
    return players.filter((player) => !playerHasSeat(player) && !player?.isBot).length;
}

function countOnlineHumans(players) {
    return players.filter((player) => !player?.isBot && player?.status === 'online').length;
}

function presenceDotClass(player) {
    if (player?.isBot) {
        return 'bg-purple-300 shadow-purple-300/60';
    }

    if (player?.status === 'online') {
        return 'bg-emerald-300 shadow-emerald-300/60';
    }

    return 'bg-slate-500 shadow-slate-500/30';
}

const FUTURE_MULTI_SEAT_COUNT = 6;

function playerRoleLabel(player) {
    if (player?.isBot) {
        return 'Bot sentado';
    }

    if (playerHasSeat(player)) {
        return `Sentado no assento ${player.seatNumber}`;
    }

    return 'Assistindo';
}

function buildFutureSeatSlots(maxPlayers) {
    const activeSeats = Math.max(Number(maxPlayers) || 2, 2);

    if (activeSeats >= FUTURE_MULTI_SEAT_COUNT) {
        return [];
    }

    return Array.from(
        { length: FUTURE_MULTI_SEAT_COUNT - activeSeats },
        (_, index) => ({
            seatNumber: activeSeats + index + 1,
            status: 'planned',
        }),
    );
}

export default function PokerRealPlayersPanel({
    players = [],
    seatSlots = [],
    currentUserId = null,
    maxPlayers = 2,
    loading = false,
    joining = false,
    seating = false,
    leaving = false,
    addingBot = false,
    message = null,
    onJoin = null,
    onSeat = null,
    onLeave = null,
    onAddBot = null,
    botProfiles = [],
    botDifficulties = [],
    botDifficultyOptions = [],
}) {
    const [selectedBotDifficulty, setSelectedBotDifficulty] = React.useState(
        botDifficulties.includes('normal') ? 'normal' : (botDifficulties[0] ?? 'normal'),
    );
    const currentPlayer = players.find((player) => Number(player.userId) === Number(currentUserId));
    const currentPlayerIsSeated = playerHasSeat(currentPlayer);
    const hasJoined = Boolean(currentPlayer);
    const hasBot = players.some((player) => Boolean(player?.isBot) && playerHasSeat(player));
    const canReplaceBot = hasBot && onAddBot;
    const seatedCount = countSeatedPlayers(players);
    const spectatorCount = countSpectators(players);
    const onlineHumanCount = countOnlineHumans(players);
    const normalizedSeatSlots = seatSlots.length > 0
        ? seatSlots
        : Array.from({ length: maxPlayers }, (_, index) => ({
            seatNumber: index + 1,
            status: 'available',
            player: players.find((player) => Number(player.seatNumber) === index + 1) ?? null,
        }));
    const spectators = players.filter((player) => !playerHasSeat(player) && !player?.isBot);
    const futureSeatSlots = buildFutureSeatSlots(maxPlayers);
    const hasFutureSeatSlots = futureSeatSlots.length > 0;

    return (
        <section className="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-xl">
            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-300">Presença da mesa</p>
                    <h2 className="mt-1 text-xl font-black">Jogadores online</h2>
                    <p className="mt-1 text-sm text-slate-400">
                        Veja quem está sentado, quem está assistindo e quais assentos ainda estão livres.
                    </p>
                </div>

                {onJoin && !hasJoined && (
                    <button
                        type="button"
                        onClick={onJoin}
                        disabled={loading}
                        className="rounded-2xl bg-emerald-400 px-4 py-2 text-sm font-black text-emerald-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {joining ? 'Entrando...' : 'Entrar na mesa'}
                    </button>
                )}

                {hasJoined && !currentPlayerIsSeated && (
                    <span className="rounded-2xl border border-amber-300/30 bg-amber-400/10 px-4 py-2 text-sm font-black text-amber-100">
                        Você está assistindo · escolha um assento
                    </span>
                )}

                {currentPlayerIsSeated && (
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-2xl border border-emerald-300/30 bg-emerald-400/10 px-4 py-2 text-sm font-black text-emerald-100">
                            Você está no assento {currentPlayer.seatNumber}
                        </span>

                        {onLeave && (
                            <button
                                type="button"
                                onClick={onLeave}
                                disabled={loading}
                                className="rounded-2xl border border-rose-300/30 bg-rose-400/10 px-4 py-2 text-sm font-black text-rose-100 transition hover:bg-rose-400/20 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {leaving ? 'Saindo...' : 'Sair da mesa'}
                            </button>
                        )}
                    </div>
                )}
            </div>

            <div className="mt-4 grid gap-3 sm:grid-cols-3">
                <div className="rounded-2xl border border-emerald-300/20 bg-emerald-400/10 p-3">
                    <p className="text-xs font-black uppercase tracking-[0.2em] text-emerald-200">Online</p>
                    <p className="mt-1 text-2xl font-black text-white">{onlineHumanCount}</p>
                    <p className="text-xs text-emerald-50/80">jogador(es) reais ativos</p>
                </div>
                <div className="rounded-2xl border border-cyan-300/20 bg-cyan-400/10 p-3">
                    <p className="text-xs font-black uppercase tracking-[0.2em] text-cyan-200">Sentados</p>
                    <p className="mt-1 text-2xl font-black text-white">{seatedCount}/{maxPlayers}</p>
                    <p className="text-xs text-cyan-50/80">assentos ocupados</p>
                </div>
                <div className="rounded-2xl border border-amber-300/20 bg-amber-400/10 p-3">
                    <p className="text-xs font-black uppercase tracking-[0.2em] text-amber-200">Assistindo</p>
                    <p className="mt-1 text-2xl font-black text-white">{spectatorCount}</p>
                    <p className="text-xs text-amber-50/80">espectador(es)</p>
                </div>
            </div>

            {message && (
                <div className="mt-4 rounded-2xl border border-emerald-300/20 bg-emerald-400/10 px-4 py-3 text-sm font-bold text-emerald-100">
                    {message}
                </div>
            )}

            <div className="mt-4 flex flex-col gap-2 rounded-2xl border border-cyan-300/15 bg-cyan-400/5 p-4">
                <div className="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-xs font-black uppercase tracking-[0.22em] text-cyan-200">Mapa de assentos</p>
                        <p className="text-sm text-cyan-50/80">
                            A mesa segue ativa em heads-up, mas a interface já está pronta para expandir visualmente para mais assentos.
                        </p>
                    </div>

                    {hasFutureSeatSlots && (
                        <span className="w-fit rounded-full border border-amber-300/25 bg-amber-400/10 px-3 py-1 text-xs font-black uppercase tracking-[0.16em] text-amber-100">
                            Multi-seat em preparação
                        </span>
                    )}
                </div>
            </div>

            <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                {normalizedSeatSlots.map((seat) => {
                    const player = seat.player ?? players.find((item) => Number(item.seatNumber) === Number(seat.seatNumber));
                    const occupied = Boolean(player);
                    const isBot = Boolean(player?.isBot);
                    const isOnline = player?.status === 'online' || isBot;
                    const isCurrentSeat = occupied && Number(player.userId) === Number(currentUserId);
                    const canChooseSeat = hasJoined && !currentPlayerIsSeated && !occupied && onSeat;

                    return (
                        <div
                            key={seat.seatNumber}
                            className={`rounded-2xl border p-4 ${isCurrentSeat ? 'border-emerald-300/50 bg-emerald-400/10' : occupied && !isOnline ? 'border-slate-500/30 bg-slate-700/20' : 'border-white/10 bg-white/5'}`}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs font-black uppercase tracking-[0.2em] text-slate-400">
                                        Assento {seat.seatNumber}
                                    </p>

                                    {occupied ? (
                                        <>
                                            <p className="mt-1 flex items-center gap-2 font-black text-white">
                                                <span className={`h-2.5 w-2.5 rounded-full shadow-[0_0_12px] ${presenceDotClass(player)}`} />
                                                {isCurrentSeat ? 'Você' : player.nickname}
                                            </p>
                                            <p className="text-xs text-slate-400">{playerRoleLabel(player)} · Stack inicial: {player.stack}</p>
                                            {isBot && (
                                                <p className="mt-1 text-xs font-bold text-purple-200">
                                                    Perfil: {player.botProfile ?? 'bot'} · Dificuldade: {player.botDifficultyLabel ?? player.botDifficulty ?? 'normal'}
                                                </p>
                                            )}
                                        </>
                                    ) : (
                                        <p className="mt-1 font-black text-slate-300">Livre</p>
                                    )}
                                </div>

                                {occupied ? (
                                    <span className={`rounded-full px-3 py-1 text-xs font-bold ${isOnline ? 'bg-emerald-400/15 text-emerald-200' : 'bg-slate-400/10 text-slate-300'}`}>
                                        {isBot ? 'Bot IA' : statusLabel(player.status)}
                                    </span>
                                ) : (
                                    <span className="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-slate-300">
                                        Disponível
                                    </span>
                                )}
                            </div>

                            {canChooseSeat && (
                                <button
                                    type="button"
                                    onClick={() => onSeat(seat.seatNumber)}
                                    disabled={loading}
                                    className="mt-4 w-full rounded-xl bg-amber-400 px-4 py-2 text-sm font-black text-amber-950 transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {seating ? 'Sentando...' : `Sentar no assento ${seat.seatNumber}`}
                                </button>
                            )}
                        </div>
                    );
                })}

                {futureSeatSlots.map((seat) => (
                    <div
                        key={`planned-seat-${seat.seatNumber}`}
                        className="rounded-2xl border border-dashed border-amber-300/25 bg-amber-400/5 p-4 opacity-90"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-black uppercase tracking-[0.2em] text-amber-200/80">
                                    Assento {seat.seatNumber}
                                </p>
                                <p className="mt-1 font-black text-amber-50">Em preparação</p>
                                <p className="mt-1 text-xs text-amber-50/70">
                                    Reservado para a futura engine multiplayer 3+.
                                </p>
                            </div>

                            <span className="rounded-full border border-amber-300/20 bg-amber-400/10 px-3 py-1 text-xs font-bold text-amber-100">
                                Futuro
                            </span>
                        </div>

                        <button
                            type="button"
                            disabled
                            className="mt-4 w-full cursor-not-allowed rounded-xl border border-amber-300/15 bg-slate-950/40 px-4 py-2 text-sm font-black text-amber-100/50"
                        >
                            Disponível em fase futura
                        </button>
                    </div>
                ))}
            </div>

            {hasFutureSeatSlots && (
                <div className="mt-4 rounded-2xl border border-amber-300/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-50">
                    <strong className="font-black">Preparação multi-seat:</strong> os assentos extras são apenas visuais por enquanto.
                    O fluxo de jogadas continua limitado ao modo heads-up até a engine 3+ ser ativada com testes próprios.
                </div>
            )}

            {spectators.length > 0 && (
                <div className="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p className="text-xs font-black uppercase tracking-[0.2em] text-amber-200">Espectadores</p>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {spectators.map((spectator) => (
                            <span
                                key={spectator.id}
                                className="inline-flex items-center gap-2 rounded-full border border-amber-200/20 bg-amber-300/10 px-3 py-1 text-xs font-bold text-amber-50"
                            >
                                <span className={`h-2 w-2 rounded-full shadow-[0_0_10px] ${presenceDotClass(spectator)}`} />
                                {Number(spectator.userId) === Number(currentUserId) ? 'Você assistindo' : spectator.nickname}
                            </span>
                        ))}
                    </div>
                </div>
            )}

            {onAddBot && botProfiles.length > 0 && (normalizedSeatSlots.some((seat) => !seat.player) || canReplaceBot) && (
                <div className="mt-4 rounded-2xl border border-purple-300/20 bg-purple-400/10 p-4">
                    <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.2em] text-purple-200">Bots/IA</p>
                            <p className="text-sm text-purple-100">Adicione bots para completar a mesa ou troque o adversário após uma mão finalizada.</p>
                        </div>

                        <div className="flex flex-col gap-2 md:items-end">
                            {botDifficultyOptions.length > 0 && (
                                <label className="flex flex-col gap-1 text-xs font-bold text-purple-100">
                                    Dificuldade
                                    <select
                                        value={selectedBotDifficulty}
                                        onChange={(event) => setSelectedBotDifficulty(event.target.value)}
                                        className="rounded-xl border border-purple-200/30 bg-slate-950 px-3 py-2 text-sm font-black text-purple-50 outline-none transition focus:border-purple-200/70"
                                    >
                                        {botDifficultyOptions.map((difficulty) => (
                                            <option key={difficulty.key} value={difficulty.key}>
                                                {difficulty.label}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            )}

                            <div className="flex flex-wrap gap-2 md:justify-end">
                                {botProfiles.map((profile) => (
                                    <button
                                        key={profile.key}
                                        type="button"
                                        onClick={() => onAddBot(profile.key, selectedBotDifficulty, { replaceBot: canReplaceBot })}
                                        disabled={addingBot || loading}
                                        className="rounded-xl bg-purple-300 px-3 py-2 text-xs font-black text-purple-950 transition hover:bg-purple-200 disabled:cursor-not-allowed disabled:opacity-60"
                                        title={profile.description}
                                    >
                                        {canReplaceBot ? `Trocar por ${profile.label}` : profile.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {!hasJoined && (
                <div className="mt-4 rounded-2xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-50">
                    {players.filter((player) => Boolean(player?.isBot) && playerHasSeat(player)).length >= 2
                        ? 'Você está assistindo uma simulação entre bots. Entre na mesa se quiser participar de uma próxima mão.'
                        : 'Você ainda está assistindo. Entre na mesa para escolher um assento e participar da mão.'}
                </div>
            )}
        </section>
    );
}
