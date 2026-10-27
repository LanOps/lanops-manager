<?php

namespace Tests\Feature;

use App\Models\EventParticipant;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    public function testUserWithoutAvatarGetsDefault(): void
    {
        $user = $this->createUser(['avatar' => '']);

        $this->assertSame(asset('images/default-avatar.svg'), $user->getAvatarUrl());
    }

    public function testUserWithAvatarKeepsIt(): void
    {
        $user = $this->createUser(['avatar' => 'https://avatars.steamstatic.com/abc_full.jpg']);

        $this->assertSame('https://avatars.steamstatic.com/abc_full.jpg', $user->getAvatarUrl());
    }

    public function testAttendeeListFallsBackToDefaultAvatar(): void
    {
        $ticket = $this->createEventWithTicket();
        $withAvatar = $this->createUser(['avatar' => 'https://avatars.steamstatic.com/dead_full.jpg']);
        $withoutAvatar = $this->createUser(['avatar' => null]);

        foreach ([$withAvatar, $withoutAvatar] as $user) {
            $participant = new EventParticipant();
            $participant->forceFill([
                'user_id'   => $user->id,
                'event_id'  => $ticket->event_id,
                'ticket_id' => $ticket->id,
            ])->saveQuietly();
        }

        $fallback = asset('images/default-avatar.svg');
        $this->get('/events/' . $ticket->event->slug)
            ->assertOk()
            ->assertSee('src="https://avatars.steamstatic.com/dead_full.jpg"', false)
            ->assertSee('src="' . $fallback . '"', false)
            ->assertSee("this.src='" . $fallback . "'", false)
            ->assertSee($withoutAvatar->username . "&#039;s Avatar", false);
    }
}
