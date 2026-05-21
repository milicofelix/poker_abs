<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;

final class PokerPhaseNineStateFeedbackService
{
    /**
     * @return array<string, mixed>
     */
    public function forTable(PokerTable $table, bool $isLocalMode): array
    {
        return [
            'phase' => '9.3',
            'mode' => $isLocalMode ? 'local' : 'lobby',
            'safeToChangeGameplay' => false,
            'summary' => $isLocalMode
                ? 'Estados visuais para turno, vencedor e showdown na mesa local, sem alterar engine ou ações.'
                : 'Estados visuais para turno, vencedor e showdown na mesa multiplayer, preservando contratos de assentos, presença e tempo real.',
            'checklist' => [
                [
                    'area' => 'turn-visibility',
                    'label' => 'Turno atual em destaque visual',
                    'status' => 'ok',
                    'description' => 'A mesa mostra quem deve agir sem depender apenas dos botões habilitados.',
                ],
                [
                    'area' => 'waiting-state',
                    'label' => 'Estado de espera mais claro',
                    'status' => 'ok',
                    'description' => 'Quando não é a vez do jogador, a tela reforça que a mesa aguarda outro participante ou bot.',
                ],
                [
                    'area' => 'showdown-state',
                    'label' => 'Showdown com leitura rápida',
                    'status' => 'ok',
                    'description' => 'O encerramento da mão ganha resumo próprio para reduzir dúvida após river/showdown.',
                ],
                [
                    'area' => 'winner-feedback',
                    'label' => 'Vencedor e empate destacados',
                    'status' => 'ok',
                    'description' => 'Resultado final fica separado do histórico de ações e visível antes da próxima mão.',
                ],
                [
                    'area' => 'regression-safety',
                    'label' => 'Sem alteração de regra de jogo',
                    'status' => 'locked',
                    'description' => 'A fase 9.3 altera somente feedback visual; apostas, timer, showdown e persistência continuam intactos.',
                ],
            ],
        ];
    }
}
