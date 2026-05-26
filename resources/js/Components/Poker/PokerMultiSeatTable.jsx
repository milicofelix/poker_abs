import CardRow from './CardRow';
import {
    LatestActionToast,
    LivePotDisplay,
    PokerActionReplayRail,
} from './PokerActionDisplay';
import { PremiumActionControlPanel } from './PokerActionControls';
import { MultiSeatPlayerSpot } from './PokerSeatDisplay';
import {
    LiveTurnTimerBadge,
    MobileTableStickyStatus,
    TournamentLiveHud,
    YourTurnOverlay,
} from './PokerTableLiveStatus';
import {
    ShowdownCinematicRibbon,
    ShowdownPremiumPanel,
} from './PokerShowdownDisplay';
import PokerTableAnimationStyles from './PokerTableAnimationStyles';
import usePokerTurnTimer from '../../hooks/usePokerTurnTimer';
import { useShowdownHighlights } from '@/features/poker/hooks/useShowdownHighlights';
import { multiSeatAssistedModeLabel } from '@/features/poker/utils/tableLive';
import { multiSeatShowdownSummary } from '@/features/poker/utils/tableShowdown';
import {
    currentUserSeatNumber,
    formatChipAmount,
    multiSeatBlindSummary,
    multiSeatCurrentTurnSeat,
    multiSeatPlayers,
} from '@/features/poker/utils/tableState';

export function MultiSeatPokerTable({ state, community, playerCardsRevealed, setPlayerCardsRevealed }) {
    const turnTimer = usePokerTurnTimer(state?.turnTimer);
    const showdownHighlights = useShowdownHighlights(state);
    const players = multiSeatPlayers(state);
    const currentSeat = currentUserSeatNumber(state);
    const currentTurnSeat = multiSeatCurrentTurnSeat(state);
    const currentTurnPlayer = players.find((player) => Number(player.seatNumber ?? 0) === currentTurnSeat);
    const currentPlayer = players.find((player) => Number(player.seatNumber ?? 0) === currentSeat);
    const opponents = players.filter((player) => Number(player.seatNumber ?? 0) !== currentSeat);
    const maxPlayers = Number(state?.multiSeat?.maxPlayers ?? state?.tableCapacity?.maxPlayers ?? players.length ?? 0);
    const assistedModeLabel = multiSeatAssistedModeLabel(state, currentPlayer);
    const tableStatusLabel = state?.isFinished
        ? multiSeatShowdownSummary(state)
        : (currentTurnPlayer?.nickname ?? state?.currentTurn?.actorLabel ?? 'Aguardando ação');
    const liveFocusName = currentTurnPlayer?.nickname ?? currentTurnPlayer?.displayName ?? state?.currentTurn?.actorLabel ?? 'Aguardando ação';

    return (
        <section className="poker-table-breath relative overflow-hidden rounded-[1.4rem] border border-amber-200/20 bg-[radial-gradient(ellipse_at_center,#166534_0%,#065f46_34%,#052e2b_64%,#020617_100%)] p-2 pb-24 shadow-[0_30px_90px_rgba(0,0,0,0.55)] sm:rounded-[2rem] sm:p-4 sm:pb-4">
            <PokerTableAnimationStyles />
            <div className="pointer-events-none absolute inset-1 rounded-[1.1rem] border-[3px] border-amber-950/45 shadow-inner shadow-black/80 sm:inset-3 sm:rounded-[1.7rem] sm:border-[7px]" />
            <div className="pointer-events-none absolute inset-x-3 top-[22%] bottom-[17%] rounded-[999px] border border-amber-200/25 shadow-[inset_0_0_60px_rgba(0,0,0,0.45)] sm:inset-x-10 lg:inset-x-20" />
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,191,36,0.14),transparent_32%),linear-gradient(120deg,rgba(255,255,255,0.10),transparent_25%,transparent_75%,rgba(255,255,255,0.06))]" />

            <div className="relative z-10 grid gap-3">
                <div className="flex flex-col gap-2 rounded-2xl border border-white/10 bg-slate-950/45 p-2 shadow-xl shadow-black/35 backdrop-blur md:flex-row md:items-center md:justify-between">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-full border border-emerald-200/25 bg-emerald-300/10 px-3 py-1 text-[0.6rem] font-black uppercase tracking-[0.18em] text-emerald-100">
                            {players.length}/{maxPlayers} jogadores
                        </span>
                        <span className="rounded-full border border-emerald-200/25 bg-emerald-300/10 px-3 py-1 text-[0.6rem] font-black uppercase tracking-[0.18em] text-emerald-100">
                            Blinds {formatChipAmount(state?.smallBlind)} / {formatChipAmount(state?.bigBlind)}
                        </span>
                        <span className="rounded-full border border-amber-200/25 bg-amber-300/10 px-3 py-1 text-[0.6rem] font-black uppercase tracking-[0.16em] text-amber-100">
                            {multiSeatBlindSummary(state)}
                        </span>
                    </div>

                    <div className="min-w-0 text-left md:text-right">
                        <p className="text-[0.58rem] font-black uppercase tracking-[0.22em] text-amber-100/70">Mesa em jogo</p>
                        <strong className="block truncate text-sm font-black text-white md:max-w-[420px]" title={tableStatusLabel}>{tableStatusLabel}</strong>
                    </div>
                </div>

                {assistedModeLabel && (
                    <div className="rounded-2xl border border-amber-200/25 bg-amber-300/10 px-3 py-2 text-sm font-bold text-amber-100 shadow-xl shadow-black/30">
                        {assistedModeLabel}
                    </div>
                )}

                <TournamentLiveHud state={state} currentPlayer={currentPlayer} />

                {!state?.isFinished && currentTurnSeat !== null && (
                    <div className="grid gap-2 rounded-[1.4rem] border border-emerald-200/25 bg-emerald-300/10 p-2 shadow-2xl shadow-emerald-950/20 backdrop-blur lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                        <div className="min-w-0 px-1">
                            <p className="text-[0.58rem] font-black uppercase tracking-[0.26em] text-emerald-100/70">Live focus</p>
                            <strong className="block truncate text-lg font-black text-white" title={liveFocusName}>{liveFocusName}</strong>
                            <p className="text-xs font-semibold text-emerald-100/75">Assento {currentTurnSeat} está com a decisão da rodada.</p>
                        </div>
                        <LiveTurnTimerBadge timer={turnTimer} compact />
                    </div>
                )}

                <MobileTableStickyStatus state={state} />

                <ShowdownPremiumPanel state={state} />
                <ShowdownCinematicRibbon state={state} />
                <LatestActionToast state={state} />
                <YourTurnOverlay state={state} timer={turnTimer} />

                <div className="grid gap-3 xl:grid-cols-[minmax(0,1fr)_320px] xl:items-start">
                    <div className="relative order-2 min-h-[430px] overflow-hidden rounded-[1.5rem] border border-amber-200/20 bg-[radial-gradient(ellipse_at_center,rgba(6,95,70,0.78),rgba(2,44,34,0.84)_58%,rgba(2,6,23,0.82)_100%)] p-2 shadow-inner shadow-black/60 sm:min-h-[620px] sm:p-4 xl:order-1 xl:min-h-[670px]">
                        <div className="pointer-events-none absolute inset-x-10 top-24 bottom-28 rounded-[999px] border border-amber-200/25 shadow-[0_0_90px_rgba(0,0,0,0.35),inset_0_0_80px_rgba(0,0,0,0.35)]" />
                        <div className="pointer-events-none absolute left-1/2 top-[34%] h-[1px] w-2/3 -translate-x-1/2 bg-gradient-to-r from-transparent via-amber-100/20 to-transparent" />

                        <div className="relative z-10 grid min-h-[410px] grid-rows-[auto_1fr_auto] gap-2 sm:min-h-[590px] sm:gap-3 xl:min-h-[640px]">
                            <div className="poker-card-scroll flex gap-2 overflow-x-auto pb-1 xl:grid xl:grid-cols-3 xl:overflow-visible xl:pb-0">
                                {opponents.length === 0 && (
                                    <div className="rounded-2xl border border-white/10 bg-black/25 p-4 text-sm font-bold text-slate-200">
                                        Aguardando adversários sentados.
                                    </div>
                                )}
                                {opponents.map((player, index) => (
                                    <div key={`opponent-wrap-${player.seatNumber}`} className="min-w-[220px] sm:min-w-[270px] xl:min-w-0">
                                        <MultiSeatPlayerSpot
                                            key={`opponent-${player.seatNumber}`}
                                            player={player}
                                            state={state}
                                            currentUserSeat={currentSeat}
                                            currentTurnSeat={currentTurnSeat}
                                            playerCardsRevealed={playerCardsRevealed}
                                            onTogglePlayerCards={() => setPlayerCardsRevealed((isRevealed) => !isRevealed)}
                                            index={index}
                                            opponentsCount={opponents.length}
                                            turnTimer={turnTimer}
                                            showdownHighlights={showdownHighlights}
                                        />
                                    </div>
                                ))}
                            </div>

                            <div className="flex min-w-0 flex-col items-center justify-center gap-3 px-1 sm:px-5 xl:px-20">
                                <LivePotDisplay state={state} />

                                <div key={`board-${state?.street ?? 'mesa'}-${community.visible.length}`} className="poker-street-transition w-full max-w-3xl rounded-[2rem] border border-amber-200/25 bg-black/25 p-3 shadow-2xl shadow-black/50 backdrop-blur sm:p-5">
                                    <CardRow
                                        title="Board / Cartas comunitárias"
                                        cards={community.visible}
                                        hiddenCount={community.hiddenCount}
                                        tone="hero"
                                        dealStartIndex={4}
                                        dealFrom="dealer"
                                        highlightCards={showdownHighlights.highlightCards}
                                        highlightActive={showdownHighlights.hasHighlights}
                                    />
                                </div>

                                <PokerActionReplayRail state={state} />
                            </div>

                            <div className="mx-auto w-full max-w-5xl">
                                {currentPlayer ? (
                                    <MultiSeatPlayerSpot
                                        key={`current-${currentPlayer.seatNumber}`}
                                        player={currentPlayer}
                                        state={state}
                                        currentUserSeat={currentSeat}
                                        currentTurnSeat={currentTurnSeat}
                                        playerCardsRevealed={playerCardsRevealed}
                                        onTogglePlayerCards={() => setPlayerCardsRevealed((isRevealed) => !isRevealed)}
                                        index={8}
                                        turnTimer={turnTimer}
                                    />
                                ) : (
                                    <div className="rounded-2xl border border-amber-200/25 bg-amber-300/10 p-4 text-sm font-bold text-amber-100">
                                        Você está acompanhando a mesa como espectador/eliminado. Acompanhe as ações nos assentos e no histórico recolhido abaixo.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                    <div className="order-1 xl:order-2">
                        <PremiumActionControlPanel state={state} />
                    </div>
                </div>
            </div>
        </section>
    );
}
