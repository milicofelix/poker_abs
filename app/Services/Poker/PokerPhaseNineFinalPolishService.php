<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;

final class PokerPhaseNineFinalPolishService
{
    /**
     * @return array<string, mixed>
     */
    public function forTable(PokerTable $table, bool $localMode): array
    {
        return [
            'phase' => '9.6',
            'title' => 'Polimento final da experiência da mesa',
            'mode' => $localMode ? 'local' : 'lobby',
            'safeToChangeGameplay' => false,
            'summary' => $localMode
                ? 'Fechamento visual da FASE 9 na mesa local, mantendo engine, sessão, histórico, ranking e estatísticas sem alteração de regra.'
                : 'Fechamento visual da FASE 9 no multiplayer atual, preservando assentos, presença, Bot vs Bot, timer, showdown e ações existentes.',
            'nextPhase' => [
                'phase' => '10',
                'title' => 'Ativação real do multiplayer 3+ jogadores',
                'note' => 'A FASE 9 fecha apenas UX/perfumaria. A liberação real de mesas com mais de 2 jogadores fica para a FASE 10.',
            ],
            'checklist' => [
                [
                    'area' => 'bot-vs-bot-readability',
                    'label' => 'Bot vs Bot legível',
                    'status' => 'visual',
                    'note' => 'Cartas abertas, indicador de pensamento e mensagens automáticas ficam claros para acompanhar simulações.',
                ],
                [
                    'area' => 'multiplayer-current-contract',
                    'label' => 'Multiplayer atual preservado',
                    'status' => 'locked',
                    'note' => 'A experiência continua limitada ao contrato atual; 3+ jogadores será ativado somente na próxima fase.',
                ],
                [
                    'area' => 'terminal-state-guard',
                    'label' => 'Showdown sem ações',
                    'status' => 'locked',
                    'note' => 'Estados finais seguem sem botões de ação, sem timer ativo e com CTA explícito para nova mão.',
                ],
                [
                    'area' => 'desktop-mobile-consistency',
                    'label' => 'Desktop e mobile consistentes',
                    'status' => 'visual',
                    'note' => 'Painéis, mesa, ações sticky e leitura lateral usam a mesma narrativa visual em tamanhos diferentes.',
                ],
                [
                    'area' => 'phase-nine-closure',
                    'label' => 'FASE 9 fechada',
                    'status' => 'done',
                    'note' => 'Auditoria, botões, estados, animações, responsividade e polimento final documentados.',
                ],
            ],
        ];
    }
}
