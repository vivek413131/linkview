<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;

class UserProfileRequested
{
    use SerializesModels;

    public $targetUserId;
    public $requestingUserId;

    public function __construct($targetUserId, $requestingUserId)
    {
        $this->targetUserId = $targetUserId;
        $this->requestingUserId = $requestingUserId;
    }
}
