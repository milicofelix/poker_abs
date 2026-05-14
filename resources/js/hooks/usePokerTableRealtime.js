import { useEffect, useMemo, useState } from 'react';

export default function usePokerTableRealtime(tableId, onStateUpdated) {
    const [status, setStatus] = useState(() => ({
        enabled: Boolean(window.Echo && tableId),
        label: window.Echo && tableId ? 'Tempo real ativo' : 'Tempo real indisponível',
    }));

    const channelName = useMemo(() => {
        if (!tableId) {
            return null;
        }

        return `poker.tables.${tableId}`;
    }, [tableId]);

    useEffect(() => {
        if (!window.Echo || !channelName) {
            setStatus({
                enabled: false,
                label: 'Tempo real indisponível',
            });

            return undefined;
        }

        setStatus({
            enabled: true,
            label: 'Tempo real ativo',
        });

        const channel = window.Echo.channel(channelName);

        channel.listen('.poker.table.state.updated', (event) => {
            if (!event?.state) {
                return;
            }

            onStateUpdated(event.state);
        });

        return () => {
            window.Echo.leave(channelName);
        };
    }, [channelName, onStateUpdated]);

    return status;
}
