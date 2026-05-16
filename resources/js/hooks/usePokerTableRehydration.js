import { useCallback, useEffect, useRef, useState } from 'react';
import axios from 'axios';

function syncVersionOf(state) {
    return Number(state?.persistence?.syncVersion ?? 0);
}

export default function usePokerTableRehydration(stateUrl, onStateRehydrated) {
    const [status, setStatus] = useState({
        enabled: Boolean(stateUrl),
        loading: false,
        label: stateUrl ? 'Reidratação pronta' : 'Reidratação indisponível',
    });

    const latestSyncVersionRef = useRef(0);
    const inFlightRef = useRef(false);
    const queuedRef = useRef(false);

    const rehydrate = useCallback(async () => {
        if (!stateUrl) {
            setStatus({
                enabled: false,
                loading: false,
                label: 'Reidratação indisponível',
            });

            return null;
        }

        if (inFlightRef.current) {
            queuedRef.current = true;

            return null;
        }

        inFlightRef.current = true;

        setStatus({
            enabled: true,
            loading: true,
            label: 'Atualizando estado da mesa...',
        });

        try {
            const response = await axios.get(stateUrl);
            const nextState = response.data?.state;

            if (!nextState) {
                setStatus({
                    enabled: true,
                    loading: false,
                    label: 'Estado da mesa não encontrado',
                });

                return null;
            }

            const nextSyncVersion = syncVersionOf(nextState);

            if (nextSyncVersion >= latestSyncVersionRef.current) {
                latestSyncVersionRef.current = nextSyncVersion;
                onStateRehydrated(nextState, response.data);
            }

            setStatus({
                enabled: true,
                loading: false,
                label: 'Estado sincronizado',
            });

            return nextState;
        } catch (error) {
            setStatus({
                enabled: true,
                loading: false,
                label: 'Falha ao atualizar estado da mesa',
            });

            return null;
        } finally {
            inFlightRef.current = false;

            if (queuedRef.current) {
                queuedRef.current = false;
                window.setTimeout(() => {
                    rehydrate();
                }, 150);
            }
        }
    }, [stateUrl, onStateRehydrated]);

    useEffect(() => {
        rehydrate();
    }, [rehydrate]);

    useEffect(() => {
        if (!stateUrl) {
            return undefined;
        }

        const handleFocus = () => {
            rehydrate();
        };

        const handleVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                rehydrate();
            }
        };

        const heartbeat = window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                rehydrate();
            }
        }, 15000);

        window.addEventListener('focus', handleFocus);
        window.addEventListener('online', handleFocus);
        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            window.clearInterval(heartbeat);
            window.removeEventListener('focus', handleFocus);
            window.removeEventListener('online', handleFocus);
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
    }, [stateUrl, rehydrate]);

    return {
        ...status,
        rehydrate,
    };
}
