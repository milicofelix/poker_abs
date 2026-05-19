import React from 'react';

function safeNumber(value, fallback = 0) {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
}

function previewStatusLabel(preview = null) {
    if (!preview || Object.keys(preview).length === 0) {
        return 'aguardando';
    }

    if (preview.enabledInMainEngine) {
        return 'ativo';
    }

    return 'preparado';
}

function PreviewCard({ title, phase, status, children }) {
    return (
        <div className="rounded-2xl border border-white/10 bg-black/20 px-3 py-2 shadow-inner shadow-black/25">
            <div className="flex items-start justify-between gap-2">
                <div>
                    <p className="text-[0.55rem] font-black uppercase tracking-[0.18em] text-fuchsia-200">{phase ?? '10.x'}</p>
                    <h3 className="mt-1 text-xs font-black uppercase tracking-[0.14em] text-white">{title}</h3>
                </div>
                <span className="rounded-full border border-fuchsia-100/20 bg-fuchsia-200/10 px-2 py-0.5 text-[0.55rem] font-black uppercase tracking-[0.12em] text-fuchsia-100">
                    {status}
                </span>
            </div>
            <div className="mt-2 text-[0.68rem] font-semibold leading-relaxed text-fuchsia-50/75 sm:text-xs">
                {children}
            </div>
        </div>
    );
}

export default function PokerMultiSeatIntegrationPanel({ contracts = null, players = [], maxPlayers = 2 }) {
    const multiSeat = contracts?.multiSeat ?? null;
    const compatibility = contracts?.compatibility ?? null;

    if (!multiSeat || safeNumber(multiSeat.declaredMaxPlayers, maxPlayers) <= 2) {
        return null;
    }

    const dealPreview = multiSeat.dealPreview ?? {};
    const turnPreview = multiSeat.turnCyclePreview ?? {};
    const actionPreview = multiSeat.actionPreview ?? {};
    const bettingPreview = multiSeat.bettingRoundPreview ?? {};
    const showdownPreview = multiSeat.showdownPreview ?? {};
    const seatedPlayers = players.filter((player) => player?.seatNumber).length;
    const declaredMaxPlayers = safeNumber(multiSeat.declaredMaxPlayers, maxPlayers);

    return (
        <section className="rounded-[1.5rem] border border-fuchsia-200/25 bg-gradient-to-br from-fuchsia-300/15 via-purple-300/10 to-slate-950/80 p-3 text-fuchsia-50 shadow-xl shadow-black/35 backdrop-blur sm:p-4">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p className="text-[0.62rem] font-black uppercase tracking-[0.24em] text-fuchsia-200">FASE 10.8</p>
                    <h2 className="mt-1 text-base font-black text-white sm:text-lg">Integração visual 3+ jogadores</h2>
                    <p className="mt-1 text-xs font-semibold text-fuchsia-50/80 sm:text-sm">
                        A mesa já mostra os contratos multi-seat preparados, mas o motor oficial continua em heads-up até a ativação final.
                    </p>
                </div>

                <div className="grid grid-cols-3 gap-2 text-xs font-black lg:min-w-[23rem]">
                    <div className="rounded-2xl border border-white/10 bg-black/20 px-3 py-2">
                        <span className="block text-[0.55rem] uppercase tracking-[0.16em] text-fuchsia-200">Assentos</span>
                        <strong className="mt-1 block text-white">{seatedPlayers}/{declaredMaxPlayers}</strong>
                    </div>
                    <div className="rounded-2xl border border-white/10 bg-black/20 px-3 py-2">
                        <span className="block text-[0.55rem] uppercase tracking-[0.16em] text-fuchsia-200">Motor</span>
                        <strong className="mt-1 block text-white">{contracts?.active ?? 'heads_up'}</strong>
                    </div>
                    <div className="rounded-2xl border border-amber-200/20 bg-amber-300/10 px-3 py-2 text-amber-50">
                        <span className="block text-[0.55rem] uppercase tracking-[0.16em] text-amber-200">3+ ativo?</span>
                        <strong className="mt-1 block">{compatibility?.multiSeatEnabled ? 'sim' : 'não'}</strong>
                    </div>
                </div>
            </div>

            <div className="mt-3 grid gap-2 md:grid-cols-2 2xl:grid-cols-3">
                <PreviewCard title="Distribuição" phase={dealPreview.phase ?? '10.3'} status={previewStatusLabel(dealPreview)}>
                    {safeNumber(dealPreview.activeSeatedPlayers)} jogador(es) sentado(s) mapeados para mãos futuras.
                </PreviewCard>

                <PreviewCard title="Turnos" phase={turnPreview.phase ?? '10.4'} status={previewStatusLabel(turnPreview)}>
                    Dealer {turnPreview.dealerSeat ?? '-'}, small blind {turnPreview.smallBlindSeat ?? '-'} e big blind {turnPreview.bigBlindSeat ?? '-'}.
                </PreviewCard>

                <PreviewCard title="Ações" phase={actionPreview.phase ?? '10.5'} status={previewStatusLabel(actionPreview)}>
                    Fold, check, call e raise já possuem contrato de preparação para múltiplos assentos.
                </PreviewCard>

                <PreviewCard title="Apostas" phase={bettingPreview.phase ?? '10.6'} status={previewStatusLabel(bettingPreview)}>
                    {safeNumber(bettingPreview.activeContenders)} competidor(es) ativos; fechamento de street ainda bloqueado.
                </PreviewCard>

                <PreviewCard title="Showdown" phase={showdownPreview.phase ?? '10.7'} status={previewStatusLabel(showdownPreview)}>
                    {safeNumber(showdownPreview.activeContenders)} competidor(es) no preview; resolução final 3+ ainda não ativada.
                </PreviewCard>
            </div>
        </section>
    );
}
