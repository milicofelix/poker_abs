import CardRow from './CardRow';
import { ActionEffectOverlay } from './PokerActionDisplay';
import {
    actionVisualTone,
    isLatestLegacyAction,
    latestActionItem,
    latestLegacyActionItem,
} from '@/features/poker/utils/tableActions';
import {
    currentPlayerTitle,
    currentTurnLabel,
    currentTurnMessage,
    hiddenPlayerHandDescription,
    hiddenPlayerHandTitle,
    opponentCardsTitle,
    seatBadgeClasses,
    seatFrameClasses,
    winnerBadgeLabel,
} from '@/features/poker/utils/tableState';

export function HeadsUpOpponentPanel({ state, shouldRevealOpponentCards, showdownHighlights }) {
    return (
        <div className={[
            'relative overflow-visible rounded-lg border p-1.5 transition duration-300 sm:rounded-2xl sm:p-3',
            seatFrameClasses(state, 'opponent'),
            isLatestLegacyAction(state, 'opponent') ? `poker-action-flash ${actionVisualTone(latestActionItem(state)?.label).seatClass}` : '',
        ].join(' ')}>
            <ActionEffectOverlay item={latestLegacyActionItem(state, 'opponent')} />

            {winnerBadgeLabel(state, 'opponent') && (
                <span className={[
                    'absolute right-2 top-2 rounded-full border px-2 py-0.5 text-[0.6rem] font-black uppercase tracking-[0.22em]',
                    seatBadgeClasses(state, 'opponent'),
                ].join(' ')}>
                    {winnerBadgeLabel(state, 'opponent')}
                </span>
            )}

            {shouldRevealOpponentCards && state.opponentCards ? (
                <CardRow
                    title={opponentCardsTitle(state)}
                    cards={state.opponentCards ?? []}
                    align="left"
                    dealStartIndex={2}
                    dealFrom="left"
                    highlightCards={showdownHighlights.highlightCards}
                    highlightActive={showdownHighlights.hasHighlights}
                />
            ) : (
                <CardRow
                    title="Adversário"
                    hiddenCount={2}
                    align="left"
                    dealStartIndex={1}
                    dealFrom="left"
                />
            )}

            {shouldRevealOpponentCards && state.opponentBestHand?.name && (
                <p className="mt-2 rounded-xl border border-white/10 bg-white/10 px-2 py-1.5 text-center text-[0.7rem] font-bold text-slate-100">
                    Melhor mão: {state.opponentBestHand.name}
                </p>
            )}
        </div>
    );
}

export function HeadsUpTurnStatusPanel({ state }) {
    return (
        <div className="poker-status-slide poker-turn-glow rounded-lg border border-amber-200/30 bg-black/35 p-1.5 text-center shadow-2xl shadow-black/40 sm:rounded-2xl sm:p-3">
            <p className="text-[0.58rem] font-black uppercase tracking-[0.18em] text-amber-200 sm:text-xs sm:tracking-[0.28em]">Turno atual</p>
            <strong className="mt-0.5 block text-sm font-black text-white sm:mt-1 sm:text-base">{currentTurnLabel(state)}</strong>
            <span className="mt-0.5 block truncate text-[0.62rem] font-semibold text-emerald-100/80 sm:text-[0.68rem]">{currentTurnMessage(state)}</span>
        </div>
    );
}

export function HeadsUpPlayerHandPanel({
    state,
    hasPlayerCards,
    isBotVsBotSimulation,
    shouldRevealPlayerCards,
    setPlayerCardsRevealed,
    showdownHighlights,
}) {
    return (
        <button
            type="button"
            disabled={!hasPlayerCards || state.isFinished || isBotVsBotSimulation}
            onClick={() => hasPlayerCards && !state.isFinished && !isBotVsBotSimulation && setPlayerCardsRevealed((isRevealed) => !isRevealed)}
            className={`group relative block min-w-0 overflow-visible rounded-2xl text-left transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-200/80 ${isLatestLegacyAction(state, 'player') ? `poker-action-flash ${actionVisualTone(latestActionItem(state)?.label).seatClass}` : ''} ${hasPlayerCards && !state.isFinished && !isBotVsBotSimulation ? 'cursor-pointer hover:-translate-y-0.5 hover:shadow-2xl hover:shadow-amber-950/25' : 'cursor-default'}`}
            aria-label={hiddenPlayerHandTitle(shouldRevealPlayerCards)}
        >
            <ActionEffectOverlay item={latestLegacyActionItem(state, 'player')} />

            <CardRow
                title={currentPlayerTitle(state)}
                cards={shouldRevealPlayerCards ? (state.playerCards ?? []) : []}
                hiddenCount={shouldRevealPlayerCards ? 0 : (state.playerCards ?? []).length}
                dealStartIndex={0}
                dealFrom="bottom"
                highlightCards={showdownHighlights.highlightCards}
                highlightActive={showdownHighlights.hasHighlights}
            />

            {hasPlayerCards && (
                <div className="mt-1.5 rounded-xl border border-amber-200/20 bg-black/30 px-2 py-1.5 text-center shadow-inner shadow-black/30 sm:mt-2">
                    <p className="text-[0.58rem] font-black uppercase tracking-[0.18em] text-amber-100/90 sm:text-[0.65rem] sm:tracking-[0.24em]">
                        {isBotVsBotSimulation ? 'Modo espectador — cartas abertas' : hiddenPlayerHandTitle(shouldRevealPlayerCards)}
                    </p>
                    <p className="mt-0.5 text-[0.62rem] font-semibold text-emerald-100/75 sm:text-[0.7rem]">
                        {isBotVsBotSimulation
                            ? 'Simulação Bot vs Bot: as duas mãos ficam visíveis para acompanhamento.'
                            : hiddenPlayerHandDescription(shouldRevealPlayerCards, state.isFinished)}
                    </p>
                </div>
            )}
        </button>
    );
}

export function HeadsUpBestHandPanel({ state, playerBestHandVisible, shouldRevealPlayerCards }) {
    return (
        <div className={[
            'relative overflow-hidden rounded-lg border p-1.5 text-center transition duration-300 sm:rounded-2xl sm:p-3',
            seatFrameClasses(state, 'player'),
        ].join(' ')}>
            {winnerBadgeLabel(state, 'player') && (
                <span className={[
                    'mx-auto mb-2 inline-flex rounded-full border px-2 py-0.5 text-[0.6rem] font-black uppercase tracking-[0.22em]',
                    seatBadgeClasses(state, 'player'),
                ].join(' ')}>
                    {winnerBadgeLabel(state, 'player')}
                </span>
            )}

            <p className="text-[0.58rem] font-black uppercase tracking-[0.18em] text-emerald-100 sm:text-xs sm:tracking-[0.28em]">Melhor mão</p>
            <strong className="mt-0.5 block text-sm font-black text-white sm:mt-1 sm:text-base">{playerBestHandVisible ? state.bestHand.name : (shouldRevealPlayerCards ? 'Aguardando' : 'Cartas ocultas')}</strong>
            <p className="mt-0.5 text-[0.62rem] text-slate-300 sm:mt-1 sm:text-[0.7rem]">Stack: {state.playerStack}</p>
        </div>
    );
}
