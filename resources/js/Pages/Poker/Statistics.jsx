import React from 'react';
import { PokerBadge, PokerButton, PokerEmptyState, PokerSectionHeader, PokerSurface } from '../../Components/Poker/Ui/PokerDesignSystem';

function formatNumber(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

function MetricCard({ label, value, hint, tone = 'soft' }) {
    return (
        <PokerSurface tone={tone} className="p-5">
            <p className="text-[0.62rem] font-black uppercase tracking-[0.24em] text-slate-400">{label}</p>
            <p className="mt-3 text-2xl font-black text-white md:text-3xl">{value}</p>
            {hint ? <p className="mt-2 text-sm font-semibold leading-relaxed text-slate-300">{hint}</p> : null}
        </PokerSurface>
    );
}

function ActionBar({ label, value, total }) {
    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;

    return (
        <div>
            <div className="mb-2 flex items-center justify-between gap-3 text-sm">
                <span className="font-bold text-slate-100">{label}</span>
                <span className="text-slate-300">{formatNumber(value)} ações</span>
            </div>
            <div className="h-3 overflow-hidden rounded-full bg-slate-950/70 ring-1 ring-white/10">
                <div
                    className="h-full rounded-full bg-emerald-300 shadow-lg shadow-emerald-500/20"
                    style={{ width: `${percentage}%` }}
                />
            </div>
        </div>
    );
}

function EmptyStatistics({ title, description }) {
    return <PokerEmptyState eyebrow="Sem dados" title={title} description={description} tone="soft" />;
}

export default function Statistics({ statistics = {} }) {
    const overview = statistics.overview ?? {};
    const actions = statistics.actions ?? {};
    const streets = statistics.streets ?? [];
    const players = statistics.players ?? [];
    const actionsTotal = overview.totalActions ?? 0;

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 px-4 py-5 text-white sm:px-6 lg:px-8">
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                <PokerSurface as="header" tone="default" className="p-5 md:p-6">
                    <div className="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div className="flex flex-wrap gap-2">
                                <PokerBadge tone="success">Poker ABS</PokerBadge>
                                <PokerBadge tone="violet">FASE 13.1.5</PokerBadge>
                            </div>
                            <h1 className="mt-3 text-3xl font-black md:text-4xl">Estatísticas avançadas</h1>
                            <p className="mt-3 max-w-2xl text-sm font-semibold leading-relaxed text-slate-300">
                                Resumo local calculado a partir das mãos persistidas e do histórico de ações gravado no banco.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <PokerButton as="a" href="/poker" tone="primary">Voltar para mesa</PokerButton>
                            <PokerButton as="a" href="/poker/hands" tone="secondary">Histórico</PokerButton>
                            <PokerButton as="a" href="/poker/ranking" tone="secondary">Ranking</PokerButton>
                        </div>
                    </div>
                </PokerSurface>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard label="Mãos finalizadas" value={formatNumber(overview.handsPlayed)} tone="emerald" />
                    <MetricCard label="Pote acumulado" value={formatNumber(overview.totalPot)} hint="fichas movimentadas em mãos finalizadas" />
                    <MetricCard label="Pote médio" value={formatNumber(overview.averagePot)} />
                    <MetricCard label="Showdown" value={`${overview.showdownRate ?? 0}%`} hint={`${formatNumber(overview.showdowns)} mãos chegaram ao showdown`} tone="cyan" />
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <PokerSurface tone="soft" className="p-5 md:p-6">
                        <PokerSectionHeader
                            eyebrow="Ações"
                            title="Ações mais usadas"
                            description="Distribuição das ações registradas no histórico persistido."
                        />

                        <div className="mt-5 space-y-5">
                            {actionsTotal <= 0 ? (
                                <EmptyStatistics title="Nenhuma ação registrada" description="As barras serão exibidas após check, call, raise ou fold persistidos." />
                            ) : (
                                <>
                                    <ActionBar label="Checks" value={actions.checks ?? 0} total={actionsTotal} />
                                    <ActionBar label="Calls" value={actions.calls ?? 0} total={actionsTotal} />
                                    <ActionBar label="Raises" value={actions.raises ?? 0} total={actionsTotal} />
                                    <ActionBar label="Folds" value={actions.folds ?? 0} total={actionsTotal} />
                                </>
                            )}
                        </div>
                    </PokerSurface>

                    <PokerSurface tone="soft" className="p-5 md:p-6">
                        <PokerSectionHeader
                            eyebrow="Streets"
                            title="Por street"
                            description="Volume de ações e fichas movimentadas por etapa da mão."
                        />

                        {streets.length === 0 ? (
                            <div className="mt-5"><EmptyStatistics title="Sem streets calculadas" description="Jogue mãos completas para alimentar pré-flop, flop, turn, river e showdown." /></div>
                        ) : (
                            <div className="mt-5 overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/50">
                                <table className="w-full min-w-[460px] text-left text-sm">
                                    <thead className="bg-white/10 text-xs uppercase tracking-[0.18em] text-emerald-200">
                                        <tr>
                                            <th className="px-4 py-3">Street</th>
                                            <th className="px-4 py-3">Ações</th>
                                            <th className="px-4 py-3">Fichas</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-white/10">
                                        {streets.map((street) => (
                                            <tr key={street.street} className="transition hover:bg-white/[0.04]">
                                                <td className="px-4 py-3 font-bold text-white">{street.label}</td>
                                                <td className="px-4 py-3 text-slate-300">{formatNumber(street.actions)}</td>
                                                <td className="px-4 py-3 text-slate-300">{formatNumber(street.chipsInvested)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </PokerSurface>
                </section>

                <PokerSurface tone="soft" className="p-5 md:p-6">
                    <PokerSectionHeader
                        eyebrow="Jogadores"
                        title="Desempenho por jogador"
                        description="Estatísticas individuais calculadas por participante do jogo local."
                    />

                    {players.length === 0 ? (
                        <div className="mt-5"><EmptyStatistics title="Sem jogadores com estatísticas" description="Os cards aparecem depois que jogadores tiverem ações ou vitórias persistidas." /></div>
                    ) : (
                        <div className="mt-5 grid gap-4 md:grid-cols-2">
                            {players.map((player) => (
                                <article key={player.player} className="rounded-3xl border border-white/10 bg-slate-950/45 p-5 shadow-xl shadow-black/20">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200">Jogador</p>
                                            <h3 className="mt-2 text-2xl font-black text-white">{player.label}</h3>
                                            <p className="mt-1 text-sm font-semibold text-slate-300">
                                                {formatNumber(player.victories)} vitórias • {player.winRate}% aproveitamento
                                            </p>
                                        </div>

                                        <PokerBadge tone="success">{formatNumber(player.chipsInvested)} fichas</PokerBadge>
                                    </div>

                                    <dl className="mt-5 grid grid-cols-2 gap-3 text-sm">
                                        <MetricBox label="Checks" value={player.checks} />
                                        <MetricBox label="Calls" value={player.calls} />
                                        <MetricBox label="Raises" value={player.raises} />
                                        <MetricBox label="Folds" value={player.folds} />
                                    </dl>
                                </article>
                            ))}
                        </div>
                    )}
                </PokerSurface>
            </div>
        </main>
    );
}

function MetricBox({ label, value }) {
    return (
        <div className="rounded-2xl border border-white/10 bg-white/[0.06] p-4">
            <dt className="text-xs font-black uppercase tracking-[0.18em] text-slate-400">{label}</dt>
            <dd className="mt-1 text-2xl font-black text-white">{formatNumber(value)}</dd>
        </div>
    );
}
