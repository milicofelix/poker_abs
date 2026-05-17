<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;

final class PokerPhaseEightClosureService
{
    /**
     * @return array<string, mixed>
     */
    public function forLocalTable(PokerTable $table): array
    {
        return [
            'phase' => '8.6',
            'title' => 'Fechamento da FASE 8',
            'summary' => 'Fluxo local mantido como base clássica para validação rápida, histórico, ranking e estatísticas.',
            'engineMode' => 'local_heads_up',
            'threePlusEnabled' => false,
            'nextPhase' => 'FASE 9 — motor multi-seat real',
            'checklist' => [
                ['label' => 'Fluxo de nova mão local', 'status' => 'ok'],
                ['label' => 'Histórico, ranking e estatísticas', 'status' => 'ok'],
                ['label' => 'Layout alinhado com mesa do lobby', 'status' => 'ok'],
                ['label' => 'Showdown e hierarquia de mãos blindados', 'status' => 'ok'],
                ['label' => 'Assentos e presença em tempo real', 'status' => 'lobby'],
                ['label' => 'Bots trocáveis e timeout automático', 'status' => 'lobby'],
            ],
            'lockedDecisions' => $this->lockedDecisions(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forLobbyTable(PokerTable $table): array
    {
        return [
            'phase' => '8.6',
            'title' => 'Fechamento da FASE 8',
            'summary' => 'Mesa multiplayer validada com lobby, assentos, presença, mesa privada, Bot vs Bot, timeout e contratos internos.',
            'engineMode' => $table->isMultiSeatCandidate() ? 'heads_up_with_multi_seat_contract' : 'heads_up',
            'threePlusEnabled' => false,
            'nextPhase' => 'FASE 9 — motor multi-seat real',
            'checklist' => [
                ['label' => 'Assentos e presença dos jogadores', 'status' => 'ok'],
                ['label' => 'Bot vs Bot e troca de adversário', 'status' => 'ok'],
                ['label' => 'Timer e timeout automático', 'status' => 'ok'],
                ['label' => 'Nova mão sem refresh manual', 'status' => 'ok'],
                ['label' => 'Showdown e hierarquia de mãos blindados', 'status' => 'ok'],
                ['label' => 'Histórico, ranking e estatísticas', 'status' => 'ok'],
            ],
            'phaseChecklist' => [
                ['label' => 'Lobby real, criação e entrada em mesa', 'status' => 'ok'],
                ['label' => 'Mesa privada por convite', 'status' => 'ok'],
                ['label' => 'Contratos heads-up e multi-seat separados', 'status' => 'ok'],
                ['label' => '3+ jogadores ainda bloqueado por segurança', 'status' => 'warning'],
            ],
            'lockedDecisions' => $this->lockedDecisions(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function lockedDecisions(): array
    {
        return [
            'O motor heads-up continua sendo a fonte oficial de ações, showdown, timer e timeout.',
            'O contrato multi-seat permanece preparatório e não ativa mesa com 3+ jogadores nesta fase.',
            'Lobby, mesa privada, Bot vs Bot, presença e reidratação devem continuar sem mudança visual obrigatória.',
            'A FASE 9 deve começar pelo motor baseado em coleção de assentos ativos antes de liberar 3+ jogadores.',
        ];
    }
}
