import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(event) {
        event.preventDefault();
        post('/login');
    }

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <Head title="Entrar" />

            <div className="mx-auto flex min-h-[calc(100vh-3rem)] max-w-6xl items-center justify-center">
                <section className="grid w-full overflow-hidden rounded-3xl border border-white/10 bg-white/10 shadow-2xl backdrop-blur lg:grid-cols-[1.05fr_0.95fr]">
                    <div className="hidden bg-slate-950/60 p-10 lg:block">
                        <p className="text-sm font-bold uppercase tracking-[0.35em] text-emerald-300">Poker ABS</p>
                        <h1 className="mt-4 text-4xl font-black leading-tight">Entre para testar mesas multiplayer reais.</h1>
                        <p className="mt-4 max-w-xl text-sm leading-6 text-slate-300">
                            Use uma conta para validar lobby, entrada em mesa, jogadores reais, assentos e os próximos controles de turno.
                        </p>

                        <div className="mt-8 rounded-3xl border border-emerald-300/20 bg-emerald-300/10 p-5 text-sm text-emerald-100">
                            Dica: para testar dois jogadores, entre com um usuário na janela normal e outro na janela anônima.
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-5 p-6 md:p-10">
                        <div>
                            <p className="text-sm font-bold uppercase tracking-[0.3em] text-emerald-300">Login</p>
                            <h2 className="mt-2 text-3xl font-black">Acessar conta</h2>
                            <p className="mt-2 text-sm text-slate-300">Informe seus dados para entrar no sistema.</p>
                        </div>

                        <label className="block">
                            <span className="text-sm font-bold text-slate-200">E-mail</span>
                            <input
                                type="email"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-white outline-none transition placeholder:text-slate-500 focus:border-emerald-300"
                                placeholder="voce@email.com"
                                autoComplete="email"
                                autoFocus
                            />
                            {errors.email && <span className="mt-2 block text-sm font-bold text-red-300">{errors.email}</span>}
                        </label>

                        <label className="block">
                            <span className="text-sm font-bold text-slate-200">Senha</span>
                            <input
                                type="password"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-white outline-none transition placeholder:text-slate-500 focus:border-emerald-300"
                                placeholder="Sua senha"
                                autoComplete="current-password"
                            />
                            {errors.password && <span className="mt-2 block text-sm font-bold text-red-300">{errors.password}</span>}
                        </label>

                        <label className="flex items-center gap-3 text-sm font-bold text-slate-300">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(event) => setData('remember', event.target.checked)}
                                className="h-4 w-4 rounded border-white/20 bg-slate-950"
                            />
                            Manter conectado
                        </label>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-2xl bg-emerald-400 px-5 py-3 font-black text-emerald-950 shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing ? 'Entrando...' : 'Entrar'}
                        </button>

                        <p className="text-center text-sm text-slate-300">
                            Ainda não tem conta?{' '}
                            <Link href="/register" className="font-black text-emerald-300 hover:text-emerald-200">
                                Criar cadastro
                            </Link>
                        </p>
                    </form>
                </section>
            </div>
        </main>
    );
}
