import { useEffect, useRef, useState } from 'react';
import axios from 'axios';

function millisecondsUntil(expiresAt) {
    if (!expiresAt) {
        return 0;
    }

    const expiresAtMs = new Date(expiresAt).getTime();

    if (Number.isNaN(expiresAtMs)) {
        return 0;
    }

    return Math.max(0, expiresAtMs - Date.now());
}

function timeoutKey(timer, turnKey = null) {
    const expiresAt = timer?.expiresAt ?? '';
    const startedAt = timer?.startedAt ?? '';
    const actor = timer?.label ?? '';

    return [turnKey ?? '', startedAt, expiresAt, actor].join('|');
}

export default function usePokerTurnTimeout(timeoutUrl, timer, onTimeoutState, options = {}) {
    const { autoProcessBotTurns = false, turnKey = null } = options;
    const processedTimerRef = useRef(null);
    const skippedStaleHumanTimerRef = useRef(null);
    const inFlightRef = useRef(false);
    const mountedRef = useRef(true);
    const [status, setStatus] = useState({
        enabled: Boolean(timeoutUrl),
        loading: false,
        label: timeoutUrl ? 'Timeout automático pronto' : 'Timeout automático indisponível',
    });

    useEffect(() => {
        mountedRef.current = true;

        return () => {
            mountedRef.current = false;
        };
    }, []);

    useEffect(() => {
        setStatus((current) => ({
            ...current,
            enabled: Boolean(timeoutUrl),
            label: timeoutUrl ? current.label : 'Timeout automático indisponível',
        }));
    }, [timeoutUrl]);

    useEffect(() => {
        if (!timeoutUrl || !timer?.expiresAt) {
            return undefined;
        }

        const currentTimerKey = timeoutKey(timer, turnKey);

        if (!currentTimerKey || processedTimerRef.current === currentTimerKey) {
            return undefined;
        }

        const delayUntilExpiration = millisecondsUntil(timer.expiresAt);

        const humanTimerIsAlreadyExpired = delayUntilExpiration <= 0 || Boolean(timer?.isExpired);

        if (!autoProcessBotTurns && humanTimerIsAlreadyExpired && skippedStaleHumanTimerRef.current !== currentTimerKey) {
            skippedStaleHumanTimerRef.current = currentTimerKey;

            setStatus({
                enabled: true,
                loading: false,
                label: 'Timer humano expirado no servidor; aguardando ação manual.',
            });

            return undefined;
        }

        let timeoutId = null;

        async function processTimeout() {
            if (inFlightRef.current || processedTimerRef.current === currentTimerKey) {
                return;
            }

            inFlightRef.current = true;
            processedTimerRef.current = currentTimerKey;

            if (mountedRef.current) {
                setStatus({
                    enabled: true,
                    loading: true,
                    label: autoProcessBotTurns
                        ? 'Processando jogada automática dos bots...'
                        : 'Processando timeout automático...',
                });
            }

            try {
                const response = await axios.post(timeoutUrl);
                const nextState = response.data?.state;

                if (mountedRef.current && nextState) {
                    onTimeoutState(nextState);
                }

                if (mountedRef.current) {
                    setStatus({
                        enabled: true,
                        loading: false,
                        label: response.data?.processed
                            ? (response.data?.action === 'bot'
                                ? 'Jogada do bot processada'
                                : 'Timeout automático aplicado')
                            : 'Timer ainda ativo no servidor',
                    });
                }
            } catch (error) {
                const nextState = error?.response?.data?.state;
                const statusCode = error?.response?.status;

                if (mountedRef.current && nextState) {
                    onTimeoutState(nextState);
                }

                if (mountedRef.current) {
                    setStatus({
                        enabled: true,
                        loading: false,
                        label: statusCode === 404
                            ? 'Timeout automático sem mão ativa'
                            : (error?.response?.data?.message ?? 'Falha ao processar timeout'),
                    });
                }
            } finally {
                inFlightRef.current = false;
            }
        }

        const delay = autoProcessBotTurns
            ? 2500
            : delayUntilExpiration + 350;

        timeoutId = window.setTimeout(processTimeout, delay);

        return () => {
            if (timeoutId) {
                window.clearTimeout(timeoutId);
            }
        };
    }, [
        timeoutUrl,
        timer?.expiresAt,
        autoProcessBotTurns,
        onTimeoutState,
        turnKey,
    ]);

    return status;
}
