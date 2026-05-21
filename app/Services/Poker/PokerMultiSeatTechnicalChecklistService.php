<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;

final class PokerMultiSeatTechnicalChecklistService
{
    /**
     * @return array<string, mixed>
     */
    public function execute(PokerTable $table): array
    {
        $isCandidate = $table->isMultiSeatCandidate();

        return [
            'phase' => '8.5.8',
            'title' => 'Checklist técnico multi-seat',
            'engineStatus' => [
                'activeEngine' => 'heads_up',
                'futureEngine' => 'multi_seat',
                'multiSeatEnabled' => false,
                'declaredMaxPlayers' => $table->declaredMaxPlayers(),
                'currentEngineMaxPlayers' => $table->currentEngineMaxPlayers(),
                'isMultiSeatCandidate' => $isCandidate,
            ],
            'stableFlows' => [
                'lobby real de mesas',
                'criação e entrada em mesa',
                'mesa privada por convite',
                'presença visual em tempo real',
                'Bot vs Bot em modo heads-up',
                'contratos heads-up e multi-seat separados',
            ],
            'headsUpDependencies' => $this->headsUpDependencies(),
            'requiredBeforeEnablingThreePlus' => $this->requiredBeforeEnablingThreePlus(),
            'recommendation' => $isCandidate
                ? 'Mesa já declara mais de 2 lugares, mas deve continuar executando pelo motor heads-up até a FASE 9.'
                : 'Mesa permanece no fluxo heads-up atual; nenhuma ativação 3+ deve ocorrer agora.',
        ];
    }

    /**
     * @return array<int, array{area:string, reason:string, risk:string}>
     */
    private function headsUpDependencies(): array
    {
        return [
            [
                'area' => 'PokerTableTurnActionService',
                'reason' => 'Alternância de turno ainda trabalha com os atores canônicos player/opponent.',
                'risk' => 'Ativar 3+ aqui antes da FASE 9 quebraria ordem de ação, apostas e fechamento da street.',
            ],
            [
                'area' => 'PokerTurnTimeoutService e PokerBotTurnProcessor',
                'reason' => 'Timeout automático ainda resolve somente o ator atual no contrato heads-up.',
                'risk' => 'Bots extras poderiam ficar sem ação automática ou agir fora da vez.',
            ],
            [
                'area' => 'MultiplayerPokerPrivateStateService',
                'reason' => 'Privacidade das cartas já protege usuário atual, mas ainda remapeia visualmente player/opponent.',
                'risk' => 'Com 3+ jogadores, cada assento precisará de visão própria sem reaproveitar apenas dois papéis.',
            ],
            [
                'area' => 'Ranking, estatísticas e histórico local',
                'reason' => 'Algumas agregações ainda nomeiam player/opponent para compatibilidade com mãos antigas.',
                'risk' => 'Múltiplos vencedores, side pots e métricas por assento precisam de modelo próprio.',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function requiredBeforeEnablingThreePlus(): array
    {
        return [
            'Criar motor de rodada baseado em coleção de assentos ativos, não em player/opponent.',
            'Persistir apostas por assento e controlar acted/folded/all-in por jogador.',
            'Implementar dealer button rotativo, small blind/big blind e ordem pré-flop/pós-flop para 3+.',
            'Adicionar resolução de showdown com múltiplos jogadores elegíveis.',
            'Separar payload privado por usuário para cartas próprias e cartas públicas.',
            'Adaptar bots para decidir por assento e mesa com mais de um adversário.',
            'Criar testes de regressão garantindo que heads-up, Bot vs Bot, lobby e mesa privada não mudaram.',
        ];
    }
}
