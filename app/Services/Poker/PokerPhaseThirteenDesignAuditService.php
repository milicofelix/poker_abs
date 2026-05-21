<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;

final class PokerPhaseThirteenDesignAuditService
{
    /**
     * @return array<string, mixed>
     */
    public function forTable(?PokerTable $table, bool $isLocalMode): array
    {
        return [
            'phase' => '13.1',
            'title' => 'Auditoria visual geral',
            'summary' => $isLocalMode
                ? 'Modo local preparado para receber o mesmo design system das mesas multiplayer, sem alterar engine, sessão ou histórico.'
                : 'Mesa multiplayer preparada para padronização visual de botões, cards, tabelas, badges e estados sem alterar regras de jogo.',
            'mode' => $isLocalMode ? 'local' : 'multiplayer',
            'safeToChangeGameplay' => false,
            'foundation' => $this->foundationComponents(),
            'tableContext' => [
                'id' => $table?->id,
                'name' => $table?->name ?? 'Mesa não carregada',
                'status' => $table?->status ?? 'unknown',
                'maxPlayers' => $table?->max_players,
            ],
            'designTokens' => $this->designTokens(),
            'checklist' => $this->checklist($isLocalMode),
            'nextSteps' => [
                '13.1.2 — Aplicar shell de página, hero, navegação, flash e filtros no lobby como primeira tela-piloto.',
                '13.1.3 — Aplicar shell, hero, cards métricos e tabela responsiva no Ranking financeiro.',
                '13.1.4 — Aplicar shell, cards, tabela responsiva e estados vazios em Bankroll.',
                '13.2 — Iniciar mesa premium com cartas, fichas, avatares, ação atual, animações e mobile.',
            ],
            'guardrails' => [
                'Não alterar cálculo de vencedor, apostas, blinds, side pots, bankroll, timer ou timeout automático.',
                'Não mudar contratos JSON consumidos pelo frontend durante a auditoria visual.',
                'Não liberar novo comportamento de mesa 3+ apenas por ajuste de layout.',
                'Toda melhoria visual precisa ser incremental, testável e reversível por patch.',
            ],
        ];
    }

    /**
     * @return array<int, array{component: string, purpose: string, status: string}>
     */
    private function foundationComponents(): array
    {
        return [
            [
                'component' => 'PokerSurface',
                'purpose' => 'Base única para cards, painéis e seções com borda, vidro escuro e sombra consistente.',
                'status' => 'ready',
            ],
            [
                'component' => 'PokerSectionHeader',
                'purpose' => 'Cabeçalhos padronizados com eyebrow, título, descrição e ação opcional.',
                'status' => 'ready',
            ],
            [
                'component' => 'PokerBadge',
                'purpose' => 'Badges reutilizáveis para status de sucesso, alerta, informação, neutro, perigo e destaques.',
                'status' => 'ready',
            ],
            [
                'component' => 'PokerButton',
                'purpose' => 'Botões por intenção visual: primário, secundário, alerta, perigo e ghost.',
                'status' => 'ready',
            ],
            [
                'component' => 'PokerEmptyState',
                'purpose' => 'Estado vazio com mensagem, orientação e ação clara para páginas de lobby, torneios e histórico.',
                'status' => 'ready',
            ],
            [
                'component' => 'PokerPageShell',
                'purpose' => 'Base visual para páginas Poker com gradiente, largura máxima e espaçamento responsivo padronizado.',
                'status' => 'ready',
            ],
            [
                'component' => 'PokerPageHero',
                'purpose' => 'Cabeçalho premium reutilizável com título, descrição, navegação e metadados do usuário.',
                'status' => 'ready',
            ],
            [
                'component' => 'PokerFlashMessage',
                'purpose' => 'Mensagens de sucesso/erro com o mesmo padrão visual e sem duplicar classes nas telas.',
                'status' => 'ready',
            ],
            [
                'component' => 'PokerFilterPill',
                'purpose' => 'Pílulas reutilizáveis para filtros e seleções rápidas com foco acessível.',
                'status' => 'ready',
            ],
            [
                'component' => 'PokerResponsiveTable',
                'purpose' => 'Tabela responsiva reutilizável para ranking, histórico, bankroll e páginas com overflow horizontal controlado.',
                'status' => 'ready',
            ],
            [
                'component' => 'PokerInfoGrid',
                'purpose' => 'Grade compacta de resumo para métricas internas sem duplicar marcação em cada tela.',
                'status' => 'ready',
            ],
            [
                'component' => 'PokerTimelineList',
                'purpose' => 'Lista lateral reutilizável para movimentações, histórico recente e eventos do jogo.',
                'status' => 'ready',
            ],

        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function designTokens(): array
    {
        return [
            'surface' => [
                'base' => 'bg-slate-950/75 border-white/10',
                'soft' => 'bg-white/[0.06] border-white/10',
                'highlight' => 'bg-emerald-300/10 border-emerald-200/20',
                'pageShell' => 'gradient + max-width + responsive padding',
            ],
            'buttons' => [
                'primary' => 'bg-emerald-300 text-emerald-950 hover:bg-emerald-200',
                'secondary' => 'bg-white/10 text-white hover:bg-white/20',
                'danger' => 'bg-red-400/15 text-red-100 border-red-300/25',
            ],
            'badges' => [
                'success' => 'emerald',
                'warning' => 'amber',
                'info' => 'cyan',
                'neutral' => 'slate',
            ],
        ];
    }

    /**
     * @return array<int, array{area: string, status: string, label: string}>
     */
    private function checklist(bool $isLocalMode): array
    {
        return [
            [
                'area' => 'buttons',
                'status' => 'mapped',
                'label' => 'Botões serão padronizados por intenção visual: primário, secundário, perigo, navegação e ação de mesa.',
            ],
            [
                'area' => 'cards',
                'status' => 'mapped',
                'label' => 'Cards manterão bordas arredondadas, vidro escuro, hierarquia de título e resumo consistente.',
            ],
            [
                'area' => 'tables',
                'status' => 'pilot-ready',
                'label' => 'Tabelas e listas ganharão cabeçalhos, linhas e células com o mesmo padrão das páginas Poker; Ranking e Bankroll já usam a tabela responsiva piloto.',
            ],
            [
                'area' => 'badges',
                'status' => 'mapped',
                'label' => 'Badges de status serão normalizadas por tom: sucesso, alerta, informação e neutro.',
            ],
            [
                'area' => 'poker-screens',
                'status' => 'protected',
                'label' => 'Lobby, mesa, torneios, bankroll, histórico, ranking e perfil entram na auditoria sem alterar regras; Bankroll já recebeu o padrão visual base.',
            ],
            [
                'area' => 'responsive',
                'status' => 'needs-polish',
                'label' => 'Mobile terá prioridade em grids, painéis sticky, overflow de tabelas e toque em botões.',
            ],
            [
                'area' => 'spacing',
                'status' => 'mapped',
                'label' => 'Espaçamentos serão revisados para reduzir telas carregadas e melhorar leitura em desktop/mobile.',
            ],
            [
                'area' => 'empty-states',
                'status' => 'needs-polish',
                'label' => 'Estados vazios terão mensagem, orientação e ação clara para o próximo passo do usuário.',
            ],
            [
                'area' => 'loading-error',
                'status' => $isLocalMode ? 'local-safe' : 'realtime-safe',
                'label' => $isLocalMode
                    ? 'Loading/error local segue separado de Reverb, timeout e reidratação.'
                    : 'Loading/error multiplayer precisa preservar Reverb, timeout, reidratação e ações em andamento.',
            ],
        ];
    }
}
