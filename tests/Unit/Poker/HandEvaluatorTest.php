<?php

namespace Tests\Unit\Poker;

use App\Domain\Poker\Cards\Card;
use App\Domain\Poker\Cards\Rank;
use App\Domain\Poker\Cards\Suit;
use App\Domain\Poker\Hands\HandEvaluator;
use App\Domain\Poker\Hands\HandRank;
use InvalidArgumentException;
use Tests\TestCase;

class HandEvaluatorTest extends TestCase
{
    public function test_evaluates_high_card(): void
    {
        $hand = $this->evaluator()->evaluate([
            $this->card(Suit::Spades, Rank::Ace),
            $this->card(Suit::Hearts, Rank::King),
            $this->card(Suit::Clubs, Rank::Nine),
            $this->card(Suit::Diamonds, Rank::Seven),
            $this->card(Suit::Spades, Rank::Three),
        ]);

        $this->assertSame(HandRank::HighCard, $hand->rank);
        $this->assertSame([14, 13, 9, 7, 3], $hand->kickers);
    }

    public function test_evaluates_pair(): void
    {
        $hand = $this->evaluator()->evaluate([
            $this->card(Suit::Spades, Rank::Ace),
            $this->card(Suit::Hearts, Rank::Ace),
            $this->card(Suit::Clubs, Rank::Nine),
            $this->card(Suit::Diamonds, Rank::Seven),
            $this->card(Suit::Spades, Rank::Three),
        ]);

        $this->assertSame(HandRank::Pair, $hand->rank);
    }

    public function test_evaluates_two_pair(): void
    {
        $hand = $this->evaluator()->evaluate([
            $this->card(Suit::Spades, Rank::Ace),
            $this->card(Suit::Hearts, Rank::Ace),
            $this->card(Suit::Clubs, Rank::King),
            $this->card(Suit::Diamonds, Rank::King),
            $this->card(Suit::Spades, Rank::Three),
        ]);

        $this->assertSame(HandRank::TwoPair, $hand->rank);
    }

    public function test_evaluates_three_of_a_kind(): void
    {
        $hand = $this->evaluator()->evaluate([
            $this->card(Suit::Spades, Rank::Queen),
            $this->card(Suit::Hearts, Rank::Queen),
            $this->card(Suit::Clubs, Rank::Queen),
            $this->card(Suit::Diamonds, Rank::King),
            $this->card(Suit::Spades, Rank::Three),
        ]);

        $this->assertSame(HandRank::ThreeOfAKind, $hand->rank);
    }

    public function test_evaluates_straight(): void
    {
        $hand = $this->evaluator()->evaluate([
            $this->card(Suit::Spades, Rank::Nine),
            $this->card(Suit::Hearts, Rank::Eight),
            $this->card(Suit::Clubs, Rank::Seven),
            $this->card(Suit::Diamonds, Rank::Six),
            $this->card(Suit::Spades, Rank::Five),
        ]);

        $this->assertSame(HandRank::Straight, $hand->rank);
        $this->assertSame([9], $hand->kickers);
    }

    public function test_evaluates_low_ace_straight(): void
    {
        $hand = $this->evaluator()->evaluate([
            $this->card(Suit::Spades, Rank::Ace),
            $this->card(Suit::Hearts, Rank::Two),
            $this->card(Suit::Clubs, Rank::Three),
            $this->card(Suit::Diamonds, Rank::Four),
            $this->card(Suit::Spades, Rank::Five),
        ]);

        $this->assertSame(HandRank::Straight, $hand->rank);
        $this->assertSame([5], $hand->kickers);
    }

    public function test_evaluates_flush(): void
    {
        $hand = $this->evaluator()->evaluate([
            $this->card(Suit::Hearts, Rank::Ace),
            $this->card(Suit::Hearts, Rank::King),
            $this->card(Suit::Hearts, Rank::Nine),
            $this->card(Suit::Hearts, Rank::Seven),
            $this->card(Suit::Hearts, Rank::Three),
        ]);

        $this->assertSame(HandRank::Flush, $hand->rank);
    }

    public function test_evaluates_full_house(): void
    {
        $hand = $this->evaluator()->evaluate([
            $this->card(Suit::Spades, Rank::Ten),
            $this->card(Suit::Hearts, Rank::Ten),
            $this->card(Suit::Clubs, Rank::Ten),
            $this->card(Suit::Diamonds, Rank::King),
            $this->card(Suit::Spades, Rank::King),
        ]);

        $this->assertSame(HandRank::FullHouse, $hand->rank);
    }

    public function test_evaluates_four_of_a_kind(): void
    {
        $hand = $this->evaluator()->evaluate([
            $this->card(Suit::Spades, Rank::Jack),
            $this->card(Suit::Hearts, Rank::Jack),
            $this->card(Suit::Clubs, Rank::Jack),
            $this->card(Suit::Diamonds, Rank::Jack),
            $this->card(Suit::Spades, Rank::King),
        ]);

        $this->assertSame(HandRank::FourOfAKind, $hand->rank);
    }

    public function test_evaluates_straight_flush(): void
    {
        $hand = $this->evaluator()->evaluate([
            $this->card(Suit::Spades, Rank::Nine),
            $this->card(Suit::Spades, Rank::Eight),
            $this->card(Suit::Spades, Rank::Seven),
            $this->card(Suit::Spades, Rank::Six),
            $this->card(Suit::Spades, Rank::Five),
        ]);

        $this->assertSame(HandRank::StraightFlush, $hand->rank);
        $this->assertSame([9], $hand->kickers);
    }


    public function test_trinca_do_board_com_carta_da_mao_vence_dois_pares(): void
    {
        $botHand = $this->evaluator()->evaluate([
            $this->card(Suit::Clubs, Rank::Five),
            $this->card(Suit::Diamonds, Rank::Nine),
            $this->card(Suit::Clubs, Rank::Nine),
            $this->card(Suit::Spades, Rank::Nine),
            $this->card(Suit::Diamonds, Rank::Ten),
            $this->card(Suit::Diamonds, Rank::Eight),
            $this->card(Suit::Spades, Rank::Three),
        ]);

        $playerHand = $this->evaluator()->evaluate([
            $this->card(Suit::Hearts, Rank::Five),
            $this->card(Suit::Hearts, Rank::Eight),
            $this->card(Suit::Clubs, Rank::Nine),
            $this->card(Suit::Spades, Rank::Nine),
            $this->card(Suit::Diamonds, Rank::Ten),
            $this->card(Suit::Diamonds, Rank::Eight),
            $this->card(Suit::Spades, Rank::Three),
        ]);

        $this->assertSame(HandRank::ThreeOfAKind, $botHand->rank);
        $this->assertSame(HandRank::TwoPair, $playerHand->rank);
        $this->assertGreaterThan($playerHand->rank->value, $botHand->rank->value);
    }

    public function test_requires_at_least_five_cards(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('É necessário informar pelo menos 5 cartas para avaliar uma mão.');

        $this->evaluator()->evaluate([
            $this->card(Suit::Spades, Rank::Ace),
        ]);
    }

    private function evaluator(): HandEvaluator
    {
        return new HandEvaluator();
    }

    private function card(Suit $suit, Rank $rank): Card
    {
        return new Card($suit, $rank);
    }
}
