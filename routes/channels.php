<?php

use App\Models\v1\Call;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.v1.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('notifications.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('Conversation.{id}', function ($user, $id) {
    return $user->threads()->where('chat_threads.id', $id)->orWhere('chat_threads.slug', $id)->exists();
});

Broadcast::channel('CallNotifications.{CallId}', function ($user, $CallId) {
    return Call::where(function ($query) use ($CallId) {
        $query->where('id', $CallId)
            ->orWhere('room_name', $CallId);
    })->isParticipant($user->id)->exists();
});