<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;

final class PokerPhaseNineMotionUxService
{
    /**
     * @return array<string, mixed>
     */
    public function forTable(PokerTable $table, bool $localMode): array
    {
        return [
            'phase' => '9.4',
            'title' => 'Animações leves da mesa',
            'mode' => $localMode ? 'local' : 'lobby',
            'safeToChangeGameplay' => false,
            'summary' => $localMode
                ? 'Microinterações aplicadas à mesa local para melhorar leitura visual sem tocar na engine.'
                : 'Microinterações aplicadas à mesa multiplayer preservando contratos de assentos, presença e tempo real.',
            'checklist' => [
                [
                    'area' => 'card-motion',
                    'label' => 'Cartas com entrada suave',
                    'status' => 'visual',
                    'note' => 'A distribuição continua recebendo as mesmas cartas; apenas a entrada visual fica mais natural.',
                ],
                [
                    'area' => 'pot-motion',
                    'label' => 'Pote com pulso discreto',
                    'status' => 'visual',
                    'note' => 'O valor do pote não é recalculado no frontend; a animação só chama atenção para o centro da mesa.',
                ],
                [
                    'area' => 'turn-motion',
                    'label' => 'Turno atual com brilho controlado',
                    'status' => 'visual',
                    'note' => 'O indicador de turno segue usando o estado vindo do backend.',
                ],
                [
                    'area' => 'result-motion',
                    'label' => 'Resultado com entrada destacada',
                    'status' => 'visual',
                    'note' => 'Showdown e vencedor continuam definidos pelos serviços já existentes.',
                ],
                [
                    'area' => 'accessibility',
                    'label' => 'Respeito a redução de movimento',
                    'status' => 'locked',
                    'note' => 'As animações são desativadas para usuários com prefers-reduced-motion.',
                ],
            ],
        ];
    }
}
