<?php

namespace App\Http\Requests\Poker;

use Illuminate\Foundation\Http\FormRequest;

final class PlayPokerRoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'state' => ['nullable', 'array'],
            'action' => ['required', 'string', 'in:check,call,raise,fold'],
            'raiseAmount' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param array<string, mixed> $fallback
     * @return array<string, mixed>
     */
    public function state(array $fallback = []): array
    {
        $state = $this->validated('state');

        return is_array($state) ? $state : $fallback;
    }

    public function pokerAction(): string
    {
        return (string) $this->validated('action');
    }

    public function raiseAmount(): int
    {
        return (int) ($this->validated('raiseAmount') ?? 0);
    }
}
