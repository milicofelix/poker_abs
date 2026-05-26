import { formatChipAmount } from './tableState';
import type { ActionVisualTone, PokerActionLike } from './tableActionTypes';

export function isAllInActionLabel(label: unknown) {
    const normalized = String(label ?? '').toLowerCase();
    const compact = normalized.replace(/[\s_-]+/g, '');

    return normalized === 'all' || compact.includes('allin');
}

export function actionActorRole(action: PokerActionLike) {
    const raw = String(action?.actor ?? action?.role ?? '').toLowerCase();

    if (raw === 'player' || raw === 'current' || raw === 'user') {
        return 'player';
    }

    if (raw === 'opponent' || raw === 'bot' || raw === 'adversario' || raw === 'adversário') {
        return 'opponent';
    }

    return raw || null;
}

export function actionActorLabel(action: PokerActionLike) {
    const role = actionActorRole(action);

    if (action?.nickname || action?.playerName || action?.actorLabel) {
        return action.nickname ?? action.playerName ?? action.actorLabel;
    }

    if (action?.seatNumber) {
        return `Assento ${action.seatNumber}`;
    }

    if (role === 'player') {
        return 'Você';
    }

    if (role === 'opponent') {
        return 'Adversário';
    }

    return 'Jogador';
}

export function normalizeActionLabel(action: PokerActionLike) {
    const raw = String(action?.label ?? action?.action ?? action?.type ?? '').toLowerCase();

    const labels: Record<string, string> = {
        fold: 'Fold',
        folded: 'Fold',
        check: 'Check',
        call: 'Call',
        bet: 'Bet',
        raise: 'Raise',
        all_in: 'All-in',
        allin: 'All-in',
    };

    return labels[raw] ?? (action?.label ?? action?.action ?? action?.type ?? null);
}

export function actionToneLabel(label: unknown) {
    const normalized = String(label ?? '').toLowerCase();

    if (isAllInActionLabel(label)) {
        return 'All-in';
    }

    if (normalized.includes('raise')) {
        return 'Raise';
    }

    if (normalized.includes('bet')) {
        return 'Bet';
    }

    if (normalized.includes('call')) {
        return 'Call';
    }

    if (normalized.includes('check')) {
        return 'Check';
    }

    if (normalized.includes('fold')) {
        return 'Fold';
    }

    return 'Ação';
}

export function actionVisualTone(label: unknown): ActionVisualTone {
    const tone = actionToneLabel(label);

    const tones: Record<string, ActionVisualTone> = {
        Fold: {
            seatClass: 'poker-action-fold-shade',
            badgeClass: 'border-slate-300/40 bg-slate-950/90 text-slate-100',
            effect: 'fold',
            verb: 'descartou',
            description: 'Cartas protegidas e assento escurecido.',
        },
        Check: {
            seatClass: 'poker-action-check-ripple',
            badgeClass: 'border-sky-100/60 bg-sky-300 text-sky-950',
            effect: 'check',
            verb: 'passou a ação',
            description: 'Sem aposta adicional nesta rodada.',
        },
        Call: {
            seatClass: 'poker-action-chip-flight-source',
            badgeClass: 'border-emerald-100/60 bg-emerald-300 text-emerald-950',
            effect: 'chips',
            verb: 'pagou',
            description: 'Fichas seguem para o centro da mesa.',
        },
        Bet: {
            seatClass: 'poker-action-chip-flight-source',
            badgeClass: 'border-amber-100/60 bg-amber-300 text-amber-950',
            effect: 'chips',
            verb: 'apostou',
            description: 'Aposta adicionada ao pote.',
        },
        Raise: {
            seatClass: 'poker-action-raise-impact',
            badgeClass: 'border-orange-100/70 bg-orange-300 text-orange-950',
            effect: 'chips',
            verb: 'aumentou',
            description: 'Pressão na mesa e fichas ao centro.',
        },
        'All-in': {
            seatClass: 'poker-action-allin-blast',
            badgeClass: 'border-rose-100/70 bg-rose-400 text-rose-950',
            effect: 'allin',
            verb: 'foi all-in',
            description: 'Momento decisivo da mão.',
        },
    };

    return tones[tone] ?? {
        seatClass: 'poker-action-flash',
        badgeClass: 'border-violet-100/50 bg-violet-300 text-violet-950',
        effect: 'pulse',
        verb: 'agiu',
        description: 'Ação executada na mesa.',
    };
}

export function actionToastTitle(item: PokerActionLike) {
    if (!item) {
        return 'Ação executada';
    }

    const tone = actionVisualTone(item.label);
    const amount = Number(item?.amount ?? 0);
    const amountLabel = amount > 0 ? ` ${formatChipAmount(amount)}` : '';

    return `${item.actor} ${tone.verb}${amountLabel}`;
}

export function actionStreetLabel(action: PokerActionLike, fallbackStreet: unknown) {
    const street = String(action?.street ?? action?.round ?? fallbackStreet ?? '').toLowerCase();
    const labels: Record<string, string> = {
        pre_flop: 'Pré-flop',
        preflop: 'Pré-flop',
        flop: 'Flop',
        turn: 'Turn',
        river: 'River',
        showdown: 'Showdown',
    };

    return labels[street] ?? 'Mesa';
}

export function actionImpactLabel(item: PokerActionLike) {
    if (Number(item?.amount ?? 0) > 0) {
        return `${item?.label} ${formatChipAmount(item?.amount)}`;
    }

    return item?.label ?? 'Ação';
}
