import { useEffect, useRef, useState } from 'react';

export default function useAnimatedCounter(value, options = {}) {
    const { duration = 520, disabled = false } = options;
    const numericValue = Number(value ?? 0);
    const [displayValue, setDisplayValue] = useState(numericValue);
    const frameRef = useRef(null);
    const previousValueRef = useRef(numericValue);

    useEffect(() => {
        const previousValue = Number(previousValueRef.current ?? 0);

        if (disabled || previousValue === numericValue || typeof window === 'undefined') {
            previousValueRef.current = numericValue;
            setDisplayValue(numericValue);
            return undefined;
        }

        const startedAt = window.performance.now();
        const delta = numericValue - previousValue;

        const tick = (now) => {
            const elapsed = now - startedAt;
            const progress = Math.min(1, elapsed / duration);
            const eased = 1 - Math.pow(1 - progress, 3);

            setDisplayValue(Math.round(previousValue + (delta * eased)));

            if (progress < 1) {
                frameRef.current = window.requestAnimationFrame(tick);
                return;
            }

            previousValueRef.current = numericValue;
            setDisplayValue(numericValue);
        };

        frameRef.current = window.requestAnimationFrame(tick);

        return () => {
            if (frameRef.current) {
                window.cancelAnimationFrame(frameRef.current);
            }
        };
    }, [numericValue, duration, disabled]);

    return displayValue;
}
