import CardRow from './CardRow';
import {
    LatestActionToast,
    LivePotDisplay,
    PokerActionReplayRail,
} from './PokerActionDisplay';
import {
    HeadsUpBestHandPanel,
    HeadsUpOpponentPanel,
    HeadsUpPlayerHandPanel,
    HeadsUpTurnStatusPanel,
} from './PokerHeadsUpPanels';
import { YourTurnOverlay } from './PokerTableLiveStatus';
import { ShowdownCinematicRibbon } from './PokerShowdownDisplay';
import PokerTableAnimationStyles from './PokerTableAnimationStyles';
import usePokerTurnTimer from '../../hooks/usePokerTurnTimer';
import { useShowdownHighlights } from '@/features/poker/hooks/useShowdownHighlights';
import {
    dealerAnimationLabel,
} from '@/features/poker/utils/tableState';

export function PokerHeadsUpTable({ state, community, playerCardsRevealed, setPlayerCardsRevealed }) {
    const showdownHighlights = useShowdownHighlights(state);
    const turnTimer = usePokerTurnTimer(state?.turnTimer);
    const isBotVsBotSimulation = Boolean(state.botVsBotSimulation);
    const hasPlayerCards = (state.playerCards ?? []).length > 0;
    const shouldRevealPlayerCards = isBotVsBotSimulation || state.isFinished || playerCardsRevealed;
    const shouldRevealOpponentCards = isBotVsBotSimulation || state.isFinished;
    const playerBestHandVisible = shouldRevealPlayerCards && state.bestHand?.name;

    return (
        <section className="poker-table-breath relative overflow-hidden rounded-[1.1rem] border border-amber-200/20 bg-[radial-gradient(circle_at_center,#166534_0%,#065f46_38%,#052e2b_68%,#020617_100%)] p-1.5 pb-24 shadow-[0_30px_90px_rgba(0,0,0,0.55)] sm:rounded-[2rem] sm:p-4 sm:pb-4">
            <PokerTableAnimationStyles />
            <div className="pointer-events-none absolute inset-1 rounded-[1rem] border-[3px] border-amber-950/45 shadow-inner shadow-black/80 sm:inset-3 sm:rounded-[1.6rem] sm:border-[7px]" />
            <div className="pointer-events-none absolute inset-3 rounded-[0.9rem] border border-amber-200/20 sm:inset-6 sm:rounded-[1.35rem]" />
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,191,36,0.14),transparent_34%),linear-gradient(120deg,rgba(255,255,255,0.10),transparent_25%,transparent_75%,rgba(255,255,255,0.06))]" />
            <div className="poker-dealer-shoe pointer-events-none absolute left-1/2 top-3 z-20 hidden -translate-x-1/2 items-center gap-2 rounded-full border border-amber-200/30 bg-black/55 px-3 py-1.5 text-[0.58rem] font-black uppercase tracking-[0.22em] text-amber-100 shadow-2xl shadow-black/45 sm:flex">
                <span className="h-2 w-2 rounded-full bg-amber-300 shadow-[0_0_12px_rgba(252,211,77,0.9)]" />
                {dealerAnimationLabel(state)}
            </div>

            <div className="relative z-10 grid min-h-[340px] gap-1 sm:gap-3 md:min-h-[410px] lg:min-h-[500px] lg:grid-rows-[auto_1fr_auto]">
                <LatestActionToast state={state} />
                <YourTurnOverlay state={state} timer={turnTimer} />
                <ShowdownCinematicRibbon state={state} />

                <div className="grid min-w-0 gap-1.5 sm:gap-3 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-start">
                    <HeadsUpOpponentPanel
                        state={state}
                        shouldRevealOpponentCards={shouldRevealOpponentCards}
                        showdownHighlights={showdownHighlights}
                    />

                    <HeadsUpTurnStatusPanel state={state} />
                </div>

                <div className="flex min-w-0 items-center justify-center">
                    <div className="w-full min-w-0 max-w-2xl rounded-xl border border-amber-200/25 bg-black/25 p-1.5 shadow-2xl shadow-black/50 backdrop-blur sm:rounded-2xl sm:p-3">
                        <LivePotDisplay state={state} compact />

                        <div key={`board-single-${state?.street ?? 'mesa'}-${community.visible.length}`} className="poker-street-transition">
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

                        <PokerActionReplayRail state={state} compact />
                    </div>
                </div>

                <div className="grid min-w-0 gap-1.5 sm:gap-3 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-end">
                    <HeadsUpPlayerHandPanel
                        state={state}
                        hasPlayerCards={hasPlayerCards}
                        isBotVsBotSimulation={isBotVsBotSimulation}
                        shouldRevealPlayerCards={shouldRevealPlayerCards}
                        setPlayerCardsRevealed={setPlayerCardsRevealed}
                        showdownHighlights={showdownHighlights}
                    />

                    <HeadsUpBestHandPanel
                        state={state}
                        playerBestHandVisible={playerBestHandVisible}
                        shouldRevealPlayerCards={shouldRevealPlayerCards}
                    />
                </div>
            </div>
        </section>
    );
}
