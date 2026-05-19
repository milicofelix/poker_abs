<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;

final class PokerPhaseTenHeadsUpAuditService
{
    /**
     * @return array<string, mixed>
     */
    public function forTable(PokerTable $table, bool $localMode): array
    {
        return [
            'phase' => '10.1',
            'title' => 'Auditoria das travas heads-up com contratos 3+ preparados',
            'mode' => $localMode ? 'local' : 'lobby',
            'activeEngine' => 'heads_up',
            'targetEngine' => 'multi_seat',
            'threePlusEnabled' => false,
            'contractStatus' => $table->isMultiSeatCandidate() ? 'preparation_only' : 'heads_up_ready',
            'canCreateThreePlusTables' => true,
            'canSeatThreePlusPlayers' => $table->isMultiSeatCandidate(),
            'declaredMaxPlayers' => $table->declaredMaxPlayers(),
            'currentEngineMaxPlayers' => $table->currentEngineMaxPlayers(),
            'isMultiSeatCandidate' => $table->isMultiSeatCandidate(),
            'summary' => $localMode
                ? 'A mesa local continua como laboratório heads-up para validar regras, histórico e regressões antes do motor 3+.'
                : 'A mesa do lobby já aceita capacidade 3+, entrada e assentos declarados; a rodada oficial ainda precisa sair do modelo player/opponent.',
            'blockers' => $this->blockers(),
            'regressionLocks' => $this->regressionLocks(),
            'safeNextSteps' => $this->safeNextSteps(),
        ];
    }

    /**
     * @return array<int, array{area:string,label:string,status:string,note:string}>
     */
    private function blockers(): array
    {
        return [
            [
                'area' => 'turn-engine',
                'label' => 'Motor de turno',
                'status' => 'heads-up',
                'note' => 'A ordem de ação ainda alterna os papéis canônicos player/opponent; precisa virar fila circular por assento ativo.',
            ],
            [
                'area' => 'betting-state',
                'label' => 'Estado de apostas',
                'status' => 'heads-up',
                'note' => 'Apostas de street, call, raise e fechamento de rodada ainda assumem somente dois participantes.',
            ],
            [
                'area' => 'private-cards',
                'label' => 'Cartas privadas',
                'status' => 'preparado',
                'note' => 'A privacidade atual funciona para duas perspectivas; 3+ exige payload privado por usuário/assento.',
            ],
            [
                'area' => 'bot-automation',
                'label' => 'Bots e timeout',
                'status' => 'heads-up',
                'note' => 'O processamento automático ainda resolve o ator atual; bots extras precisam entrar na mesma fila circular.',
            ],
            [
                'area' => 'showdown',
                'label' => 'Showdown multi-way',
                'status' => 'pendente',
                'note' => 'A comparação final precisa aceitar múltiplos jogadores elegíveis, empates e preparação para side pots.',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function regressionLocks(): array
    {
        return [
            'Não iniciar mão 3+ no motor principal antes do motor circular estar coberto por testes.',
            'Não alterar Bot vs Bot heads-up, timeout automático, nova mão, lobby e mesa privada durante a auditoria.',
            'Manter o contrato atual do frontend aceitando player/opponent até existir adaptador multi-seat completo.',
            'Preservar showdown terminal sem ações habilitadas, conforme hotfix da FASE 9.3.1.',
        ];
    }

    /**
     * @return array<int, array{phase:string,title:string,goal:string}>
     */
    private function safeNextSteps(): array
    {
        return [
            [
                'phase' => '10.2',
                'title' => 'Contratos de mesa 3+',
                'goal' => 'Liberar criação/entrada/assentos 3+ mantendo o motor da mão em heads-up.',
            ],
            [
                'phase' => '10.3',
                'title' => 'Distribuição multi-seat',
                'goal' => 'Distribuir cartas por assento elegível e manter payload privado para cada usuário.',
            ],
            [
                'phase' => '10.4',
                'title' => 'Rodada circular',
                'goal' => 'Substituir player/opponent por fila de assentos com folded, acted, all-in e committed.',
            ],
        ];
    }
}
