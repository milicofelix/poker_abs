<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;

final class PokerPhaseNineActionButtonUxService
{
    /**
     * @return array<string, mixed>
     */
    public function forTable(PokerTable $table, bool $localMode): array
    {
        return [
            'phase' => '9.2',
            'title' => 'Botões de ação revisados',
            'mode' => $localMode ? 'local' : 'lobby',
            'safeToChangeGameplay' => false,
            'summary' => $localMode
                ? 'A mesa local mantém o mesmo fluxo de ações, com botões mais legíveis para testes rápidos.'
                : 'A mesa do lobby mantém o mesmo contrato multiplayer, com botões mais claros para vez, bloqueio e raise.',
            'checklist' => [
                [
                    'area' => 'primary-action',
                    'label' => 'Ação principal destacada',
                    'status' => 'visual',
                    'note' => 'Check ou call continua vindo do estado atual; apenas o destaque visual foi reforçado.',
                ],
                [
                    'area' => 'locked-state',
                    'label' => 'Estado bloqueado mais explicativo',
                    'status' => 'visual',
                    'note' => 'Quando não é a vez do jogador, o painel explica o motivo sem alterar permissões.',
                ],
                [
                    'area' => 'raise-control',
                    'label' => 'Controle de raise mais legível',
                    'status' => 'visual',
                    'note' => 'Slider, valor manual e atalhos rápidos continuam usando os mesmos limites do backend.',
                ],
                [
                    'area' => 'mobile-usability',
                    'label' => 'Ações compactas para mobile',
                    'status' => 'visual',
                    'note' => 'Botões preservam área de toque e reduzem ruído visual em telas menores.',
                ],
                [
                    'area' => 'regression-safety',
                    'label' => 'Sem mudança nas regras da mão',
                    'status' => 'locked',
                    'note' => 'Nenhum valor de aposta, permissão de ação, timer, bot ou showdown foi recalculado nesta fase.',
                ],
            ],
        ];
    }
}
