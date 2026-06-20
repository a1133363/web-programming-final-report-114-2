<?php

declare(strict_types=1);

namespace App\Services;

final class MailService
{
    public function send(string $recipient, string $subject, string $message): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: NOCTURNE <noreply@example.test>',
        ];
        return mail($recipient, $subject, $message, implode("\r\n", $headers));
    }
}
