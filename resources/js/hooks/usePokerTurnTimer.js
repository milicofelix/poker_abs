import { useEffect, useMemo, useState } from 'react';

function secondsUntil(expiresAt) {
    if (!expiresAt) {
        return null;
    }

    const expiresAtMs = new Date(expiresAt).getTime();

    if (Number.isNaN(expiresAtMs)) {
        return null;
    }

    return Math.max(0, Math.ceil((expiresAtMs - Date.now()) / 1000));
}

export default function usePokerTurnTimer(turnTimer) {
    const [secondsRemaining, setSecondsRemaining] = useState(() => secondsUntil(turnTimer?.expiresAt));

    useEffect(() => {
        setSecondsRemaining(secondsUntil(turnTimer?.expiresAt));

        if (!turnTimer?.expiresAt) {
            return undefined;
        }

        const interval = window.setInterval(() => {
            setSecondsRemaining(secondsUntil(turnTimer.expiresAt));
        }, 1000);

        return () => window.clearInterval(interval);
    }, [turnTimer?.expiresAt]);

    return useMemo(() => {
        const total = Number(turnTimer?.secondsTotal ?? 30);
        const remaining = secondsRemaining ?? total;
        const percentage = total > 0 ? Math.max(0, Math.min(100, Math.round((remaining / total) * 100))) : 0;

        return {
            total,
            secondsRemaining: remaining,
            percentage,
            isExpired: remaining <= 0 || Boolean(turnTimer?.isExpired),
            label: turnTimer?.label ?? 'Tempo da jogada',
            expiresAt: turnTimer?.expiresAt ?? null,
        };
    }, [secondsRemaining, turnTimer]);
}
