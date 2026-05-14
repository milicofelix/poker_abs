import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    function submit(event) {
        event.preventDefault();
        post('/register');
    }

    return (
        <main className="min-h-screen bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 p-6 text-white">
            <Head title="Cadastro" />

            <div className="mx-auto flex min-h-[calc(100vh-3rem)] max-w-6xl items-center justify-center">
                <section className="grid w-full overflow-hidden rounded-3xl border border-white/10 bg-white/10 shadow-2xl backdrop-blur lg:grid-cols-[0.95fr_1.05fr]">
                    <div className="hidden bg-slate-950/60 p-10 lg:block">
                        <p className="text-sm font-bold uppercase tracking-[0.35em] text-emerald-300">Poker ABS</p>
                        <h1 className="mt-4 text-4xl font-black leading-tight">Crie um jogador real para a mesa.</h1>
                        <p className="mt-4 max-w-xl text-sm leading-6 text-slate-300">
                            Este cadastro usa a autenticação padrão do Laravel e mantém o fluxo compatível com Inertia.
                        </p>

                        <div className="mt-8 rounded-3xl border border-cyan-300/20 bg-cyan-300/10 p-5 text-sm text-cyan-100">
                            Depois do cadastro, você será direcionado automaticamente para o lobby multiplayer.
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-5 p-6 md:p-10">
                        <div>
                            <p className="text-sm font-bold uppercase tracking-[0.3em] text-emerald-300">Cadastro</p>
                            <h2 className="mt-2 text-3xl font-black">Criar conta</h2>
                            <p className="mt-2 text-sm text-slate-300">Cadastre um usuário para testar mesas com jogadores reais.</p>
                        </div>

                        <label className="block">
                            <span className="text-sm font-bold text-slate-200">Nome</span>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-white outline-none transition placeholder:text-slate-500 focus:border-emerald-300"
                                placeholder="Seu nome"
                                autoComplete="name"
                                autoFocus
                            />
                            {errors.name && <span className="mt-2 block text-sm font-bold text-red-300">{errors.name}</span>}
                        </label>

                        <label className="block">
                            <span className="text-sm font-bold text-slate-200">E-mail</span>
                            <input
                                type="email"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-white outline-none transition placeholder:text-slate-500 focus:border-emerald-300"
                                placeholder="voce@email.com"
                                autoComplete="email"
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
                                placeholder="Mínimo de 8 caracteres"
                                autoComplete="new-password"
                            />
                            {errors.password && <span className="mt-2 block text-sm font-bold text-red-300">{errors.password}</span>}
                        </label>

                        <label className="block">
                            <span className="text-sm font-bold text-slate-200">Confirmar senha</span>
                            <input
                                type="password"
                                value={data.password_confirmation}
                                onChange={(event) => setData('password_confirmation', event.target.value)}
                                className="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-white outline-none transition placeholder:text-slate-500 focus:border-emerald-300"
                                placeholder="Repita a senha"
                                autoComplete="new-password"
                            />
                        </label>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-2xl bg-emerald-400 px-5 py-3 font-black text-emerald-950 shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing ? 'Criando...' : 'Criar conta'}
                        </button>

                        <p className="text-center text-sm text-slate-300">
                            Já tem conta?{' '}
                            <Link href="/login" className="font-black text-emerald-300 hover:text-emerald-200">
                                Entrar
                            </Link>
                        </p>
                    </form>
                </section>
            </div>
        </main>
    );
}
