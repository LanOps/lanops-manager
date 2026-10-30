<?php

namespace Tests\Feature;

use App\Models\EventParticipant;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Tests\TestCase;

class TicketQrCodeTest extends TestCase
{
    private function participant(int $id): EventParticipant
    {
        $participant = new EventParticipant();
        $participant->id = $id;
        $participant->qr_token = 'tok' . $id;
        return $participant;
    }

    public function testTicketUrlUsesAppUrlWithScheme(): void
    {
        config(['app.url' => 'https://www.lanops.co.uk']);

        $this->assertSame('https://www.lanops.co.uk/tickets/retrieve/tok1971', $this->participant(1971)->getTicketUrl());
    }

    public function testTicketUrlHandlesTrailingSlash(): void
    {
        config(['app.url' => 'https://www.lanops.co.uk/']);

        $this->assertSame('https://www.lanops.co.uk/tickets/retrieve/tok1971', $this->participant(1971)->getTicketUrl());
    }

    public function testTicketUrlAddsHttpsWhenAppUrlHasNoScheme(): void
    {
        config(['app.url' => 'www.lanops.co.uk']);

        $this->assertSame('https://www.lanops.co.uk/tickets/retrieve/tok1971', $this->participant(1971)->getTicketUrl());
    }

    public function testRegeneratingReplacesTheOldQrCode(): void
    {
        QrCode::shouldReceive('format->size->generate')->andReturn('new-qr');
        $ticket = $this->createEventWithTicket();
        $participant = new EventParticipant();
        $participant->forceFill([
            'user_id'   => $this->createUser()->id,
            'event_id'  => $ticket->event_id,
            'ticket_id' => $ticket->id,
        ])->saveQuietly();
        $participant->addMediaFromString('old-qr')->usingFileName('oldqr.png')->toMediaCollection('qr');

        $this->assertTrue($participant->generateQRCode());

        $media = $participant->fresh()->getMedia('qr');
        $this->assertCount(1, $media);
        $this->assertNotSame('oldqr.png', $media->first()->file_name);
    }

    public function testTicketUrlUsesARandomTokenNotTheId(): void
    {
        config(['app.url' => 'https://www.lanops.co.uk']);
        $ticket = $this->createEventWithTicket();
        $participant = new EventParticipant();
        $participant->forceFill([
            'user_id'   => $this->createUser()->id,
            'event_id'  => $ticket->event_id,
            'ticket_id' => $ticket->id,
        ])->saveQuietly();

        $url = $participant->getTicketUrl();

        $token = $participant->fresh()->qr_token;
        $this->assertSame(40, strlen($token));
        $this->assertSame('https://www.lanops.co.uk/tickets/retrieve/' . $token, $url);
        $this->assertArrayNotHasKey('qr_token', $participant->fresh()->toArray());
    }

    public function testRetrieveFindsTheTicketByTokenOnly(): void
    {
        $admin = $this->createUser(['admin' => 1]);
        $ticket = $this->createEventWithTicket();
        $participant = new EventParticipant();
        $participant->forceFill([
            'user_id'   => $this->createUser()->id,
            'event_id'  => $ticket->event_id,
            'ticket_id' => $ticket->id,
            'qr_token'  => 'known-token',
        ])->saveQuietly();

        $this->actingAs($admin)->get('/tickets/retrieve/known-token')
            ->assertRedirect('/admin/events/' . $ticket->event->slug . '/participants/' . $participant->id);
        $this->actingAs($admin)->get('/tickets/retrieve/' . $participant->id)->assertNotFound();
    }

    public function testRegenerateCommandRunsWithNoParticipants(): void
    {
        $this->artisan('tickets:regenerate-qr', ['--event' => 999999])
            ->expectsOutput('QR codes regenerated')
            ->assertExitCode(0);
    }
}
