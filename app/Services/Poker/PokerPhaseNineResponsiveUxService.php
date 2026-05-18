<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;

final class PokerPhaseNineResponsiveUxService
{
    /**
     * @return array<string, mixed>
     */
    public function forTable(PokerTable $table, bool $localMode): array
    {
        return [
            'phase' => '9.5',
            'title' => 'Responsividade e leitura mobile da mesa',
            'mode' => $localMode ? 'local' : 'lobby',
            'safeToChangeGameplay' => false,
            'summary' => $localMode
                ? 'A mesa local recebeu ajustes de densidade, rolagem horizontal controlada e painel sticky mais confortável em telas pequenas.'
                : 'A mesa multiplayer recebeu ajustes mobile preservando assentos, presença, bots, timer, Reverb e ações existentes.',
            'checklist' => [
                [
                    'area' => 'safe-area',
                    'label' => 'Área segura no rodapé',
                    'status' => 'visual',
                    'note' => 'O painel de ações respeita o safe-area do aparelho e evita ficar colado na borda inferior.',
                ],
                [
                    'area' => 'compact-table',
                    'label' => 'Mesa compacta em celulares',
                    'status' => 'visual',
                    'note' => 'Cartas, pote e blocos laterais ficam mais densos sem remover informações do jogo.',
                ],
                [
                    'area' => 'horizontal-cards',
                    'label' => 'Cartas com rolagem previsível',
                    'status' => 'visual',
                    'note' => 'Linhas de cartas mantêm leitura horizontal e reduzem quebra visual em telas estreitas.',
                ],
                [
                    'area' => 'mobile-actions',
                    'label' => 'Ações sticky revisadas',
                    'status' => 'locked',
                    'note' => 'O painel mobile continua usando os mesmos booleans canAct, canCall, canRaise e estado terminal.',
                ],
                [
                    'area' => 'desktop-preserved',
                    'label' => 'Desktop preservado',
                    'status' => 'locked',
                    'note' => 'Os ajustes são progressivos e mantêm a experiência lateral do desktop intacta.',
                ],
            ],
        ];
    }
}
