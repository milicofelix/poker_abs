import React from 'react';

function hasSeat(player) {
    return player?.seatNumber !== null && player?.seatNumber !== undefined;
}

function isOnline(player) {
    return Boolean(player?.isBot) || player?.status === 'online';
}

function playerInitial(player) {
    const name = player?.nickname || 'J';

    return name.trim().charAt(0).toUpperCase() || 'J';
}

function statusClasses(player) {
    if (player?.isBot) {
        return 'border-purple-200/40 bg-purple-300/15 text-purple-100';
    }

    if (isOnline(player)) {
        return 'border-emerald-200/40 bg-emerald-300/15 text-emerald-100';
    }

    return 'border-slate-400/30 bg-slate-500/10 text-slate-300';
}

function dotClasses(player) {
    if (player?.isBot) {
        return 'bg-purple-300 shadow-purple-300/60';
    }

    if (isOnline(player)) {
        return 'bg-emerald-300 shadow-emerald-300/60';
    }

    return 'bg-slate-500 shadow-slate-500/40';
}

function roleLabel(player) {
    if (player?.isBot) {
        return 'Bot IA';
    }

    if (hasSeat(player)) {
        return `Assento ${player.seatNumber}`;
    }

    return 'Assistindo';
}

function statusLabel(player) {
    if (player?.isBot) {
        return 'ativo';
    }

    return player?.status === 'online' ? 'online' : 'offline';
}

export default function PokerPresenceMiniPanel({
    players = [],
    currentUserId = null,
    currentTurnLabel = null,
    maxPlayers = 2,
    realtimeStatus = null,
}) {
    const seatedPlayers = players.filter((player) => hasSeat(player));
    const spectators = players.filter((player) => !hasSeat(player) && !player?.isBot);
    const onlineHumans = players.filter((player) => !player?.isBot && player?.status === 'online');
    const sortedPlayers = [...players].sort((a, b) => {
        const seatA = hasSeat(a) ? Number(a.seatNumber) : 999;
        const seatB = hasSeat(b) ? Number(b.seatNumber) : 999;

        if (seatA !== seatB) {
            return seatA - seatB;
        }

        return String(a?.nickname ?? '').localeCompare(String(b?.nickname ?? ''));
    });

    return (
        <section className="rounded-[1.5rem] border border-emerald-200/20 bg-slate-950/75 p-4 shadow-xl shadow-black/35 backdrop-blur">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-[0.65rem] font-black uppercase tracking-[0.26em] text-emerald-200">Presença ao vivo</p>
                    <h2 className="mt-1 text-lg font-black text-white">Jogadores na mesa</h2>
                    <p className="mt-1 text-xs font-bold text-slate-400">
                        {onlineHumans.length} online · {seatedPlayers.length}/{maxPlayers} sentados · {spectators.length} assistindo
                    </p>
                </div>

                <span className={`rounded-full border px-3 py-1 text-[0.62rem] font-black uppercase tracking-[0.18em] ${realtimeStatus?.enabled ? 'border-emerald-200/40 bg-emerald-300/15 text-emerald-100' : 'border-amber-200/30 bg-amber-300/10 text-amber-100'}`}>
                    {realtimeStatus?.enabled ? 'tempo real' : 'polling'}
                </span>
            </div>

            {currentTurnLabel && (
                <div className="mt-3 rounded-2xl border border-amber-200/25 bg-amber-300/10 px-3 py-2 text-sm font-black text-amber-50">
                    Turno atual: <span className="text-white">{currentTurnLabel}</span>
                </div>
            )}

            <div className="mt-3 space-y-2">
                {sortedPlayers.length > 0 ? sortedPlayers.map((player) => {
                    const isCurrentUser = Number(player?.userId) === Number(currentUserId);

                    return (
                        <div
                            key={player.id}
                            className={`flex items-center justify-between gap-3 rounded-2xl border px-3 py-2 ${statusClasses(player)}`}
                        >
                            <div className="flex min-w-0 items-center gap-3">
                                <div className="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-sm font-black text-white">
                                    {playerInitial(player)}
                                    <span className={`absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full border border-slate-950 shadow-[0_0_12px] ${dotClasses(player)}`} />
                                </div>

                                <div className="min-w-0">
                                    <p className="truncate text-sm font-black text-white">
                                        {isCurrentUser ? 'Você' : player.nickname}
                                    </p>
                                    <p className="truncate text-xs font-bold text-white/65">
                                        {roleLabel(player)} · {statusLabel(player)}
                                    </p>
                                </div>
                            </div>

                            {hasSeat(player) && (
                                <span className="shrink-0 rounded-full bg-white/10 px-2 py-1 text-[0.62rem] font-black uppercase tracking-[0.15em] text-white/80">
                                    S{player.seatNumber}
                                </span>
                            )}
                        </div>
                    );
                }) : (
                    <div className="rounded-2xl border border-white/10 bg-white/5 px-3 py-4 text-sm font-bold text-slate-300">
                        Nenhum jogador real conectado ainda.
                    </div>
                )}
            </div>
        </section>
    );
}
