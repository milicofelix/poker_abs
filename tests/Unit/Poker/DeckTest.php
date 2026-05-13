<?php

namespace Tests\Unit\Poker;

use App\Domain\Poker\Cards\Card;
use App\Domain\Poker\Cards\Deck;
use App\Domain\Poker\Cards\Rank;
use App\Domain\Poker\Cards\Suit;
use RuntimeException;
use Tests\TestCase;

class DeckTest extends TestCase
{
    public function test_standard_deck_has_52_cards(): void
    {
        $deck = Deck::standard();

        $this->assertCount(52, $deck->cards());
        $this->assertSame(52, $deck->remaining());
    }

    public function test_standard_deck_has_unique_cards(): void
    {
        $deck = Deck::standard();

        $labels = array_map(
            fn (Card $card) => $card->suit->value . '-' . $card->rank->value,
            $deck->cards()
        );

        $this->assertCount(52, array_unique($labels));
    }

    public function test_can_draw_cards_from_deck(): void
    {
        $deck = Deck::standard();

        $cards = $deck->draw(2);

        $this->assertCount(2, $cards);
        $this->assertSame(50, $deck->remaining());
    }

    public function test_cannot_draw_more_cards_than_available(): void
    {
        $deck = Deck::standard();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Não há cartas suficientes no baralho.');

        $deck->draw(53);
    }

    public function test_cannot_draw_zero_cards(): void
    {
        $deck = Deck::standard();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A quantidade de cartas deve ser maior que zero.');

        $deck->draw(0);
    }

    public function test_card_has_readable_label(): void
    {
        $card = new Card(Suit::Spades, Rank::Ace);

        $this->assertSame('A♠', $card->label());
    }
}