<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;

final class PokerPhaseNineVisualAuditService
{
    /**
     * @return array<string, mixed>
     */
    public function forTable(?PokerTable $table, bool $isLocalMode): array
    {
        return [
            'phase' => '9.1',
            'title' => 'Auditoria visual da mesa',
            'summary' => $isLocalMode
                ? 'Modo local auditado para manter o mesmo padrão visual da mesa do lobby sem alterar a engine clássica.'
                : 'Mesa do lobby auditada para preparar polimento visual, responsividade e estados de jogo sem alterar regras.',
            'mode' => $isLocalMode ? 'local' : 'lobby',
            'safeToChangeGameplay' => false,
            'checklist' => $this->checklist($isLocalMode),
            'nextSteps' => [
                '9.2 — Melhorar botões de ação e hierarquia visual do painel de jogadas.',
                '9.3 — Reforçar estados visuais de turno, vencedor, showdown e espera.',
                '9.4 — Adicionar animações leves sem interferir em timer, ações ou reidratação.',
                '9.5 — Revisar responsividade/mobile da mesa e painel sticky.',
            ],
            'guardrails' => [
                'Não alterar cálculo de vencedor, apostas, timer, timeout ou serialização da mão.',
                'Não liberar mesa 3+ jogadores nesta fase visual.',
                'Manter Bot vs Bot, mesa privada, lobby e modo local com o mesmo contrato de estado.',
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
                'area' => 'layout',
                'status' => 'mapped',
                'label' => 'Estrutura principal da mesa identificada para polimento progressivo.',
            ],
            [
                'area' => 'actions',
                'status' => 'mapped',
                'label' => 'Painel de ações separado para melhoria visual sem alterar endpoints.',
            ],
            [
                'area' => 'state',
                'status' => 'mapped',
                'label' => 'Banners de turno, erro, showdown e sincronização preservados.',
            ],
            [
                'area' => 'responsive',
                'status' => 'needs-polish',
                'label' => 'Mobile/sticky actions será refinado nas próximas etapas.',
            ],
            [
                'area' => 'multiplayer',
                'status' => $isLocalMode ? 'lobby-only' : 'protected',
                'label' => $isLocalMode
                    ? 'Presença, assentos e bots continuam concentrados no lobby multiplayer.'
                    : 'Presença, assentos, Bot vs Bot e mesa privada seguem protegidos contra regressão visual.',
            ],
        ];
    }
}
