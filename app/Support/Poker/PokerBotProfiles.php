<?php

namespace App\Support\Poker;

final class PokerBotProfiles
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function all(): array
    {
        return [
            'conservative' => [
                'key' => 'conservative',
                'label' => 'Conservador',
                'description' => 'Evita riscos, paga apostas menores e tende a desistir sob pressão.',
            ],
            'aggressive' => [
                'key' => 'aggressive',
                'label' => 'Agressivo',
                'description' => 'Pressiona mais, aceita apostas maiores e prepara raises com mais frequência.',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<int, string>
     */
    public static function difficulties(): array
    {
        return array_keys(self::difficultyOptions());
    }

    /**
     * @return array<string, array<string, int|string>>
     */
    public static function difficultyOptions(): array
    {
        return [
            'easy' => [
                'key' => 'easy',
                'label' => 'Fácil',
                'description' => 'Comete mais erros, paga fora de hora e não pressiona tanto.',
                'score_bonus' => -14,
                'call_bias' => -25,
                'raise_bias' => 18,
                'fold_bias' => 10,
                'bluff_score' => 92,
            ],
            'normal' => [
                'key' => 'normal',
                'label' => 'Normal',
                'description' => 'Equilibrado, respeita a força da mão e blefa pouco.',
                'score_bonus' => 0,
                'call_bias' => 0,
                'raise_bias' => 0,
                'fold_bias' => 0,
                'bluff_score' => 84,
            ],
            'hard' => [
                'key' => 'hard',
                'label' => 'Difícil',
                'description' => 'Mais seletivo, pressiona melhor e tolera calls com odds melhores.',
                'score_bonus' => 12,
                'call_bias' => 35,
                'raise_bias' => -12,
                'fold_bias' => -8,
                'bluff_score' => 74,
            ],
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public static function difficulty(string $difficulty): array
    {
        return self::difficultyOptions()[$difficulty] ?? self::difficultyOptions()['normal'];
    }

    public static function label(string $profile): string
    {
        return self::all()[$profile]['label'] ?? ucfirst($profile);
    }

    public static function difficultyLabel(string $difficulty): string
    {
        return (string) (self::difficulty($difficulty)['label'] ?? ucfirst($difficulty));
    }
}
