import { formatChipAmount } from './tableState';
import {
    actionActorLabel,
    actionActorRole,
    actionImpactLabel,
    actionStreetLabel,
    actionToneLabel,
    normalizeActionLabel,
} from './tableActionLabels';
import type { PokerStateLike } from './tableActionTypes';

export function actionTimelineItems(state: PokerStateLike) {
    const history = Array.isArray(state?.actionHistory) ? state.actionHistory : [];

    return [...history]
        .slice(-5)
        .reverse()
        .map((action, index) => ({
            key: `${action?.seatNumber ?? action?.tablePlayerId ?? actionActorRole(action) ?? 'action'}-${action?.action ?? action?.type ?? index}-${index}`,
            seatNumber: action?.seatNumber,
            actorRole: actionActorRole(action),
            actor: actionActorLabel(action),
            label: normalizeActionLabel(action) ?? 'Ação',
            amount: Number(action?.amount ?? 0),
        }));
}

export function latestActionItem(state: PokerStateLike) {
    return actionTimelineItems(state)[0] ?? null;
}

export function latestActionAnimationKey(state: PokerStateLike) {
    const latest = latestActionItem(state);

    if (!latest) {
        return `street-${state?.street ?? 'waiting'}-${Number(state?.pot ?? 0)}`;
    }

    return `${latest.key}-${state?.street ?? 'mesa'}-${Number(state?.pot ?? 0)}`;
}

export function isLatestSeatAction(state: PokerStateLike, seatNumber: unknown) {
    const latest = latestActionItem(state);

    return latest && Number(latest.seatNumber ?? 0) === Number(seatNumber ?? 0);
}

export function isLatestLegacyAction(state: PokerStateLike, actorRole: string) {
    const latest = latestActionItem(state);

    return latest && latest.actorRole === actorRole && !state?.isFinished;
}

export function latestLegacyActionItem(state: PokerStateLike, actorRole: string) {
    return isLatestLegacyAction(state, actorRole) ? latestActionItem(state) : null;
}

export function shouldPulsePot(state: PokerStateLike) {
    const latest = latestActionItem(state);

    return Boolean(latest && Number(latest.amount ?? 0) > 0);
}

export function actionTimelineDetailedItems(state: PokerStateLike) {
    const history = Array.isArray(state?.actionHistory) ? state.actionHistory : [];

    return [...history]
        .slice(-7)
        .reverse()
        .map((action, index) => {
            const label = normalizeActionLabel(action) ?? 'Ação';

            return {
                key: `${action?.id ?? action?.seatNumber ?? action?.tablePlayerId ?? actionActorRole(action) ?? 'action'}-${action?.action ?? action?.type ?? label}-${index}`,
                seatNumber: action?.seatNumber,
                actorRole: actionActorRole(action),
                actor: actionActorLabel(action),
                label,
                tone: actionToneLabel(label),
                street: actionStreetLabel(action, state?.street),
                amount: Number(action?.amount ?? 0),
            };
        });
}

export function actionFlowSummary(state: PokerStateLike) {
    const actions = actionTimelineDetailedItems(state);
    const latest = actions[0];

    if (!latest) {
        return {
            title: 'Mesa aguardando primeira ação',
            description: state?.isFinished ? 'Mão encerrada sem novas ações registradas.' : 'Assim que alguém agir, o fluxo aparece aqui.',
        };
    }

    return {
        title: `${latest.actor} · ${actionImpactLabel(latest)}`,
        description: `${latest.street} · ${state?.isFinished ? 'mão finalizada' : 'mesa em andamento'}`,
    };
}

export function latestTableActionLabel(state: PokerStateLike) {
    const [latest] = actionTimelineItems(state);

    if (!latest) {
        return 'Aguardando ação';
    }

    return latest.amount > 0
        ? `${latest.actor}: ${latest.label} ${formatChipAmount(latest.amount)}`
        : `${latest.actor}: ${latest.label}`;
}
