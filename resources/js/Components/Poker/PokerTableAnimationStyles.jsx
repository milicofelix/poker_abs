export default function PokerTableAnimationStyles() {
    return (
        <style>{`
            @keyframes pokerActionFlash {
                0% { transform: translateY(0) scale(1); box-shadow: 0 0 0 rgba(16,185,129,0); }
                28% { transform: translateY(-4px) scale(1.015); box-shadow: 0 0 34px rgba(16,185,129,0.34); }
                100% { transform: translateY(0) scale(1); box-shadow: 0 0 0 rgba(16,185,129,0); }
            }

            @keyframes pokerActionPillPop {
                0% { transform: scale(0.88); filter: brightness(0.92); }
                45% { transform: scale(1.08); filter: brightness(1.18); }
                100% { transform: scale(1); filter: brightness(1); }
            }

            @keyframes pokerPotReceive {
                0% { transform: scale(1); }
                35% { transform: scale(1.055); box-shadow: 0 0 42px rgba(251,191,36,0.32); }
                100% { transform: scale(1); }
            }

            @keyframes pokerStreetFade {
                0% { opacity: 0.68; transform: translateY(7px) scale(0.985); }
                100% { opacity: 1; transform: translateY(0) scale(1); }
            }

            @keyframes pokerWinnerGlow {
                0%, 100% { box-shadow: 0 0 28px rgba(251,191,36,0.18); }
                50% { box-shadow: 0 0 54px rgba(251,191,36,0.36); }
            }

            @keyframes pokerTurnLiveGlow {
                0%, 100% { box-shadow: 0 0 22px rgba(16,185,129,0.20), inset 0 0 18px rgba(16,185,129,0.08); }
                50% { box-shadow: 0 0 58px rgba(16,185,129,0.46), inset 0 0 28px rgba(16,185,129,0.14); }
            }

            @keyframes pokerTurnBorderSweep {
                0% { transform: rotate(0deg); opacity: .42; }
                50% { opacity: .95; }
                100% { transform: rotate(360deg); opacity: .42; }
            }

            @keyframes pokerTurnCriticalPulse {
                0%, 100% { transform: scale(1); filter: brightness(1); }
                50% { transform: scale(1.065); filter: brightness(1.22); }
            }

            @keyframes pokerLiveNameGlow {
                0%, 100% { text-shadow: 0 0 0 rgba(110,231,183,0); }
                50% { text-shadow: 0 0 18px rgba(110,231,183,0.82); }
            }

            .poker-action-flash { animation: pokerActionFlash 760ms ease-out both; }
            .poker-action-pop { animation: pokerActionPillPop 420ms cubic-bezier(.2,.9,.3,1.25) both; }
            .poker-pot-receive { animation: pokerPotReceive 820ms ease-out both; }
            .poker-street-transition { animation: pokerStreetFade 520ms ease-out both; }
            .poker-winner-seat { animation: pokerWinnerGlow 1.65s ease-in-out infinite; }
            .poker-live-turn-seat { animation: pokerTurnLiveGlow 1.45s ease-in-out infinite; }
            .poker-turn-critical-pulse { animation: pokerTurnCriticalPulse 720ms ease-in-out infinite; }
            .poker-live-name-glow { animation: pokerLiveNameGlow 1.35s ease-in-out infinite; }
            .poker-live-border-sweep::before {
                content: '';
                position: absolute;
                inset: -42%;
                background: conic-gradient(from 0deg, transparent 0deg, rgba(110,231,183,.0) 46deg, rgba(110,231,183,.52) 90deg, transparent 134deg, transparent 360deg);
                animation: pokerTurnBorderSweep 2.4s linear infinite;
                pointer-events: none;
            }
            @keyframes pokerChipFlightToPot {
                0% { opacity: 0; transform: translate3d(0, 0, 0) scale(.72) rotate(0deg); }
                16% { opacity: 1; }
                70% { opacity: 1; transform: translate3d(36px, -78px, 0) scale(1.04) rotate(220deg); }
                100% { opacity: 0; transform: translate3d(72px, -126px, 0) scale(.64) rotate(380deg); }
            }

            @keyframes pokerAllInBlast {
                0%, 100% { filter: brightness(1); box-shadow: 0 0 0 rgba(251,113,133,0); }
                35% { filter: brightness(1.26); box-shadow: 0 0 48px rgba(251,113,133,.48); }
            }

            @keyframes pokerRaiseImpact {
                0% { transform: translateY(0) scale(1); }
                34% { transform: translateY(-5px) scale(1.022); }
                100% { transform: translateY(0) scale(1); }
            }

            @keyframes pokerFoldOverlay {
                0% { opacity: 0; backdrop-filter: grayscale(0); }
                100% { opacity: 1; backdrop-filter: grayscale(.8); }
            }

            @keyframes pokerCheckRipple {
                0% { opacity: .95; transform: scale(.94); }
                100% { opacity: 0; transform: scale(1.14); }
            }

            @keyframes pokerLiveActionToast {
                0% { opacity: 0; transform: translateY(12px) scale(.97); }
                18% { opacity: 1; transform: translateY(0) scale(1); }
                82% { opacity: 1; transform: translateY(0) scale(1); }
                100% { opacity: .92; transform: translateY(-2px) scale(.995); }
            }

            .poker-action-chip-flight {
                position: absolute;
                display: block;
                width: 1.05rem;
                height: 1.05rem;
                border-radius: 999px;
                border: 3px solid rgba(254,243,199,.9);
                background: radial-gradient(circle at 50% 50%, rgba(254,243,199,.85) 0 18%, rgba(220,38,38,.96) 20% 62%, rgba(127,29,29,.98) 64% 100%);
                box-shadow: 0 10px 18px rgba(0,0,0,.34);
                animation: pokerChipFlightToPot 820ms cubic-bezier(.2,.9,.25,1) both;
            }
            .poker-action-chip-flight-allin { width: 1.18rem; height: 1.18rem; animation-duration: 980ms; }
            .poker-action-chip-flight-source { box-shadow: 0 0 34px rgba(251,191,36,.18), inset 0 0 20px rgba(251,191,36,.08); }
            .poker-action-raise-impact { animation: pokerRaiseImpact 620ms cubic-bezier(.2,.9,.3,1.25) both; }
            .poker-action-allin-blast { animation: pokerAllInBlast 900ms ease-out both; }
            .poker-action-fold-shade { filter: saturate(.76) brightness(.82); }
            .poker-fold-overlay { animation: pokerFoldOverlay 480ms ease-out both; }
            .poker-check-ripple-ring { animation: pokerCheckRipple 760ms ease-out both; }
            .poker-action-check-ripple { box-shadow: 0 0 30px rgba(125,211,252,.20); }
            .poker-live-action-toast { animation: pokerLiveActionToast 2.4s ease-out both; }

            .poker-live-border-sweep > * { position: relative; z-index: 1; }

            @keyframes pokerShowdownWinningGlow {
                0%, 100% {
                    transform: translateY(0) scale(1);
                    box-shadow:
                        0 0 18px rgba(251,191,36,.34),
                        0 0 28px rgba(251,191,36,.22);
                }

                50% {
                    transform: translateY(-3px) scale(1.055);
                    box-shadow:
                        0 0 42px rgba(251,191,36,.78),
                        0 0 68px rgba(251,191,36,.35);
                }
            }

            .poker-showdown-winning-card {
                animation:
                    pokerShowdownWinningGlow 1.35s ease-in-out infinite;
                z-index: 2;
            }

            @media (prefers-reduced-motion: reduce) {
                .poker-action-flash,
                .poker-action-pop,
                .poker-pot-receive,
                .poker-street-transition,
                .poker-winner-seat,
                .poker-live-turn-seat,
                .poker-turn-critical-pulse,
                .poker-live-name-glow,
                .poker-live-border-sweep::before,
                .poker-action-chip-flight,
                .poker-action-raise-impact,
                .poker-action-allin-blast,
                .poker-fold-overlay,
                .poker-check-ripple-ring,
                .poker-live-action-toast {
                    animation: none !important;
                }
            }
        `}</style>
    );
}
