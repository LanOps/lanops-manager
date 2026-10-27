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
        return $participant;
    }

    public function testTicketUrlUsesAppUrlWithScheme(): void
    {
        config(['app.url' => 'https://www.lanops.co.uk']);

        $this->assertSame('https://www.lanops.co.uk/tickets/retrieve/1971', $this->participant(1971)->getTicketUrl());
    }

    public function testTicketUrlHandlesTrailingSlash(): void
    {
        config(['app.url' => 'https://www.lanops.co.uk/']);

        $this->assertSame('https://www.lanops.co.uk/tickets/retrieve/1971', $this->participant(1971)->getTicketUrl());
    }

    public function testTicketUrlAddsHttpsWhenAppUrlHasNoScheme(): void
    {
        config(['app.url' => 'www.lanops.co.uk']);

        $this->assertSame('https://www.lanops.co.uk/tickets/retrieve/1971', $this->participant(1971)->getTicketUrl());
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

    public function testRegenerateCommandRunsWithNoParticipants(): void
    {
        $this->artisan('tickets:regenerate-qr', ['--event' => 999999])
            ->expectsOutput('QR codes regenerated')
            ->assertExitCode(0);
    }
}
