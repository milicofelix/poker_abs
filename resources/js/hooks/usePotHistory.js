import { useEffect, useRef, useState } from 'react';

export default function usePotHistory(value, options = {}) {
    const { maxItems = 3, disabled = false } = options;
    const numericValue = Number(value ?? 0);
    const previousValueRef = useRef(numericValue);
    const [items, setItems] = useState([]);

    useEffect(() => {
        const previousValue = Number(previousValueRef.current ?? 0);
        const increment = numericValue - previousValue;
        previousValueRef.current = numericValue;

        if (disabled || increment <= 0) {
            return;
        }

        const item = {
            id: `${Date.now()}-${numericValue}-${increment}`,
            amount: increment,
        };

        setItems((currentItems) => [item, ...currentItems].slice(0, maxItems));

        const timeout = window.setTimeout(() => {
            setItems((currentItems) => currentItems.filter((currentItem) => currentItem.id !== item.id));
        }, 2800);

        return () => window.clearTimeout(timeout);
    }, [numericValue, maxItems, disabled]);

    return items;
}
