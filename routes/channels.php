<?php

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

// General orders channel (all authenticated users)
Broadcast::channel('orders', function ($user) {
    return $user !== null;
});

// Kitchen channel (kitchen staff and admins)
Broadcast::channel('kitchen', function ($user) {
    return $user && ($user->hasRole('kitchen') || $user->hasRole('admin'));
});

// Specific kitchen station channel
Broadcast::channel('station.{stationId}', function ($user, $stationId) {
    return $user && ($user->hasRole('kitchen') || $user->hasRole('admin'));
});

// Tables channel (all authenticated users)
Broadcast::channel('tables', function ($user) {
    return $user !== null;
});

// Specific floor channel
Broadcast::channel('floor.{floorId}', function ($user, $floorId) {
    return $user !== null;
});

// Payments channel (cashiers and admins)
Broadcast::channel('payments', function ($user) {
    return $user && ($user->hasRole('cashier') || $user->hasRole('admin'));
});

// User-specific private channel
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
