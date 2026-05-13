<?php

namespace App\Services\Poker;

use Illuminate\Contracts\Session\Session;

final readonly class LocalPokerSessionService
{
    private const SESSION_KEY = 'poker.local_hand';

    public function __construct(
        private Session $session,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function current(): ?array
    {
        $state = $this->session->get(self::SESSION_KEY);

        return is_array($state) ? $state : null;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function store(array $state): array
    {
        $this->session->put(self::SESSION_KEY, $state);

        return $state;
    }

    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }
}
