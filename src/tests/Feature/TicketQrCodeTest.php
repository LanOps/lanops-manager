<?php

namespace Tests\Feature;

use App\Models\EventParticipant;
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

    public function testTicketUrlFixesAppUrlMissingTheColon(): void
    {
        config(['app.url' => 'https//www.lanops.co.uk']);

        $this->assertSame('https://www.lanops.co.uk/tickets/retrieve/1971', $this->participant(1971)->getTicketUrl());
    }

    public function testRegenerateCommandRunsWithNoParticipants(): void
    {
        $this->artisan('tickets:regenerate-qr', ['--event' => 999999])
            ->expectsOutput('QR codes regenerated')
            ->assertExitCode(0);
    }
}
