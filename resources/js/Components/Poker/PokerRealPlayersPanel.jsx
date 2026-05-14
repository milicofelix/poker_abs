import React from 'react';

function statusLabel(status) {
    const labels = {
        online: 'Online',
        offline: 'Offline',
    };

    return labels[status] ?? status;
}

function playerHasSeat(player) {
    return player?.seatNumber !== null && player?.seatNumber !== undefined;
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
    message = null,
    onJoin = null,
    onSeat = null,
    onLeave = null,
}) {
    const currentPlayer = players.find((player) => Number(player.userId) === Number(currentUserId));
    const currentPlayerIsSeated = playerHasSeat(currentPlayer);
    const hasJoined = Boolean(currentPlayer);
    const normalizedSeatSlots = seatSlots.length > 0
        ? seatSlots
        : Array.from({ length: maxPlayers }, (_, index) => ({
            seatNumber: index + 1,
            status: 'available',
            player: players.find((player) => Number(player.seatNumber) === index + 1) ?? null,
        }));

    return (
        <section className="rounded-3xl border border-white/10 bg-slate-950/70 p-5 shadow-xl">
            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-300">Jogadores reais</p>
                    <h2 className="mt-1 text-xl font-black">Assentos da mesa</h2>
                    <p className="mt-1 text-sm text-slate-400">
                        Entre na mesa, escolha um assento livre e acompanhe quem está online ou offline.
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
                        Escolha um assento
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

            {message && (
                <div className="mt-4 rounded-2xl border border-emerald-300/20 bg-emerald-400/10 px-4 py-3 text-sm font-bold text-emerald-100">
                    {message}
                </div>
            )}

            <div className="mt-4 grid gap-3 md:grid-cols-2">
                {normalizedSeatSlots.map((seat) => {
                    const player = seat.player ?? players.find((item) => Number(item.seatNumber) === Number(seat.seatNumber));
                    const occupied = Boolean(player);
                    const isOnline = player?.status === 'online';
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
                                            <p className="mt-1 font-black text-white">
                                                {isCurrentSeat ? 'Você' : player.nickname}
                                            </p>
                                            <p className="text-xs text-slate-400">Stack inicial: {player.stack}</p>
                                        </>
                                    ) : (
                                        <p className="mt-1 font-black text-slate-300">Livre</p>
                                    )}
                                </div>

                                {occupied ? (
                                    <span className={`rounded-full px-3 py-1 text-xs font-bold ${isOnline ? 'bg-emerald-400/15 text-emerald-200' : 'bg-slate-400/10 text-slate-300'}`}>
                                        {statusLabel(player.status)}
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
            </div>

            {!hasJoined && (
                <div className="mt-4 rounded-2xl border border-dashed border-white/15 bg-white/5 p-4 text-sm text-slate-400">
                    Você ainda está assistindo. Entre na mesa para escolher um assento e participar da mão.
                </div>
            )}
        </section>
    );
}
