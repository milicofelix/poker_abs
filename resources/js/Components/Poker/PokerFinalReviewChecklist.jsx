import React from 'react';

const CHECKLIST_ITEMS = [
    {
        title: 'Fluxo da mão',
        description: 'Iniciar nova mão, avançar streets, showdown e reinício sem refresh manual.',
    },
    {
        title: 'Bot vs Bot',
        description: 'Timer expira, bots agem automaticamente, cartas dos dois bots ficam visíveis.',
    },
    {
        title: 'Troca de adversário',
        description: 'Substituir bot/adversário mantém assentos, stacks, estado da mesa e reidratação corretos.',
    },
    {
        title: 'Mobile',
        description: 'Botões fixos não cobrem cartas importantes e o botão de nova mão aparece ao finalizar.',
    },
    {
        title: 'Tempo real',
        description: 'Reverb, reidratação e timeout mostram estado claro sem mensagens alarmistas desnecessárias.',
    },
];

export default function PokerFinalReviewChecklist({ table = null, state = null }) {
    const isBotVsBot = Boolean(state?.botVsBotSimulation);
    const maxPlayers = Number(table?.maxPlayers ?? 2);
    const phase8Hint = maxPlayers <= 2
        ? 'Mesa atual validada como heads-up. Mesas 3+ jogadores ficam preparadas para a FASE 8.'
        : `Mesa configurada para até ${maxPlayers} jogadores.`;

    return (
        <section className="rounded-[1.5rem] border border-emerald-300/20 bg-emerald-400/10 p-4 shadow-xl shadow-black/30">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p className="text-[0.65rem] font-black uppercase tracking-[0.24em] text-emerald-200">FASE 7.9</p>
                    <h3 className="mt-1 text-lg font-black text-white">Revisão final da mesa</h3>
                    <p className="mt-1 text-sm font-semibold text-emerald-50/75">
                        Use este painel como checklist manual antes de abrir a FASE 8 — Lobby e salas.
                    </p>
                </div>

                <span className="w-fit rounded-full border border-emerald-200/30 bg-emerald-300/15 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-emerald-100">
                    {isBotVsBot ? 'Bot vs Bot ativo' : 'Mesa interativa'}
                </span>
            </div>

            <div className="mt-4 grid gap-2 md:grid-cols-2">
                {CHECKLIST_ITEMS.map((item) => (
                    <div key={item.title} className="rounded-2xl border border-white/10 bg-slate-950/45 p-3">
                        <div className="flex items-start gap-2">
                            <span className="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-300 text-xs font-black text-emerald-950">
                                ✓
                            </span>
                            <div>
                                <p className="text-sm font-black text-white">{item.title}</p>
                                <p className="mt-0.5 text-xs font-semibold leading-relaxed text-slate-300">{item.description}</p>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="mt-3 rounded-2xl border border-amber-200/20 bg-amber-300/10 px-3 py-2 text-xs font-bold leading-relaxed text-amber-50">
                {phase8Hint}
            </div>
        </section>
    );
}
