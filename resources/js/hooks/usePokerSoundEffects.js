import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

const STORAGE_KEY = 'poker.soundEffects.enabled';

function readInitialPreference() {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.localStorage.getItem(STORAGE_KEY) === 'true';
}

function clampVolume(volume) {
    return Math.min(0.24, Math.max(0.04, Number(volume ?? 0.12)));
}

function createOscillatorSound(audioContext, { frequency, type = 'sine', duration = 0.11, volume = 0.1, delay = 0 }) {
    const oscillator = audioContext.createOscillator();
    const gain = audioContext.createGain();
    const startsAt = audioContext.currentTime + delay;
    const endsAt = startsAt + duration;

    oscillator.type = type;
    oscillator.frequency.setValueAtTime(frequency, startsAt);
    gain.gain.setValueAtTime(0.0001, startsAt);
    gain.gain.exponentialRampToValueAtTime(clampVolume(volume), startsAt + 0.015);
    gain.gain.exponentialRampToValueAtTime(0.0001, endsAt);

    oscillator.connect(gain);
    gain.connect(audioContext.destination);
    oscillator.start(startsAt);
    oscillator.stop(endsAt + 0.025);
}

function createChipNoise(audioContext, { duration = 0.09, volume = 0.07, delay = 0 } = {}) {
    const sampleRate = audioContext.sampleRate;
    const length = Math.max(1, Math.floor(sampleRate * duration));
    const buffer = audioContext.createBuffer(1, length, sampleRate);
    const data = buffer.getChannelData(0);

    for (let index = 0; index < length; index += 1) {
        const progress = index / length;
        data[index] = (Math.random() * 2 - 1) * (1 - progress) * 0.65;
    }

    const source = audioContext.createBufferSource();
    const gain = audioContext.createGain();
    const startsAt = audioContext.currentTime + delay;

    gain.gain.setValueAtTime(clampVolume(volume), startsAt);
    gain.gain.exponentialRampToValueAtTime(0.0001, startsAt + duration);

    source.buffer = buffer;
    source.connect(gain);
    gain.connect(audioContext.destination);
    source.start(startsAt);
    source.stop(startsAt + duration + 0.025);
}

function actionSignature(action) {
    if (!action) {
        return null;
    }

    return [
        action.action,
        action.actor,
        action.actorLabel,
        action.amount,
        action.createdAt,
        action.label,
    ].filter(Boolean).join('|');
}

function soundForAction(actionName) {
    const normalized = String(actionName ?? '').toLowerCase();

    if (normalized.includes('fold')) {
        return 'fold';
    }

    if (normalized.includes('raise') || normalized.includes('bet')) {
        return 'raise';
    }

    if (normalized.includes('call')) {
        return 'call';
    }

    if (normalized.includes('check')) {
        return 'check';
    }

    return 'action';
}

export default function usePokerSoundEffects(state) {
    const [enabled, setEnabled] = useState(readInitialPreference);
    const audioContextRef = useRef(null);
    const previousRef = useRef({
        action: actionSignature(state?.lastAction),
        finished: Boolean(state?.isFinished),
        pot: Number(state?.pot ?? 0),
        street: state?.street ?? null,
    });

    const getAudioContext = useCallback(() => {
        if (typeof window === 'undefined') {
            return null;
        }

        if (!audioContextRef.current) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;

            if (!AudioContextClass) {
                return null;
            }

            audioContextRef.current = new AudioContextClass();
        }

        if (audioContextRef.current.state === 'suspended') {
            audioContextRef.current.resume();
        }

        return audioContextRef.current;
    }, []);

    const play = useCallback((soundName) => {
        if (!enabled) {
            return;
        }

        const audioContext = getAudioContext();

        if (!audioContext) {
            return;
        }

        if (soundName === 'winner') {
            createOscillatorSound(audioContext, { frequency: 523.25, duration: 0.12, volume: 0.1 });
            createOscillatorSound(audioContext, { frequency: 659.25, duration: 0.13, volume: 0.1, delay: 0.1 });
            createOscillatorSound(audioContext, { frequency: 783.99, duration: 0.18, volume: 0.11, delay: 0.2 });
            return;
        }

        if (soundName === 'street') {
            createOscillatorSound(audioContext, { frequency: 392, type: 'triangle', duration: 0.12, volume: 0.08 });
            createChipNoise(audioContext, { duration: 0.07, volume: 0.045, delay: 0.08 });
            return;
        }

        if (soundName === 'raise') {
            createChipNoise(audioContext, { duration: 0.11, volume: 0.08 });
            createChipNoise(audioContext, { duration: 0.08, volume: 0.055, delay: 0.055 });
            return;
        }

        if (soundName === 'call') {
            createChipNoise(audioContext, { duration: 0.08, volume: 0.06 });
            createOscillatorSound(audioContext, { frequency: 330, type: 'triangle', duration: 0.07, volume: 0.045, delay: 0.035 });
            return;
        }

        if (soundName === 'fold') {
            createOscillatorSound(audioContext, { frequency: 196, type: 'sawtooth', duration: 0.1, volume: 0.045 });
            return;
        }

        if (soundName === 'check') {
            createOscillatorSound(audioContext, { frequency: 440, type: 'sine', duration: 0.055, volume: 0.04 });
            return;
        }

        createOscillatorSound(audioContext, { frequency: 294, type: 'triangle', duration: 0.08, volume: 0.045 });
    }, [enabled, getAudioContext]);

    const toggleEnabled = useCallback(() => {
        setEnabled((current) => {
            const next = !current;

            if (typeof window !== 'undefined') {
                window.localStorage.setItem(STORAGE_KEY, String(next));
            }

            if (next) {
                setTimeout(() => play('check'), 0);
            }

            return next;
        });
    }, [play]);

    useEffect(() => {
        if (!enabled) {
            previousRef.current = {
                action: actionSignature(state?.lastAction),
                finished: Boolean(state?.isFinished),
                pot: Number(state?.pot ?? 0),
                street: state?.street ?? null,
            };
            return;
        }

        const previous = previousRef.current;
        const currentAction = actionSignature(state?.lastAction);
        const currentFinished = Boolean(state?.isFinished);
        const currentPot = Number(state?.pot ?? 0);
        const currentStreet = state?.street ?? null;

        if (currentAction && currentAction !== previous.action) {
            play(soundForAction(state?.lastAction?.action ?? state?.lastAction?.type ?? state?.lastAction?.label));
        } else if (currentStreet && previous.street && currentStreet !== previous.street) {
            play('street');
        } else if (currentPot > previous.pot) {
            play('call');
        }

        if (currentFinished && !previous.finished) {
            play('winner');
        }

        previousRef.current = {
            action: currentAction,
            finished: currentFinished,
            pot: currentPot,
            street: currentStreet,
        };
    }, [enabled, play, state?.isFinished, state?.lastAction, state?.pot, state?.street]);

    return useMemo(() => ({
        enabled,
        toggleEnabled,
        play,
    }), [enabled, play, toggleEnabled]);
}
