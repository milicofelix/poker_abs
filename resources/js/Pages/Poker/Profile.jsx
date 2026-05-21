import React from 'react';
import { PokerBadge, PokerButton, PokerEmptyState, PokerSectionHeader, PokerSurface } from '../../Components/Poker/Ui/PokerDesignSystem';

function formatNumber(value) {
    return Number(value || 0).toLocaleString('pt-BR');
}

function formatPercent(value) {
    if (value === null || value === undefined) {
        return '-';
    }

    return `${Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
}

function movementTone(amount) {
    return Number(amount || 0) >= 0 ? 'text-emerald-200' : 'text-red-200';
}

function formatMetric(card) {
    if (card?.suffix === '%') {
        return formatPercent(card.value);
    }

    return formatNumber(card?.value);
}

function formatDelta(delta, suffix = '') {
    if (!delta || delta.value === null || delta.value === undefined) {
        return '-';
    }

    const value = Number(delta.value || 0);
    const prefix = value > 0 ? '+' : '';

    if (suffix === '%') {
        return `${prefix}${value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
    }

    return `${prefix}${value.toLocaleString('pt-BR')}`;
}

function deltaTone(delta) {
    if (!delta || delta.direction === 'flat') {
        return 'text-slate-200';
    }

    return delta.direction === 'up' ? 'text-emerald-200' : 'text-red-200';
}

function currentUrlWithPeriod(period) {
    const params = new URLSearchParams(window.location.search);
    params.set('advanced_period', period);

    return `${window.location.pathname}?${params.toString()}`;
}

function chartValueRange(items, keys) {
    const values = items.flatMap((item) => keys.map((key) => Number(item[key] || 0)));

    return Math.max(1, ...values.map((value) => Math.abs(value)));
}

function StatCard({ label, value, helper, tone = 'soft' }) {
    return (
        <PokerSurface as="article" tone={tone} className="p-5">
            <p className="text-[0.62rem] font-black uppercase tracking-[0.24em] text-slate-400">{label}</p>
            <strong className="mt-2 block text-2xl font-black text-white md:text-3xl">{value}</strong>
            {helper ? <span className="mt-2 block text-xs font-semibold leading-relaxed text-slate-400">{helper}</span> : null}
        </PokerSurface>
    );
}

function MiniMetric({ label, value, helper, tone = 'neutral' }) {
    return (
        <div className="rounded-2xl border border-white/10 bg-white/[0.06] p-4">
            <div className="flex items-center justify-between gap-3">
                <p className="text-[0.62rem] font-black uppercase tracking-[0.2em] text-slate-400">{label}</p>
                <PokerBadge tone={tone}>{helper ?? 'Perfil'}</PokerBadge>
            </div>
            <strong className="mt-3 block text-2xl font-black text-white">{value}</strong>
        </div>
    );
}

function EmptyList({ title, description }) {
    return <PokerEmptyState title={title} description={description} eyebrow="Sem registros" tone="soft" />;
}

function PerformanceBars({ title, subtitle, items = [], valueKey, helperKey, valueFormatter = formatNumber, tone = 'emerald' }) {
    const max = chartValueRange(items, [valueKey]);

    return (
        <PokerSurface as="article" tone="soft" className="p-5">
            <PokerSectionHeader eyebrow={title} title={subtitle} description="Barras compactas para leitura rápida do desempenho no período." />

            <div className="mt-5 grid gap-3">
                {items.length === 0 ? (
                    <EmptyList title="Sem dados para este gráfico" description="Altere o período ou jogue novas mãos para alimentar esta visualização." />
                ) : items.map((item) => {
                    const value = Number(item[valueKey] || 0);
                    const width = Math.max(6, Math.round((Math.abs(value) / max) * 100));

                    return (
                        <div key={`${title}-${item.date}-${valueKey}`} className="grid gap-2">
                            <div className="flex items-center justify-between gap-3 text-xs font-bold text-slate-300">
                                <span>{item.label}</span>
                                <span className={movementTone(value)}>{valueFormatter(value)}</span>
                            </div>
                            <div className="h-3 overflow-hidden rounded-full bg-slate-950/70 ring-1 ring-white/10">
                                <div className={`h-full rounded-full ${tone === 'cyan' ? 'bg-cyan-300/80' : 'bg-emerald-300/80'}`} style={{ width: `${width}%` }} />
                            </div>
                            {helperKey ? <span className="text-[11px] font-semibold text-slate-500">{item[helperKey]}</span> : null}
                        </div>
                    );
                })}
            </div>
        </PokerSurface>
    );
}

function HandsPerformanceChart({ items = [] }) {
    const max = chartValueRange(items, ['played', 'won']);

    return (
        <PokerSurface as="article" tone="soft" className="p-5">
            <PokerSectionHeader eyebrow="Mãos jogadas/vencidas" title="Comparativo diário" description="Volume de mãos no período e taxa de vitórias por dia." />

            <div className="mt-5 grid gap-4">
                {items.length === 0 ? (
                    <EmptyList title="Sem mãos no período" description="As barras aparecem assim que existirem mãos persistidas para o filtro atual." />
                ) : items.map((item) => {
                    const playedWidth = Math.max(6, Math.round((Number(item.played || 0) / max) * 100));
                    const wonWidth = Math.max(6, Math.round((Number(item.won || 0) / max) * 100));

                    return (
                        <div key={`hands-${item.date}`} className="grid gap-2">
                            <div className="flex items-center justify-between gap-3 text-xs font-bold text-slate-300">
                                <span>{item.label}</span>
                                <span>{formatNumber(item.won)} / {formatNumber(item.played)} · {formatPercent(item.winRate)}</span>
                            </div>
                            <div className="grid gap-1">
                                <div className="h-2 overflow-hidden rounded-full bg-slate-950/70 ring-1 ring-white/10">
                                    <div className="h-full rounded-full bg-cyan-300/75" style={{ width: `${playedWidth}%` }} />
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-slate-950/70 ring-1 ring-white/10">
                                    <div className="h-full rounded-full bg-amber-300/80" style={{ width: `${wonWidth}%` }} />
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </PokerSurface>
    );
}

function ActionDistributionChart({ items = [] }) {
    const max = chartValueRange(items, ['count']);

    return (
        <PokerSurface as="article" tone="soft" className="p-5 xl:col-span-2">
            <PokerSectionHeader eyebrow="Distribuição de ações" title="Decisões mais frequentes" description="Mostra quais decisões aparecem mais no histórico filtrado." />

            <div className="mt-5 grid gap-3 md:grid-cols-2">
                {items.length === 0 ? (
                    <div className="md:col-span-2"><EmptyList title="Sem ações registradas" description="Jogue novas mãos ou ajuste o período para visualizar esta distribuição." /></div>
                ) : items.map((item) => {
                    const width = Math.max(6, Math.round((Number(item.count || 0) / max) * 100));

                    return (
                        <div key={`action-${item.label}`} className="grid gap-2 rounded-2xl border border-white/10 bg-white/[0.06] p-3">
                            <div className="flex items-center justify-between gap-3 text-xs font-bold text-slate-300">
                                <span>{item.label}</span>
                                <span>{formatNumber(item.count)} · {formatPercent(item.percentage)}</span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-slate-950/70 ring-1 ring-white/10">
                                <div className="h-full rounded-full bg-cyan-300/75" style={{ width: `${width}%` }} />
                            </div>
                        </div>
                    );
                })}
            </div>
        </PokerSurface>
    );
}

function FinancialEfficiencyPanel({ data = {} }) {
    const rows = [
        ['Investido/mão', data.averageInvestedPerHand],
        ['Retorno/mão', data.averageReturnedPerHand],
        ['Lucro médio/mão', data.averageProfitPerHand],
        ['Conversão retorno/investido', data.conversionRate, '%'],
        ['Winrate do período', data.winRate, '%'],
    ];

    return (
        <PokerSurface as="article" tone="soft" className="p-5">
            <PokerSectionHeader eyebrow="Eficiência financeira" title="Médias do período" description="Comparação entre volume investido, retorno e aproveitamento." />

            <div className="mt-5 grid gap-3">
                {rows.map(([label, value, suffix]) => (
                    <div key={label} className="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-sm">
                        <span className="font-semibold text-slate-300">{label}</span>
                        <strong className={suffix === '%' ? 'text-emerald-100' : movementTone(value)}>
                            {suffix === '%' ? formatPercent(value) : value === null || value === undefined ? '-' : formatNumber(value)}
                        </strong>
                    </div>
                ))}
            </div>
        </PokerSurface>
    );
}

function PeriodComparisonPanel({ comparison = {} }) {
    if (!comparison.enabled) {
        return (
            <PokerSurface as="article" tone="soft" className="mt-5 p-5 text-sm text-slate-300">
                <strong className="block text-white">Comparativo por período</strong>
                <span className="mt-1 block">{comparison.message ?? 'Selecione 7, 30 ou 90 dias para comparar com o período anterior.'}</span>
            </PokerSurface>
        );
    }

    const rows = [
        ['Mãos jogadas', comparison.current?.handsPlayed, comparison.previous?.handsPlayed, comparison.delta?.handsPlayed],
        ['Vitórias', comparison.current?.wins, comparison.previous?.wins, comparison.delta?.wins],
        ['Winrate', comparison.current?.winRate, comparison.previous?.winRate, comparison.delta?.winRate, '%'],
        ['Lucro líquido', comparison.current?.netProfit, comparison.previous?.netProfit, comparison.delta?.netProfit],
        ['ROI', comparison.current?.roi, comparison.previous?.roi, comparison.delta?.roi, '%'],
    ];

    return (
        <PokerSurface as="article" tone="soft" className="mt-5 p-5">
            <PokerSectionHeader eyebrow="Comparativo" title="Período atual vs. período anterior" description={comparison.message ?? 'Leitura direta para identificar evolução ou queda.'} />

            <div className="mt-5 overflow-x-auto rounded-2xl border border-white/10">
                <div className="min-w-[680px]">
                    <div className="grid grid-cols-4 bg-white/10 px-4 py-3 text-xs font-black uppercase tracking-[0.18em] text-slate-300">
                        <span>Métrica</span>
                        <span>Atual</span>
                        <span>Anterior</span>
                        <span>Variação</span>
                    </div>
                    {rows.map(([label, currentValue, previousValue, rowDelta, suffix]) => (
                        <div key={label} className="grid grid-cols-4 border-t border-white/10 px-4 py-3 text-sm">
                            <span className="font-bold text-white">{label}</span>
                            <span className="text-slate-200">{suffix === '%' ? formatPercent(currentValue) : formatNumber(currentValue)}</span>
                            <span className="text-slate-400">{suffix === '%' ? formatPercent(previousValue) : formatNumber(previousValue)}</span>
                            <strong className={deltaTone(rowDelta)}>{formatDelta(rowDelta, suffix)}</strong>
                        </div>
                    ))}
                </div>
            </div>
        </PokerSurface>
    );
}

function PerformanceChartsSection({ charts = {} }) {
    return (
        <div className="mt-5 grid gap-4 xl:grid-cols-3">
            <PerformanceBars title="Lucro por período" subtitle="Resultado líquido por dia" items={charts.profitByPeriod ?? []} valueKey="profit" />
            <PerformanceBars title="Evolução acumulada" subtitle="Lucro/prejuízo acumulado" items={charts.bankrollEvolution ?? []} valueKey="net" tone="cyan" />
            <HandsPerformanceChart items={charts.handsPerformance ?? []} />
            <ActionDistributionChart items={charts.actionDistribution ?? []} />
            <FinancialEfficiencyPanel data={charts.financialEfficiency ?? {}} />
        </div>
    );
}

function AdvancedStatisticsSection({ advancedStatistics = {} }) {
    const cards = advancedStatistics.cards ?? [];
    const options = advancedStatistics.periodOptions ?? [];
    const currentPeriod = advancedStatistics.period ?? '30d';
    const summary = advancedStatistics.summary ?? {};

    return (
        <PokerSurface tone="default" className="p-5">
            <PokerSectionHeader
                eyebrow={`FASE ${advancedStatistics.phase ?? '12.11.9'}`}
                title="Estatísticas avançadas"
                description="Winrate real, fold rate, all-in rate, lucro por período e ROI calculados a partir do histórico persistido."
                action={options.map((option) => (
                    <PokerButton
                        key={option.value}
                        as="a"
                        href={currentUrlWithPeriod(option.value)}
                        tone={option.value === currentPeriod ? 'primary' : 'secondary'}
                        className="px-3 py-2 text-xs"
                    >
                        {option.label}
                    </PokerButton>
                ))}
            />

            {cards.length === 0 ? (
                <div className="mt-5">
                    <EmptyList title="Sem estatísticas avançadas" description="As métricas aparecem após existirem mãos e ações suficientes no histórico." />
                </div>
            ) : (
                <div className="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    {cards.map((card) => (
                        <StatCard key={card.label} label={card.label} value={formatMetric(card)} helper={card.helper} tone="soft" />
                    ))}
                </div>
            )}

            <div className="mt-5 grid gap-3 rounded-3xl border border-white/10 bg-slate-950/50 p-4 text-sm text-slate-300 md:grid-cols-3">
                <span><strong className="text-white">Período:</strong> {advancedStatistics.periodLabel ?? '-'}</span>
                <span><strong className="text-white">Ações:</strong> {formatNumber(summary.totalActions)}</span>
                <span><strong className="text-white">Lucro líquido:</strong> {formatNumber(summary.netProfit)}</span>
            </div>

            <PeriodComparisonPanel comparison={advancedStatistics.periodComparison ?? {}} />
            <PerformanceChartsSection charts={advancedStatistics.charts ?? {}} />
        </PokerSurface>
    );
}

function TransactionList({ items = [] }) {
    if (items.length === 0) {
        return <EmptyList title="Sem movimentações recentes" description="Buy-ins, rebuys, prêmios e devoluções aparecerão aqui." />;
    }

    return (
        <div className="grid gap-3">
            {items.map((transaction) => (
                <div key={transaction.id ?? `${transaction.type}-${transaction.createdAt}`} className="rounded-2xl border border-white/10 bg-white/[0.06] p-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="font-black text-white">{transaction.label ?? transaction.type}</p>
                            <p className="mt-1 text-xs font-semibold text-slate-400">{transaction.createdAt ?? transaction.created_at ?? '-'}</p>
                        </div>
                        <strong className={movementTone(transaction.amount)}>{formatNumber(transaction.amount)}</strong>
                    </div>
                </div>
            ))}
        </div>
    );
}

function RecentHandsList({ items = [] }) {
    if (items.length === 0) {
        return <EmptyList title="Sem mãos recentes" description="As últimas mãos finalizadas serão listadas aqui com resultado e data." />;
    }

    return (
        <div className="grid gap-3">
            {items.map((hand) => (
                <div key={hand.id ?? hand.finishedAt} className="rounded-2xl border border-white/10 bg-white/[0.06] p-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="font-black text-white">{hand.title ?? `Mão #${hand.id ?? '-'}`}</p>
                            <p className="mt-1 text-xs font-semibold text-slate-400">{hand.finishedAt ?? hand.finished_at ?? '-'}</p>
                        </div>
                        <PokerBadge tone={hand.won ? 'success' : 'neutral'}>{hand.won ? 'Vitória' : 'Histórico'}</PokerBadge>
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function Profile({ profile = {} }) {
    const player = profile.player ?? {};
    const stats = profile.stats ?? {};
    const recentTransactions = profile.recentTransactions ?? [];
    const recentHands = profile.recentHands ?? [];
    const advancedStatistics = profile.advancedStatistics ?? {};

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 px-4 py-5 text-white sm:px-6 lg:px-8">
            <div className="mx-auto flex max-w-7xl flex-col gap-6">
                <PokerSurface as="header" tone="default" className="overflow-hidden">
                    <div className="grid gap-6 p-5 lg:grid-cols-[auto_1fr_auto] lg:items-center lg:p-6">
                        <div className="flex h-24 w-24 items-center justify-center rounded-[2rem] border border-amber-200/30 bg-amber-300/15 text-4xl font-black text-amber-100 shadow-xl shadow-black/25">
                            {player.initials ?? 'JP'}
                        </div>

                        <div>
                            <div className="flex flex-wrap gap-2">
                                <PokerBadge tone="success">Poker ABS</PokerBadge>
                                <PokerBadge tone="violet">FASE {profile.phase ?? '13.1.5'}</PokerBadge>
                            </div>
                            <h1 className="mt-3 text-3xl font-black md:text-5xl">{player.name ?? 'Jogador'}</h1>
                            <p className="mt-3 max-w-3xl text-sm font-semibold leading-relaxed text-slate-300">
                                Perfil financeiro com bankroll, ROI, vitórias, métricas avançadas por período e histórico resumido das últimas mãos.
                            </p>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2 lg:min-w-[340px]">
                            <MiniMetric label="Bankroll" value={formatNumber(player.bankroll)} helper="Fichas" tone="success" />
                            <MiniMetric label="Ranking" value={`#${player.rankingPosition ?? '-'}`} helper="Posição" tone="warning" />
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-3 border-t border-white/10 bg-slate-950/40 px-5 py-4 lg:px-6">
                        <PokerButton as="a" href="/poker/ranking" tone="primary">Ranking</PokerButton>
                        <PokerButton as="a" href="/poker/bankroll" tone="secondary">Minhas fichas</PokerButton>
                        <PokerButton as="a" href="/poker/lobby" tone="secondary">Lobby</PokerButton>
                    </div>
                </PokerSurface>

                <section className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard label="Vitórias" value={formatNumber(stats.wins)} helper={`${formatNumber(stats.playedHands)} mãos com movimentação`} tone="emerald" />
                    <StatCard label="Winrate" value={formatPercent(stats.winRate)} helper="Vitórias / mãos jogadas" />
                    <StatCard label="Lucro líquido" value={formatNumber(stats.netProfit)} helper="Retorno - investimento" tone={Number(stats.netProfit || 0) >= 0 ? 'emerald' : 'danger'} />
                    <StatCard label="ROI" value={formatPercent(stats.roi)} helper="Resultado sobre buy-ins" />
                </section>

                <AdvancedStatisticsSection advancedStatistics={advancedStatistics} />

                <section className="grid gap-6 xl:grid-cols-[1fr_380px]">
                    <PokerSurface tone="soft" className="p-5">
                        <PokerSectionHeader
                            eyebrow="Desempenho"
                            title="Resumo financeiro"
                            description="Leitura consolidada do investimento, retorno e movimentações do jogador."
                            action={<span className="text-xs font-bold text-slate-400">Último movimento: {stats.lastMovementAt ?? '-'}</span>}
                        />

                        <div className="mt-5 grid gap-3 md:grid-cols-3">
                            <StatCard label="Investido" value={formatNumber(stats.invested)} helper={`Buy-ins ${formatNumber(stats.buyIns)} · Rebuys ${formatNumber(stats.rebuys)}`} />
                            <StatCard label="Retornado" value={formatNumber(stats.returned)} helper={`Prêmios ${formatNumber(stats.payouts)} · Saídas ${formatNumber(stats.stackReturns)}`} />
                            <StatCard label="Saldo" value={formatNumber(stats.balance)} helper="Bankroll atual após movimentos" />
                        </div>
                    </PokerSurface>

                    <PokerSurface tone="soft" className="p-5">
                        <PokerSectionHeader eyebrow="Atalhos" title="Navegação do jogador" description="Acesso rápido às áreas relacionadas ao desempenho." />
                        <div className="mt-5 grid gap-3">
                            <PokerButton as="a" href="/poker/statistics" tone="secondary">Estatísticas globais</PokerButton>
                            <PokerButton as="a" href="/poker/hands" tone="secondary">Histórico de mãos</PokerButton>
                            <PokerButton as="a" href="/poker/tournaments" tone="secondary">Torneios</PokerButton>
                        </div>
                    </PokerSurface>
                </section>

                <section className="grid gap-6 xl:grid-cols-2">
                    <PokerSurface tone="soft" className="p-5">
                        <PokerSectionHeader eyebrow="Histórico" title="Movimentações recentes" description="Últimas entradas e saídas de bankroll." />
                        <div className="mt-5"><TransactionList items={recentTransactions} /></div>
                    </PokerSurface>

                    <PokerSurface tone="soft" className="p-5">
                        <PokerSectionHeader eyebrow="Mãos" title="Últimas mãos" description="Resumo das mãos mais recentes vinculadas ao jogador." />
                        <div className="mt-5"><RecentHandsList items={recentHands} /></div>
                    </PokerSurface>
                </section>
            </div>
        </main>
    );
}
