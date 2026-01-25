<?php
namespace App\Listeners;

use App\Events\UserProfileRequested;
use Illuminate\Support\Facades\Log;

class NotifyUserListener
{
    public function handle(UserProfileRequested $event)
    {
        // TODO: Send push notification
        Log::info("User {$event->targetUserId} profile requested by {$event->requestingUserId}");
    }
}
