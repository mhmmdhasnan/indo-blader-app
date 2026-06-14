<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Registration;

class NotificationService
{
    public static function send(
        Registration $registration,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): void {
        Notification::create([
            'notifiable_id' => $registration->id,
            'type'          => $type,
            'title'         => $title,
            'body'          => $body,
            'data'          => $data ?: null,
        ]);
    }

    public static function sendToMany(
        iterable $registrations,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): void {
        foreach ($registrations as $registration) {
            static::send($registration, $type, $title, $body, $data);
        }
    }
}
