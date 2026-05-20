<?php

namespace App\Services\Poker;

use App\Domain\Poker\Cards\Card;
use App\Domain\Poker\Cards\Rank;
use App\Domain\Poker\Cards\Suit;
use App\Domain\Poker\Game\PokerAction;
use App\Domain\Poker\Hands\HandEvaluator;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class PokerMultiSeatTurnActionService
{
    public function __construct(
        private HandEvaluator $handEvaluator,
    ) {
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function execute(PokerTable $table, array $state, ?User $user, string $action, int $raiseAmount = 0): array
    {
        if (! (bool) data_get($state, 'multiSeat.enabled', false)) {
            return $state;
        }

        if (! $user) {
            throw new AccessDeniedHttpException('Você precisa estar autenticado para agir nesta mesa.');
        }

        /** @var PokerTablePlayer|null $tablePlayer */
        $tablePlayer = $table->realPlayers()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (! $tablePlayer || $tablePlayer->seat_number === null) {
            throw new AccessDeniedHttpException('Você precisa estar sentado para agir nesta mesa.');
        }

        $currentSeat = (int) data_get($state, 'multiSeat.currentSeat', data_get($state, 'currentTurn.seatNumber', 0));

        if ((int) $tablePlayer->seat_number !== $currentSeat) {
            throw new AccessDeniedHttpException('Ainda não é a sua vez de agir nesta mesa.');
        }

        return $this->applySeatAction($state, $currentSeat, $action, $raiseAmount);
    }

    /**
     * Executa uma ação automática no assento atual da mesa multi-seat.
     *
     * Usado pelo timeout para não cair no motor heads-up antigo, que só conhece
     * os atores player/opponent e corrompe o ciclo quando existem 3+ assentos.
     *
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function executeAutomatic(array $state, string $action, int $raiseAmount = 0): array
    {
        if (! (bool) data_get($state, 'multiSeat.enabled', false)) {
            return $state;
        }

        $currentSeat = (int) data_get($state, 'multiSeat.currentSeat', data_get($state, 'currentTurn.seatNumber', 0));

        if ($currentSeat <= 0) {
            return $state;
        }

        return $this->applySeatAction($state, $currentSeat, $action, $raiseAmount);
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function applySeatAction(array $state, int $currentSeat, string $action, int $raiseAmount = 0): array
    {
        $pokerAction = PokerAction::tryFrom($action);

        if (! $pokerAction) {
            throw new InvalidArgumentException('Ação inválida para a rodada.');
        }

        $players = $this->players($state);
        $actorIndex = $this->playerIndexBySeat($players, $currentSeat);

        if ($actorIndex === null) {
            throw new AccessDeniedHttpException('Assento atual não está ativo na mão.');
        }

        $currentBet = (int) ($state['currentBet'] ?? 0);
        $minimumRaise = max((int) ($state['bigBlind'] ?? 20), (int) ($state['minimumRaise'] ?? 20));
        $actor = $players[$actorIndex];
        $actorStreetBet = (int) ($actor['streetBet'] ?? 0);
        $actorStack = (int) ($actor['stack'] ?? 0);
        $amountToCall = max(0, $currentBet - $actorStreetBet);
        $actorAmount = 0;

        if ($pokerAction === PokerAction::Check && $amountToCall > 0) {
            $pokerAction = PokerAction::Call;
        }

        if ($pokerAction === PokerAction::Fold) {
            $actor['hasFolded'] = true;
            $actor['status'] = 'folded';
            $actor['hasActed'] = true;
        }

        if ($pokerAction === PokerAction::Call) {
            $actorAmount = min($amountToCall, $actorStack);
            $actorStreetBet += $actorAmount;
            $actorStack -= $actorAmount;
            $actor['streetBet'] = $actorStreetBet;
            $actor['stack'] = $actorStack;
            $actor['hasActed'] = true;
            $actor['isAllIn'] = $actorStack <= 0;
            $state['pot'] = (int) ($state['pot'] ?? 0) + $actorAmount;
        }

        if ($pokerAction === PokerAction::Raise) {
            $previousBet = $currentBet;
            $maximumTargetBet = $actorStreetBet + $actorStack;
            $minimumTargetBet = $currentBet + $minimumRaise;
            $requestedTargetBet = $raiseAmount > 0 ? $raiseAmount : $minimumTargetBet;
            $targetBet = min(max($minimumTargetBet, $requestedTargetBet), $maximumTargetBet);
            $isFullRaise = $targetBet >= $minimumTargetBet;
            $actorAmount = max(0, $targetBet - $actorStreetBet);
            $actorStreetBet += $actorAmount;
            $actorStack -= $actorAmount;
            $actor['streetBet'] = $actorStreetBet;
            $actor['stack'] = $actorStack;
            $actor['hasActed'] = true;
            $actor['isAllIn'] = $actorStack <= 0;
            $state['pot'] = (int) ($state['pot'] ?? 0) + $actorAmount;
            $currentBet = max($currentBet, $actorStreetBet);

            if ($isFullRaise && $currentBet > $previousBet) {
                $minimumRaise = max((int) ($state['bigBlind'] ?? 20), $currentBet - $previousBet);

                foreach ($players as $index => $player) {
                    if ($index !== $actorIndex && ! (bool) ($player['hasFolded'] ?? false) && (int) ($player['stack'] ?? 0) > 0) {
                        $players[$index]['hasActed'] = false;
                    }
                }
            }
        }

        if ($pokerAction === PokerAction::Check) {
            $actor['hasActed'] = true;
        }

        if ($actorAmount > 0) {
            $contribution = $this->registerSeatContribution(
                state: $state,
                actor: $actor,
                currentSeat: $currentSeat,
                amount: $actorAmount,
            );
            $state = $contribution['state'];
            $actor = $contribution['actor'];
        }

        $players[$actorIndex] = $actor;
        $activePlayers = $this->activePlayers($players);
        $isFinished = count($activePlayers) <= 1;
        $winner = $activePlayers[0] ?? null;
        $streetClosed = ! $isFinished && $this->streetCanClose($activePlayers, $currentBet);

        if ($streetClosed) {
            $state = $this->advanceStreet($state);
            $players = $this->resetStreetBets($players);
            $currentBet = 0;
            $minimumRaise = (int) ($state['bigBlind'] ?? 20);

            if ((string) ($state['street'] ?? '') === 'showdown') {
                $isFinished = true;
                $winner = $this->resolveShowdownWinner($activePlayers, $state);
            }
        }

        $nextSeat = $isFinished ? null : $this->nextSeatAfterStreetResolution($players, $currentSeat, $streetClosed);
        $history = is_array($state['actionHistory'] ?? null) ? $state['actionHistory'] : [];
        $history[] = [
            'street' => (string) ($state['streetLabel'] ?? 'Pré-flop'),
            'action' => $pokerAction->label(),
            'amount' => $actorAmount,
            'message' => $this->messageFor($actor, $pokerAction),
            'pot' => (int) ($state['pot'] ?? 0),
            'actor' => 'seat:'.$currentSeat,
            'seatNumber' => $currentSeat,
            'tablePlayerId' => (int) ($actor['tablePlayerId'] ?? 0),
        ];

        $state['currentBet'] = $currentBet;
        $state['minimumRaise'] = $minimumRaise;
        $state['minimumRaiseTo'] = $currentBet + $minimumRaise;
        $state['amountToCall'] = 0;
        $state['actionHistory'] = $history;
        $state['lastAction'] = [
            'type' => $pokerAction->value,
            'label' => $pokerAction->label(),
            'message' => $this->messageFor($actor, $pokerAction),
            'amount' => $actorAmount,
            'actor' => 'seat:'.$currentSeat,
            'seatNumber' => $currentSeat,
        ];
        $state['multiSeat']['players'] = $players;
        $state['multiSeat']['botEnginePhase'] = '10.14';
        $state['multiSeat']['botDecisionContexts'] = (new PokerMultiSeatBotDecisionContextService())->forState($state);
        $state['multiSeat']['currentSeat'] = $nextSeat;
        $state['multiSeat']['lastStreetClosed'] = $streetClosed;
        $state['multiSeat']['streetClosurePhase'] = '10.10';
        $state['multiSeat']['bettingEnginePhase'] = '10.13';
        $state['multiSeat']['lastBettingAction'] = [
            'seatNumber' => $currentSeat,
            'action' => $pokerAction->value,
            'amountToCallBeforeAction' => $amountToCall,
            'amountCommitted' => $actorAmount,
            'handContributionAfterAction' => (int) ($actor['handContribution'] ?? 0),
            'currentBetAfterAction' => $currentBet,
            'minimumRaiseAfterAction' => $minimumRaise,
            'isAllIn' => (bool) ($actor['isAllIn'] ?? false),
        ];
        $showdownFinished = $isFinished && (string) ($state['street'] ?? '') === 'showdown';
        $state['multiSeat']['showdownResolutionPhase'] = $showdownFinished ? '10.12' : null;
        $state['multiSeat']['showdownEvaluator'] = $showdownFinished ? 'real_hand_evaluator' : null;
        $state['multiSeat']['winnerSeats'] = $isFinished && is_array($winner)
            ? (array) ($winner['winnerSeats'] ?? [(int) ($winner['seatNumber'] ?? 0)])
            : [];
        $state['currentTurn'] = [
            'actor' => $isFinished ? null : 'seat:'.$nextSeat,
            'seatNumber' => $isFinished ? null : $nextSeat,
            'actedThisStreet' => [],
            'label' => $isFinished ? 'Mão finalizada' : 'Vez do assento '.$nextSeat,
            'message' => $streetClosed && ! $isFinished
                ? 'Rodada concluída; próxima street iniciada.'
                : null,
        ];
        $state['isFinished'] = $isFinished;
        $state['conclusion'] = $isFinished && is_array($winner)
            ? [
                'isFinished' => true,
                'winner' => [
                    'player' => 'seat:'.((int) ($winner['seatNumber'] ?? 0)),
                    'seatNumber' => (int) ($winner['seatNumber'] ?? 0),
                    'label' => (string) ($winner['nickname'] ?? 'Jogador'),
                    'handName' => (string) ($state['street'] ?? '') === 'showdown'
                        ? (string) data_get($winner, 'showdownHand.name', 'Showdown multi-seat')
                        : 'Desistência',
                ],
                'message' => (string) ($state['street'] ?? '') === 'showdown'
                    ? ((string) ($winner['nickname'] ?? 'Jogador')).' venceu o showdown multi-seat com '.((string) data_get($winner, 'showdownHand.name', 'mão avaliada')).'.'
                    : ((string) ($winner['nickname'] ?? 'Jogador')).' venceu por fold na mesa multi-seat.',
            ]
            : null;

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<int, array<string, mixed>>
     */
    private function players(array $state): array
    {
        $players = data_get($state, 'multiSeat.players', []);

        return is_array($players)
            ? array_values(array_filter($players, static fn (mixed $player): bool => is_array($player)))
            : [];
    }


    /**
     * @param array<int, array<string, mixed>> $players
     * @return array<int, array<string, mixed>>
     */
    private function activePlayers(array $players): array
    {
        return array_values(array_filter(
            $players,
            static fn (array $player): bool => ! (bool) ($player['hasFolded'] ?? false),
        ));
    }

    /**
     * Registra contribuição acumulada da mão por assento.
     *
     * Esta estrutura não resolve side pots ainda, mas deixa o estado preparado
     * para a próxima fase calcular potes principal/laterais com base no total
     * que cada assento colocou na mão, sem depender só do streetBet atual.
     *
     * @param array<string, mixed> $state
     * @param array<string, mixed> $actor
     * @return array{state: array<string, mixed>, actor: array<string, mixed>}
     */
    private function registerSeatContribution(array $state, array $actor, int $currentSeat, int $amount): array
    {
        $street = (string) ($state['street'] ?? 'pre_flop');
        $seatKey = (string) $currentSeat;
        $tablePlayerId = (int) ($actor['tablePlayerId'] ?? 0);
        $previousActorContribution = (int) ($actor['handContribution'] ?? $actor['totalCommitted'] ?? 0);
        $actorContribution = $previousActorContribution + $amount;

        $actor['handContribution'] = $actorContribution;
        $actor['totalCommitted'] = $actorContribution;

        $contributions = is_array(data_get($state, 'multiSeat.contributions'))
            ? (array) data_get($state, 'multiSeat.contributions')
            : [];

        $seats = is_array($contributions['seats'] ?? null) ? $contributions['seats'] : [];
        $seatContribution = is_array($seats[$seatKey] ?? null) ? $seats[$seatKey] : [];
        $byStreet = is_array($seatContribution['byStreet'] ?? null) ? $seatContribution['byStreet'] : [];

        $byStreet[$street] = (int) ($byStreet[$street] ?? 0) + $amount;

        $seats[$seatKey] = [
            'seatNumber' => $currentSeat,
            'tablePlayerId' => $tablePlayerId,
            'nickname' => (string) ($actor['nickname'] ?? 'Jogador'),
            'total' => (int) ($seatContribution['total'] ?? 0) + $amount,
            'byStreet' => $byStreet,
            'lastAmount' => $amount,
        ];

        $contributions['phase'] = '12.4';
        $contributions['description'] = 'Contribuições por assento com prévia de side pots.';
        $contributions['sidePotReady'] = true;
        $contributions['totalPotTracked'] = (int) ($contributions['totalPotTracked'] ?? 0) + $amount;
        $contributions['seats'] = $seats;

        $state['multiSeat']['contributions'] = $contributions;
        $state['multiSeat']['sidePots'] = $this->buildSidePotPreview($state);

        return [
            'state' => $state,
            'actor' => $actor,
        ];
    }

    /**
     * Monta uma prévia determinística dos potes principal/laterais com base
     * no total contribuído por assento. A liquidação financeira usa uma rotina
     * equivalente na persistência, para evitar depender do frontend.
     *
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function buildSidePotPreview(array $state): array
    {
        $contributionSeats = (array) data_get($state, 'multiSeat.contributions.seats', []);
        $players = $this->players($state);
        $foldedSeats = [];

        foreach ($players as $player) {
            if ((bool) ($player['hasFolded'] ?? false)) {
                $foldedSeats[] = (int) ($player['seatNumber'] ?? 0);
            }
        }

        $totals = [];

        foreach ($contributionSeats as $seatKey => $seatContribution) {
            if (! is_array($seatContribution)) {
                continue;
            }

            $seatNumber = (int) ($seatContribution['seatNumber'] ?? $seatKey);
            $total = max(0, (int) ($seatContribution['total'] ?? 0));

            if ($seatNumber > 0 && $total > 0) {
                $totals[$seatNumber] = $total;
            }
        }

        $levels = array_values(array_unique(array_values($totals)));
        sort($levels);

        $pots = [];
        $previousLevel = 0;

        foreach ($levels as $level) {
            $contributors = array_values(array_keys(array_filter(
                $totals,
                static fn (int $total): bool => $total >= $level,
            )));

            $amount = ($level - $previousLevel) * count($contributors);

            if ($amount <= 0) {
                $previousLevel = $level;
                continue;
            }

            $eligibleSeats = array_values(array_filter(
                $contributors,
                static fn (int $seatNumber): bool => ! in_array($seatNumber, $foldedSeats, true),
            ));

            sort($contributors);
            sort($eligibleSeats);

            $pots[] = [
                'type' => $pots === [] ? 'main' : 'side',
                'amount' => $amount,
                'cap' => $level,
                'contributors' => $contributors,
                'eligibleSeats' => $eligibleSeats,
            ];

            $previousLevel = $level;
        }

        return [
            'phase' => '12.4',
            'ready' => count($pots) > 0,
            'hasSidePot' => count($pots) > 1,
            'pots' => $pots,
            'total' => array_sum(array_map(static fn (array $pot): int => (int) $pot['amount'], $pots)),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $activePlayers
     */
    private function streetCanClose(array $activePlayers, int $currentBet): bool
    {
        if (count($activePlayers) < 2) {
            return false;
        }

        foreach ($activePlayers as $player) {
            $hasActed = (bool) ($player['hasActed'] ?? false);
            $streetBet = (int) ($player['streetBet'] ?? 0);
            $stack = (int) ($player['stack'] ?? 0);

            if (! $hasActed && $stack > 0) {
                return false;
            }

            if ($stack > 0 && $streetBet !== $currentBet) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function advanceStreet(array $state): array
    {
        $nextStreet = match ((string) ($state['street'] ?? 'pre_flop')) {
            'pre_flop' => ['key' => 'flop', 'label' => 'Flop'],
            'flop' => ['key' => 'turn', 'label' => 'Turn'],
            'turn' => ['key' => 'river', 'label' => 'River'],
            default => ['key' => 'showdown', 'label' => 'Showdown'],
        };

        $state['street'] = $nextStreet['key'];
        $state['streetLabel'] = $nextStreet['label'];

        return $state;
    }

    /**
     * @param array<int, array<string, mixed>> $players
     * @return array<int, array<string, mixed>>
     */
    private function resetStreetBets(array $players): array
    {
        foreach ($players as $index => $player) {
            if (! (bool) ($player['hasFolded'] ?? false)) {
                $players[$index]['streetBet'] = 0;
                $players[$index]['hasActed'] = (int) ($player['stack'] ?? 0) <= 0;
                $players[$index]['isAllIn'] = (int) ($player['stack'] ?? 0) <= 0;
            }
        }

        return $players;
    }

    /**
     * @param array<int, array<string, mixed>> $players
     */
    private function nextSeatAfterStreetResolution(array $players, int $currentSeat, bool $streetClosed): ?int
    {
        if (! $streetClosed) {
            return $this->nextActiveSeat($players, $currentSeat);
        }

        $dealerSeat = 1;

        return $this->nextActiveSeat($players, $dealerSeat - 1);
    }


    /**
     * Resolve o showdown real da FASE 10.12 reutilizando o avaliador de mãos já
     * existente no domínio. O desempate considera ranking, kickers e, apenas em
     * empate absoluto, mantém o menor assento como vencedor principal para que o
     * contrato continue determinístico.
     *
     * @param array<int, array<string, mixed>> $activePlayers
     * @param array<string, mixed> $state
     * @return array<string, mixed>|null
     */
    private function resolveShowdownWinner(array $activePlayers, array $state): ?array
    {
        if ($activePlayers === []) {
            return null;
        }

        $rankedPlayers = [];

        foreach ($activePlayers as $player) {
            $showdownHand = $this->showdownHandFor($player, $state);

            if ($showdownHand === null) {
                continue;
            }

            $player['showdownHand'] = $showdownHand;
            $rankedPlayers[] = $player;
        }

        if ($rankedPlayers === []) {
            usort($activePlayers, static function (array $left, array $right): int {
                return (int) ($left['seatNumber'] ?? 0) <=> (int) ($right['seatNumber'] ?? 0);
            });

            $activePlayers[0]['winnerSeats'] = [(int) ($activePlayers[0]['seatNumber'] ?? 0)];

            return $activePlayers[0];
        }

        usort($rankedPlayers, function (array $left, array $right): int {
            $comparison = $this->compareShowdownHands(
                (array) ($left['showdownHand'] ?? []),
                (array) ($right['showdownHand'] ?? []),
            );

            if ($comparison !== 0) {
                return -$comparison;
            }

            return (int) ($left['seatNumber'] ?? 0) <=> (int) ($right['seatNumber'] ?? 0);
        });

        $winner = $rankedPlayers[0];
        $winner['winnerSeats'] = array_values(array_map(
            static fn (array $player): int => (int) ($player['seatNumber'] ?? 0),
            array_filter($rankedPlayers, function (array $player) use ($winner): bool {
                return $this->compareShowdownHands(
                    (array) ($player['showdownHand'] ?? []),
                    (array) ($winner['showdownHand'] ?? []),
                ) === 0;
            }),
        ));

        return $winner;
    }

    /**
     * @param array<string, mixed> $player
     * @param array<string, mixed> $state
     * @return array{name: string, rank: int, kickers: array<int, int>, cards: array<int, array<string, string>>}|null
     */
    private function showdownHandFor(array $player, array $state): ?array
    {
        $bestHand = $player['bestHand'] ?? null;

        if (is_array($bestHand) && isset($bestHand['rank'])) {
            return [
                'name' => (string) ($bestHand['name'] ?? 'Mão avaliada'),
                'rank' => (int) ($bestHand['rank'] ?? 0),
                'kickers' => array_values(array_map('intval', (array) ($bestHand['kickers'] ?? []))),
                'cards' => (array) ($bestHand['cards'] ?? []),
            ];
        }

        $cards = [
            ...$this->deserializeCards((array) ($player['cards'] ?? [])),
            ...$this->deserializeCards((array) ($state['communityCards'] ?? [])),
        ];

        if (count($cards) < 5) {
            return null;
        }

        $evaluated = $this->handEvaluator->evaluate($cards);

        return [
            'name' => $evaluated->rank->label(),
            'rank' => $evaluated->rank->value,
            'kickers' => $evaluated->kickers,
            'cards' => array_map($this->serializeCard(...), $evaluated->cards()),
        ];
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function compareShowdownHands(array $left, array $right): int
    {
        $rankComparison = (int) ($left['rank'] ?? 0) <=> (int) ($right['rank'] ?? 0);

        if ($rankComparison !== 0) {
            return $rankComparison;
        }

        $leftKickers = array_values(array_map('intval', (array) ($left['kickers'] ?? [])));
        $rightKickers = array_values(array_map('intval', (array) ($right['kickers'] ?? [])));
        $max = max(count($leftKickers), count($rightKickers));

        for ($index = 0; $index < $max; $index++) {
            $comparison = ($leftKickers[$index] ?? 0) <=> ($rightKickers[$index] ?? 0);

            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    /**
     * @param array<int, mixed> $payload
     * @return array<int, Card>
     */
    private function deserializeCards(array $payload): array
    {
        $cards = [];

        foreach ($payload as $card) {
            if (! is_array($card)) {
                continue;
            }

            $rank = Rank::tryFrom((string) ($card['rank'] ?? ''));
            $suit = Suit::tryFrom((string) ($card['suit'] ?? ''));

            if (! $rank || ! $suit) {
                continue;
            }

            $cards[] = new Card($suit, $rank);
        }

        return $cards;
    }

    /**
     * @return array{rank: string, suit: string, label: string}
     */
    private function serializeCard(Card $card): array
    {
        return [
            'rank' => $card->rank->value,
            'suit' => $card->suit->value,
            'label' => $card->label(),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $players
     */
    private function playerIndexBySeat(array $players, int $seatNumber): ?int
    {
        foreach ($players as $index => $player) {
            if ((int) ($player['seatNumber'] ?? 0) === $seatNumber) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $players
     */
    private function nextActiveSeat(array $players, int $currentSeat): ?int
    {
        $activeSeats = array_values(array_map(
            static fn (array $player): int => (int) ($player['seatNumber'] ?? 0),
            array_filter(
                $players,
                static fn (array $player): bool => ! (bool) ($player['hasFolded'] ?? false) && (int) ($player['stack'] ?? 0) > 0,
            ),
        ));

        sort($activeSeats);

        foreach ($activeSeats as $seat) {
            if ($seat > $currentSeat) {
                return $seat;
            }
        }

        return $activeSeats[0] ?? null;
    }

    /**
     * @param array<string, mixed> $actor
     */
    private function messageFor(array $actor, PokerAction $action): string
    {
        $name = (string) ($actor['nickname'] ?? 'Jogador');

        return match ($action) {
            PokerAction::Check => $name.' pediu mesa.',
            PokerAction::Call => $name.' pagou a aposta.',
            PokerAction::Raise => $name.' aumentou a aposta.',
            PokerAction::Fold => $name.' desistiu da mão.',
        };
    }
}
