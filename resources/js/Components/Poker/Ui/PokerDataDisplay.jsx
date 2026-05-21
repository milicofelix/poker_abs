import React from 'react';
import { PokerBadge, PokerEmptyState, PokerSectionHeader, PokerSurface } from './PokerDesignSystem';

export function PokerResponsiveTable({ columns = [], rows = [], emptyState = null, getRowKey = (_, index) => index, renderRow }) {
    if (rows.length === 0) {
        return emptyState ?? (
            <PokerEmptyState
                title="Nenhum registro encontrado"
                description="Quando houver dados disponíveis, eles aparecerão nesta listagem."
            />
        );
    }

    return (
        <div className="overflow-x-auto rounded-2xl border border-white/10">
            <div
                className="grid min-w-[760px] gap-3 bg-slate-950/75 px-4 py-3 text-xs font-black uppercase tracking-[0.16em] text-slate-400"
                style={{ gridTemplateColumns: columns.map((column) => column.width ?? '1fr').join(' ') }}
            >
                {columns.map((column) => (
                    <span key={column.key} className={column.align === 'right' ? 'text-right' : ''}>{column.label}</span>
                ))}
            </div>

            <div className="min-w-[760px] divide-y divide-white/10">
                {rows.map((row, index) => (
                    <div key={getRowKey(row, index)}>{renderRow(row, index)}</div>
                ))}
            </div>
        </div>
    );
}

export function PokerInfoGrid({ items = [], columns = 'md:grid-cols-4', className = '' }) {
    return (
        <div className={`grid gap-3 ${columns} ${className}`}>
            {items.map((item) => (
                <div key={item.label} className="rounded-2xl border border-white/10 bg-slate-950/45 p-4">
                    <span className="text-xs font-bold text-slate-400">{item.label}</span>
                    <strong className="mt-1 block text-xl font-black text-white sm:text-2xl">{item.value}</strong>
                </div>
            ))}
        </div>
    );
}

export function PokerTimelineList({ title, eyebrow, description, items = [], emptyText, renderItem, badge = null }) {
    return (
        <PokerSurface as="aside" tone="default" className="p-5">
            <PokerSectionHeader eyebrow={eyebrow} title={title} description={description} action={badge ? <PokerBadge tone="info">{badge}</PokerBadge> : null} />
            <div className="mt-4 grid gap-3">
                {items.length === 0 ? (
                    <p className="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm font-semibold text-slate-300">{emptyText}</p>
                ) : items.map(renderItem)}
            </div>
        </PokerSurface>
    );
}
