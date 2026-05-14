import { useEffect, useRef, useState } from 'react';
import axios from 'axios';

export default function usePokerTurnTimeout(timeoutUrl, timer, onTimeoutState) {
    const processedTimerRef = useRef(null);
    const [status, setStatus] = useState({
        enabled: Boolean(timeoutUrl),
        loading: false,
        label: timeoutUrl ? 'Timeout automático pronto' : 'Timeout automático indisponível',
    });

    useEffect(() => {
        setStatus((current) => ({
            ...current,
            enabled: Boolean(timeoutUrl),
            label: timeoutUrl ? current.label : 'Timeout automático indisponível',
        }));
    }, [timeoutUrl]);

    useEffect(() => {
        if (!timeoutUrl || !timer?.isExpired) {
            return undefined;
        }

        const timerKey = timer?.expiresAt ?? `${timer?.label ?? 'timer'}-${timer?.secondsRemaining ?? 0}`;

        if (processedTimerRef.current === timerKey) {
            return undefined;
        }

        processedTimerRef.current = timerKey;
        let cancelled = false;

        async function processTimeout() {
            setStatus({
                enabled: true,
                loading: true,
                label: 'Processando timeout automático...',
            });

            try {
                const response = await axios.post(timeoutUrl);
                const nextState = response.data?.state;

                if (!cancelled && nextState) {
                    onTimeoutState(nextState);
                }

                if (!cancelled) {
                    setStatus({
                        enabled: true,
                        loading: false,
                        label: response.data?.processed
                            ? 'Timeout automático aplicado'
                            : 'Timer ainda ativo no servidor',
                    });
                }
            } catch (error) {
                if (!cancelled) {
                    setStatus({
                        enabled: true,
                        loading: false,
                        label: 'Falha ao processar timeout',
                    });
                }
            }
        }

        processTimeout();

        return () => {
            cancelled = true;
        };
    }, [timeoutUrl, timer?.isExpired, timer?.expiresAt, timer?.label, timer?.secondsRemaining, onTimeoutState]);

    return status;
}
