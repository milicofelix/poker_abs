import React from 'react';

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

function StatCard({ label, value, helper }) {
    return (
        <article className="rounded-3xl border border-white/10 bg-slate-950/45 p-5 shadow-xl">
            <p className="text-xs font-black uppercase tracking-[0.22em] text-slate-400">{label}</p>
            <strong className="mt-2 block text-3xl font-black text-white">{value}</strong>
            {helper && <span className="mt-1 block text-xs font-semibold text-slate-400">{helper}</span>}
        </article>
    );
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
    const max = Math.max(1, ...values.map((value) => Math.abs(value)));

    return max;
}

function PerformanceBars({ title, subtitle, items = [], valueKey, helperKey, valueFormatter = formatNumber }) {
    const max = chartValueRange(items, [valueKey]);

    return (
        <article className="rounded-3xl border border-white/10 bg-slate-950/45 p-5">
            <div className="flex flex-col gap-1">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-emerald-200">{title}</p>
                <p className="text-sm font-semibold text-slate-400">{subtitle}</p>
            </div>

            <div className="mt-5 grid gap-3">
                {items.length === 0 ? (
                    <p className="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">Sem dados suficientes para montar este gráfico.</p>
                ) : items.map((item) => {
                    const value = Number(item[valueKey] || 0);
                    const width = Math.max(6, Math.round((Math.abs(value) / max) * 100));

                    return (
                        <div key={`${title}-${item.date}-${valueKey}`} className="grid gap-2">
                            <div className="flex items-center justify-between gap-3 text-xs font-bold text-slate-300">
                                <span>{item.label}</span>
                                <span className={movementTone(value)}>{valueFormatter(value)}</span>
                            </div>
                            <div className="h-3 overflow-hidden rounded-full bg-white/10">
                                <div className={`h-full rounded-full ${value >= 0 ? 'bg-emerald-300/80' : 'bg-red-300/80'}`} style={{ width: `${width}%` }} />
                            </div>
                            {helperKey && <span className="text-[11px] font-semibold text-slate-500">{item[helperKey]}</span>}
                        </div>
                    );
                })}
            </div>
        </article>
    );
}

function HandsPerformanceChart({ items = [] }) {
    const max = chartValueRange(items, ['played', 'won']);

    return (
        <article className="rounded-3xl border border-white/10 bg-slate-950/45 p-5">
            <div className="flex flex-col gap-1">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-amber-100">Mãos jogadas/vencidas</p>
                <p className="text-sm font-semibold text-slate-400">Comparativo diário do período filtrado.</p>
            </div>

            <div className="mt-5 grid gap-4">
                {items.length === 0 ? (
                    <p className="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">Sem mãos no período selecionado.</p>
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
                                <div className="h-2 overflow-hidden rounded-full bg-white/10">
                                    <div className="h-full rounded-full bg-sky-300/75" style={{ width: `${playedWidth}%` }} />
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-white/10">
                                    <div className="h-full rounded-full bg-amber-300/80" style={{ width: `${wonWidth}%` }} />
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </article>
    );
}


function ActionDistributionChart({ items = [] }) {
    const max = chartValueRange(items, ['count']);

    return (
        <article className="rounded-3xl border border-white/10 bg-slate-950/45 p-5 xl:col-span-2">
            <div className="flex flex-col gap-1">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-cyan-100">Distribuição de ações</p>
                <p className="text-sm font-semibold text-slate-400">Mostra quais decisões aparecem mais no histórico filtrado.</p>
            </div>

            <div className="mt-5 grid gap-3 md:grid-cols-2">
                {items.length === 0 ? (
                    <p className="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300 md:col-span-2">Sem ações registradas para este período.</p>
                ) : items.map((item) => {
                    const width = Math.max(6, Math.round((Number(item.count || 0) / max) * 100));

                    return (
                        <div key={`action-${item.label}`} className="grid gap-2 rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div className="flex items-center justify-between gap-3 text-xs font-bold text-slate-300">
                                <span>{item.label}</span>
                                <span>{formatNumber(item.count)} · {formatPercent(item.percentage)}</span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-white/10">
                                <div className="h-full rounded-full bg-cyan-300/75" style={{ width: `${width}%` }} />
                            </div>
                        </div>
                    );
                })}
            </div>
        </article>
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
        <article className="rounded-3xl border border-white/10 bg-slate-950/45 p-5">
            <div className="flex flex-col gap-1">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-violet-100">Eficiência financeira</p>
                <p className="text-sm font-semibold text-slate-400">Médias do período para comparar volume e retorno.</p>
            </div>

            <div className="mt-5 grid gap-3">
                {rows.map(([label, value, suffix]) => (
                    <div key={label} className="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm">
                        <span className="font-semibold text-slate-300">{label}</span>
                        <strong className={suffix === '%' ? 'text-emerald-100' : movementTone(value)}>
                            {suffix === '%' ? formatPercent(value) : value === null || value === undefined ? '-' : formatNumber(value)}
                        </strong>
                    </div>
                ))}
            </div>
        </article>
    );
}


function PeriodComparisonPanel({ comparison = {} }) {
    if (!comparison.enabled) {
        return (
            <article className="mt-5 rounded-3xl border border-white/10 bg-slate-950/40 p-5 text-sm text-slate-300">
                <strong className="block text-white">Comparativo por período</strong>
                <span className="mt-1 block">{comparison.message ?? 'Selecione 7, 30 ou 90 dias para comparar com o período anterior.'}</span>
            </article>
        );
    }

    const current = comparison.current ?? {};
    const previous = comparison.previous ?? {};
    const delta = comparison.delta ?? {};
    const rows = [
        ['Mãos jogadas', current.handsPlayed, previous.handsPlayed, delta.handsPlayed],
        ['Mãos vencidas', current.handsWon, previous.handsWon, delta.handsWon],
        ['Lucro líquido', current.netProfit, previous.netProfit, delta.netProfit],
        ['ROI', current.roi, previous.roi, delta.roi, '%'],
        ['Winrate', current.winRate, previous.winRate, delta.winRate, '%'],
    ];

    return (
        <article className="mt-5 rounded-3xl border border-emerald-200/10 bg-slate-950/45 p-5">
            <div className="flex flex-col gap-1">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-emerald-200">Comparativo por período</p>
                <p className="text-sm font-semibold text-slate-400">{comparison.currentLabel} contra {comparison.previousLabel}.</p>
            </div>

            <div className="mt-5 overflow-hidden rounded-2xl border border-white/10">
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
        </article>
    );
}

function PerformanceChartsSection({ charts = {} }) {
    const profit = charts.profitByPeriod ?? [];
    const bankroll = charts.bankrollEvolution ?? [];
    const hands = charts.handsPerformance ?? [];
    const actions = charts.actionDistribution ?? [];
    const efficiency = charts.financialEfficiency ?? {};

    return (
        <div className="mt-5 grid gap-4 xl:grid-cols-3">
            <PerformanceBars
                title="Lucro por período"
                subtitle="Resultado líquido por dia."
                items={profit}
                valueKey="profit"
            />
            <PerformanceBars
                title="Evolução acumulada"
                subtitle="Lucro/prejuízo acumulado no período."
                items={bankroll}
                valueKey="net"
            />
            <HandsPerformanceChart items={hands} />
            <ActionDistributionChart items={actions} />
            <FinancialEfficiencyPanel data={efficiency} />
        </div>
    );
}

function AdvancedStatisticsSection({ advancedStatistics = {} }) {
    const cards = advancedStatistics.cards ?? [];
    const options = advancedStatistics.periodOptions ?? [];
    const currentPeriod = advancedStatistics.period ?? '30d';
    const summary = advancedStatistics.summary ?? {};

    return (
        <section className="rounded-3xl border border-emerald-200/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200">FASE {advancedStatistics.phase ?? '12.11.9'}</p>
                    <h2 className="mt-1 text-2xl font-black">Estatísticas avançadas</h2>
                    <p className="mt-1 text-sm text-slate-300">
                        Winrate real, fold rate, all-in rate, lucro por período e ROI calculados a partir do histórico persistido.
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    {options.map((option) => (
                        <a
                            key={option.value}
                            href={currentUrlWithPeriod(option.value)}
                            className={`rounded-xl px-4 py-2 text-xs font-black uppercase tracking-[0.18em] transition ${option.value === currentPeriod ? 'bg-emerald-300 text-emerald-950' : 'border border-white/10 bg-white/10 text-slate-200 hover:bg-white/20'}`}
                        >
                            {option.label}
                        </a>
                    ))}
                </div>
            </div>

            <div className="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                {cards.map((card) => (
                    <StatCard key={card.label} label={card.label} value={formatMetric(card)} helper={card.helper} />
                ))}
            </div>

            <div className="mt-5 grid gap-3 rounded-3xl border border-white/10 bg-slate-950/40 p-4 text-sm text-slate-300 md:grid-cols-3">
                <span><strong className="text-white">Período:</strong> {advancedStatistics.periodLabel ?? '-'}</span>
                <span><strong className="text-white">Ações:</strong> {formatNumber(summary.totalActions)}</span>
                <span><strong className="text-white">Lucro líquido:</strong> {formatNumber(summary.netProfit)}</span>
            </div>

            <PeriodComparisonPanel comparison={advancedStatistics.periodComparison ?? {}} />

            <PerformanceChartsSection charts={advancedStatistics.charts ?? {}} />
        </section>
    );
}

export default function Profile({ profile = {} }) {
    const player = profile.player ?? {};
    const stats = profile.stats ?? {};
    const recentTransactions = profile.recentTransactions ?? [];
    const recentHands = profile.recentHands ?? [];
    const advancedStatistics = profile.advancedStatistics ?? {};

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <div className="mx-auto flex max-w-7xl flex-col gap-6">
                <header className="overflow-hidden rounded-3xl border border-white/10 bg-white/10 shadow-2xl backdrop-blur">
                    <div className="grid gap-6 p-6 lg:grid-cols-[auto_1fr_auto] lg:items-center">
                        <div className="flex h-24 w-24 items-center justify-center rounded-[2rem] border border-amber-200/30 bg-amber-300/15 text-4xl font-black text-amber-100 shadow-xl shadow-black/25">
                            {player.initials ?? 'JP'}
                        </div>

                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.35em] text-emerald-200">Poker ABS · FASE {profile.phase ?? '12.9'}</p>
                            <h1 className="mt-2 text-4xl font-black">{player.name ?? 'Jogador'}</h1>
                            <p className="mt-2 max-w-3xl text-sm text-slate-300">
                                Perfil financeiro com bankroll, ROI, vitórias, métricas avançadas por período e histórico resumido das últimas mãos.
                            </p>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2 lg:min-w-[320px]">
                            <div className="rounded-2xl border border-emerald-300/20 bg-emerald-300/10 p-4">
                                <p className="text-xs font-black uppercase tracking-[0.2em] text-emerald-100">Bankroll</p>
                                <strong className="mt-1 block text-3xl font-black">{formatNumber(player.bankroll)}</strong>
                            </div>
                            <div className="rounded-2xl border border-amber-200/20 bg-amber-300/10 p-4">
                                <p className="text-xs font-black uppercase tracking-[0.2em] text-amber-100">Ranking</p>
                                <strong className="mt-1 block text-3xl font-black">#{player.rankingPosition ?? '-'}</strong>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-3 border-t border-white/10 bg-slate-950/30 px-6 py-4">
                        <a href="/poker/ranking" className="rounded-xl bg-white px-4 py-2 text-sm font-black text-slate-950 transition hover:bg-emerald-100">Ranking</a>
                        <a href="/poker/bankroll" className="rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20">Minhas fichas</a>
                        <a href="/poker/lobby" className="rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20">Lobby</a>
                    </div>
                </header>

                <section className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard label="Vitórias" value={formatNumber(stats.wins)} helper={`${formatNumber(stats.playedHands)} mãos com movimentação`} />
                    <StatCard label="Winrate" value={formatPercent(stats.winRate)} helper="Vitórias / mãos jogadas" />
                    <StatCard label="Lucro líquido" value={formatNumber(stats.netProfit)} helper="Retorno - investimento" />
                    <StatCard label="ROI" value={formatPercent(stats.roi)} helper="Resultado sobre buy-ins" />
                </section>

                <AdvancedStatisticsSection advancedStatistics={advancedStatistics} />

                <section className="grid gap-6 xl:grid-cols-[1fr_380px]">
                    <div className="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-2xl backdrop-blur">
                        <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200">Desempenho</p>
                                <h2 className="mt-1 text-2xl font-black">Resumo financeiro</h2>
                            </div>
                            <span className="text-xs font-bold text-slate-400">Último movimento: {stats.lastMovementAt ?? '-'}</span>
                        </div>

                        <div className="mt-5 grid gap-3 md:grid-cols-3">
                            <StatCard label="Investido" value={formatNumber(stats.invested)} helper={`Buy-ins ${formatNumber(stats.buyIns)} · Rebuys ${formatNumber(stats.rebuys)}`} />
                            <StatCard label="Retornado" value={formatNumber(stats.returned)} helper={`Prêmios ${formatNumber(stats.payouts)} · Saídas ${formatNumber(stats.stackReturns)}`} />
                            <StatCard label="Movimentações" value={formatNumber(stats.transactions)} helper="Ledger do bankroll" />
                        </div>

                        <div className="mt-6 rounded-3xl border border-white/10 bg-slate-950/40 p-5">
                            <p className="text-xs font-black uppercase tracking-[0.25em] text-amber-100">Últimas mãos</p>
                            <div className="mt-4 grid gap-3">
                                {recentHands.length === 0 ? (
                                    <p className="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">Nenhuma mão encontrada para este perfil.</p>
                                ) : recentHands.map((hand) => (
                                    <article key={`${hand.handId}-${hand.movement}`} className="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <strong className="block text-white">{hand.table}</strong>
                                                <span className="text-xs text-slate-400">{hand.finishedAt} · Pote {formatNumber(hand.pot)}</span>
                                            </div>
                                            <strong className={movementTone(hand.movement)}>{formatNumber(hand.movement)}</strong>
                                        </div>
                                        <p className="mt-2 text-sm text-slate-300">Vencedor: {hand.winner ?? '-'} · Melhor mão: {hand.winningHand ?? '-'}</p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </div>

                    <aside className="rounded-3xl border border-white/10 bg-slate-950/50 p-5 shadow-2xl">
                        <p className="text-xs font-black uppercase tracking-[0.25em] text-emerald-200">Movimentações recentes</p>
                        <div className="mt-4 grid gap-3">
                            {recentTransactions.length === 0 ? (
                                <p className="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">Sem movimentações ainda.</p>
                            ) : recentTransactions.map((transaction) => (
                                <article key={transaction.id} className="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <strong className="block text-white">{transaction.typeLabel}</strong>
                                            <span className="text-xs text-slate-400">{transaction.createdAt}</span>
                                        </div>
                                        <strong className={movementTone(transaction.amount)}>{formatNumber(transaction.amount)}</strong>
                                    </div>
                                    <p className="mt-2 text-xs text-slate-400">Saldo após: {formatNumber(transaction.balanceAfter)}</p>
                                </article>
                            ))}
                        </div>
                    </aside>
                </section>
            </div>
        </main>
    );
}
