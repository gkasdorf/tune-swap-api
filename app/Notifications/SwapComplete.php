<?php

namespace App\Notifications;

use App\Models\Swap;
use App\Models\User;
use NotificationChannels\Apn\ApnChannel;
use NotificationChannels\Apn\ApnMessage;

class SwapComplete extends \Illuminate\Notifications\Notification
{
    private Swap $swap;
    private User $user;

    public function __construct(Swap $swap)
    {
        $this->swap = $swap;
    }

    public function via($notifiable)
    {
        return [ApnChannel::class];
    }

    public function toApn($notifiable)
    {
        return ApnMessage::create()
            ->title("Swap Complete!")
            ->body("Great news " . $notifiable->getName() . "! " . $this->swap->getPlaylistName() . " has finished moving over to your " . $this->swap->getToService(true) . " account. Go ahead, check it out!")
            ->sound("default");
    }
}
