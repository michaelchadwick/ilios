<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async_priority_normal')]
class SessionDeleteRequest
{
    public function __construct(private readonly int $sessionId)
    {
    }

    public function getSessionId(): int
    {
        return $this->sessionId;
    }
}
