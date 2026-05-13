import React from 'react';

export default function PokerHeader() {
    return (
        <header className="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl">
            <p className="text-sm uppercase tracking-[0.35em] text-emerald-300">Poker ABS</p>

            <h1 className="mt-2 text-3xl font-black md:text-5xl">
                Mesa local de Texas Hold'em
            </h1>

            <p className="mt-3 max-w-3xl text-slate-300">
                Fase 1.6: frontend mais componentizado, mesa organizada e cartas comunitárias reveladas conforme a street.
            </p>
        </header>
    );
}
