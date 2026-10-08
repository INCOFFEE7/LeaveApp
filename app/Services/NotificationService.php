<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Kirim notifikasi ke satu user
     */
    public static function sendToUser(
        User|int $user,
        string $title,
        string $message,
        string $type = 'general',
        array $data = [],
        ?string $relatedTable = null,
        ?int $relatedId = null,
        int $priority = 0
    ): Notification {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id'         => $userId, // ✅ tambahkan
            'type'            => $type,
            'notifiable_type' => User::class,
            'notifiable_id'   => $userId,
            'title'           => $title, // ✅ langsung isi
            'related_table'   => $relatedTable,
            'related_id'      => $relatedId,
            'priority'        => $priority,
            'data'            => [
                'message' => $message,
                'extra'   => $data,
            ],
        ]);
    }

    /**
     * Kirim notifikasi ke banyak user
     */
    public static function sendToUsers(
        iterable $users,
        string $title,
        string $message,
        string $type = 'general',
        array $data = [],
        ?string $relatedTable = null,
        ?int $relatedId = null,
        int $priority = 0
    ): void {
        foreach ($users as $user) {
            $userId = $user instanceof User ? $user->id : $user;

            Notification::create([
                'user_id'         => $userId, // ✅ tambahkan
                'type'            => $type,
                'notifiable_type' => User::class,
                'notifiable_id'   => $userId,
                'title'           => $title, // ✅ langsung isi
                'related_table'   => $relatedTable,
                'related_id'      => $relatedId,
                'priority'        => $priority,
                'data'            => [
                    'message' => $message,
                    'extra'   => $data,
                ],
            ]);
        }
    }

    /**
     * Tandai notifikasi sudah dibaca
     */
    public static function markAsRead(Notification|int $notification): bool
    {
        $notif = $notification instanceof Notification
            ? $notification
            : Notification::find($notification);

        if (! $notif) {
            return false;
        }

        $notif->read_at = now();
        return $notif->save();
    }

    /**
     * Tandai semua notifikasi user sebagai sudah dibaca
     */
    public static function markAllAsRead(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::where('user_id', $userId)
            ->whereNull('read_at') // ✅ pakai read_at, bukan is_read
            ->update(['read_at' => now()]);
    }
}
