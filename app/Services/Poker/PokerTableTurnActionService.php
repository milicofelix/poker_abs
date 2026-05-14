<?php

namespace App\Services\Poker;

use App\Domain\Poker\Game\PokerAction;
use App\Domain\Poker\Game\RoundStreet;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class PokerTableTurnActionService
{
    public function __construct(
        private ResolveLocalHandConclusion $resolveConclusion = new ResolveLocalHandConclusion(),
        private ResolveLocalShowdownWinner $resolveShowdownWinner = new ResolveLocalShowdownWinner(),
    ) {
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function execute(PokerTable $table, array $state, ?User $user, string $action, int $raiseAmount = 0): array
    {
        $currentActor = $this->currentActor($state);
        $actor = $this->canonicalActorForUser($table, $user);
        $requiresExplicitOpponentAction = $actor !== null;

        if ($actor !== null && $actor !== $currentActor) {
            throw new AccessDeniedHttpException('Ainda não é a sua vez de agir nesta mesa.');
        }

        return $this->applyAction($state, $actor ?? $currentActor, $action, $raiseAmount, $requiresExplicitOpponentAction);
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function executeAutomatic(array $state, string $action): array
    {
        return $this->applyAction($state, $this->currentActor($state), $action, 0, true);
    }

    public function canonicalActorForUser(PokerTable $table, ?User $user): ?string
    {
        $players = $table->realPlayers()
            ->orderByRaw('seat_number IS NULL')
            ->orderBy('seat_number')
            ->orderBy('joined_at')
            ->orderBy('id')
            ->get();

        if ($players->isEmpty()) {
            return null;
        }

        if (! $user) {
            throw new AccessDeniedHttpException('Você precisa estar autenticado para agir nesta mesa.');
        }

        /** @var PokerTablePlayer|null $currentPlayer */
        $currentPlayer = $players->first(
            static fn (PokerTablePlayer $player): bool => (int) $player->user_id === (int) $user->id,
        );

        if (! $currentPlayer) {
            throw new AccessDeniedHttpException('Você precisa entrar na mesa antes de agir.');
        }

        if ($currentPlayer->seat_number === null) {
            throw new AccessDeniedHttpException('Você precisa escolher um assento antes de agir nesta mesa.');
        }

        if ((int) $currentPlayer->seat_number === 2) {
            return 'opponent';
        }

        if ((int) $currentPlayer->seat_number === 1) {
            return 'player';
        }

        throw new AccessDeniedHttpException('Assento inválido para ação nesta mesa.');
    }

    /**
     * @param array<string, mixed> $state
     */
    private function currentActor(array $state): string
    {
        $actor = (string) data_get($state, 'currentTurn.actor', 'player');

        return $actor === 'opponent' ? 'opponent' : 'player';
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function applyAction(
        array $state,
        string $actor,
        string $action,
        int $raiseAmount,
        bool $requiresExplicitOpponentAction,
    ): array
    {
        $pokerAction = PokerAction::tryFrom($action);

        if (! $pokerAction) {
            throw new InvalidArgumentException('Ação inválida para a rodada.');
        }

        $street = RoundStreet::tryFrom((string) ($state['street'] ?? RoundStreet::PreFlop->value)) ?? RoundStreet::PreFlop;
        $pot = (int) ($state['pot'] ?? 0);
        $currentBet = (int) ($state['currentBet'] ?? 0);
        $minimumRaise = max(10, (int) ($state['minimumRaise'] ?? 20));
        $bigBlind = max(2, (int) ($state['bigBlind'] ?? 20));
        $smallBlind = max(1, (int) ($state['smallBlind'] ?? 10));
        $dealerPosition = max(1, (int) ($state['dealerPosition'] ?? 1));
        $playerStack = (int) ($state['playerStack'] ?? 1000);
        $opponentStack = (int) ($state['opponentStack'] ?? 1000);
        $playerStreetBet = (int) ($state['playerStreetBet'] ?? 0);
        $opponentStreetBet = (int) ($state['opponentStreetBet'] ?? 0);
        $history = $this->normalizeHistory($state['actionHistory'] ?? []);
        $acted = $this->actedThisStreet($state);

        $actorStack = $actor === 'opponent' ? $opponentStack : $playerStack;
        $actorStreetBet = $actor === 'opponent' ? $opponentStreetBet : $playerStreetBet;
        $otherActor = $actor === 'opponent' ? 'player' : 'opponent';
        $amountToCall = max(0, $currentBet - $actorStreetBet);
        $actorAmount = 0;

        if ($pokerAction === PokerAction::Check && $amountToCall > 0) {
            $pokerAction = PokerAction::Call;
        }

        if ($pokerAction === PokerAction::Call) {
            $actorAmount = min($amountToCall, $actorStack);
            $actorStreetBet += $actorAmount;
            $actorStack -= $actorAmount;
            $pot += $actorAmount;
        }

        if ($pokerAction === PokerAction::Raise) {
            $previousBet = $currentBet;
            $minimumTargetBet = $currentBet + $minimumRaise;
            $maximumTargetBet = $actorStreetBet + $actorStack;
            $targetBet = min(max($minimumTargetBet, $raiseAmount), $maximumTargetBet);
            $actorAmount = min(max(0, $targetBet - $actorStreetBet), $actorStack);

            $actorStreetBet += $actorAmount;
            $actorStack -= $actorAmount;
            $pot += $actorAmount;
            $currentBet = max($currentBet, $actorStreetBet);
            $minimumRaise = max(10, $currentBet - $previousBet);
            $acted[$otherActor] = false;
        }

        if ($actor === 'opponent') {
            $opponentStack = $actorStack;
            $opponentStreetBet = $actorStreetBet;
        } else {
            $playerStack = $actorStack;
            $playerStreetBet = $actorStreetBet;
        }

        $acted[$actor] = true;

        $message = $this->messageFor($actor, $pokerAction, $amountToCall);

        $history[] = [
            'street' => $street->label(),
            'action' => $pokerAction->label(),
            'amount' => $actorAmount,
            'message' => $message,
            'pot' => $pot,
            'playerStack' => $playerStack,
            'opponentStack' => $opponentStack,
            'playerStreetBet' => $playerStreetBet,
            'opponentStreetBet' => $opponentStreetBet,
            'actor' => $actor,
        ];

        $nextStreet = $street;
        $conclusion = null;

        if ($pokerAction === PokerAction::Fold) {
            $conclusion = $this->foldConclusion($actor);
        } elseif ($this->streetIsComplete(
            $currentBet,
            $playerStreetBet,
            $opponentStreetBet,
            $acted,
            $requiresExplicitOpponentAction,
        )) {
            $nextStreet = $street->next();

            if ($nextStreet === RoundStreet::Showdown) {
                $winner = $this->resolveShowdownWinner->execute($state);
                $conclusion = $this->resolveConclusion->execute($pokerAction, null, $nextStreet, $winner)->toArray();
            } else {
                $currentBet = 0;
                $playerStreetBet = 0;
                $opponentStreetBet = 0;
                $minimumRaise = $bigBlind;
                $acted = ['player' => false, 'opponent' => false];
            }
        }

        $isFinished = is_array($conclusion) && (bool) ($conclusion['isFinished'] ?? false);
        $nextActor = $isFinished ? null : $this->nextActor($actor);
        $nextAmountToCall = $nextActor
            ? max(0, $currentBet - ($nextActor === 'opponent' ? $opponentStreetBet : $playerStreetBet))
            : 0;

        $state = [
            ...$state,
            'street' => $nextStreet->value,
            'streetLabel' => $nextStreet->label(),
            'pot' => $pot,
            'playerStack' => $playerStack,
            'opponentStack' => $opponentStack,
            'currentBet' => $currentBet,
            'playerStreetBet' => $playerStreetBet,
            'opponentStreetBet' => $opponentStreetBet,
            'amountToCall' => $nextAmountToCall,
            'minimumRaise' => $minimumRaise,
            'minimumRaiseTo' => $currentBet + $minimumRaise,
            'maximumRaiseTo' => $this->maximumRaiseTo($nextActor, $playerStack, $opponentStack, $playerStreetBet, $opponentStreetBet),
            'smallBlind' => $smallBlind,
            'bigBlind' => $bigBlind,
            'dealerPosition' => $dealerPosition,
            'canCheck' => $nextAmountToCall === 0,
            'canCall' => $nextAmountToCall > 0,
            'canRaise' => $nextActor !== null && ($nextActor === 'opponent' ? $opponentStack : $playerStack) > $nextAmountToCall,
            'bettingSummary' => [
                'playerCommitted' => $playerStreetBet,
                'opponentCommitted' => $opponentStreetBet,
                'amountToCall' => $nextAmountToCall,
                'currentBet' => $currentBet,
            ],
            'lastAction' => [
                'type' => $pokerAction->value,
                'label' => $pokerAction->label(),
                'message' => $message,
                'amount' => $actorAmount,
                'actor' => $actor,
            ],
            'actionHistory' => $history,
            'conclusion' => $conclusion,
            'currentTurn' => [
                'actor' => $nextActor,
                'actedThisStreet' => $acted,
                'label' => $nextActor === 'opponent' ? 'Vez do oponente' : ($nextActor === 'player' ? 'Vez do jogador' : 'Mão finalizada'),
            ],
            'isFinished' => $isFinished,
        ];

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @return array{player: bool, opponent: bool}
     */
    private function actedThisStreet(array $state): array
    {
        return [
            'player' => (bool) data_get($state, 'currentTurn.actedThisStreet.player', false),
            'opponent' => (bool) data_get($state, 'currentTurn.actedThisStreet.opponent', false),
        ];
    }

    /**
     * @param array<string, mixed> $acted
     */
    private function streetIsComplete(
        int $currentBet,
        int $playerStreetBet,
        int $opponentStreetBet,
        array $acted,
        bool $requiresExplicitOpponentAction,
    ): bool {
        $betsAreMatched = $playerStreetBet === $opponentStreetBet
            && $playerStreetBet >= $currentBet;

        if (! $betsAreMatched) {
            return false;
        }

        if (! $requiresExplicitOpponentAction) {
            return true;
        }

        return (bool) ($acted['player'] ?? false)
            && (bool) ($acted['opponent'] ?? false);
    }

    private function nextActor(string $actor): string
    {
        return $actor === 'opponent' ? 'player' : 'opponent';
    }

    private function maximumRaiseTo(?string $actor, int $playerStack, int $opponentStack, int $playerStreetBet, int $opponentStreetBet): int
    {
        if ($actor === 'opponent') {
            return $opponentStreetBet + $opponentStack;
        }

        return $playerStreetBet + $playerStack;
    }

    private function messageFor(string $actor, PokerAction $action, int $amountToCall): string
    {
        $subject = $actor === 'opponent' ? 'Oponente' : 'Você';

        return match ($action) {
            PokerAction::Check => $amountToCall > 0
                ? "{$subject} não podia pedir mesa e pagou a aposta pendente."
                : "{$subject} pediu mesa.",
            PokerAction::Call => "{$subject} pagou a aposta.",
            PokerAction::Raise => "{$subject} aumentou a aposta.",
            PokerAction::Fold => "{$subject} desistiu da mão.",
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function foldConclusion(string $foldActor): array
    {
        $winner = $foldActor === 'opponent' ? 'player' : 'opponent';

        return [
            'isFinished' => true,
            'winner' => [
                'player' => $winner,
                'label' => $winner === 'player' ? 'Você' : 'Oponente',
                'handName' => 'Desistência',
            ],
            'message' => $foldActor === 'opponent'
                ? 'Oponente desistiu da mão. Você venceu por fold.'
                : 'Você desistiu da mão. Oponente venceu por fold.',
        ];
    }

    /**
     * @param mixed $history
     * @return array<int, array<string, mixed>>
     */
    private function normalizeHistory(mixed $history): array
    {
        if (! is_array($history)) {
            return [];
        }

        return array_values(array_filter(
            $history,
            static fn (mixed $item): bool => is_array($item),
        ));
    }
}
