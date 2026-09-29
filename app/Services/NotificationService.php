<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Send in-app notification to a user
     */
    public static function notify(
        User|string $user,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?array $data = null
    ): Notification {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }

    /**
     * Notify an entire team
     */
    public static function notifyTeam(
        $team,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null
    ): void {
        foreach ($team->members as $member) {
            self::notify($member->id, $type, $title, $message, $actionUrl);
        }
    }
}
