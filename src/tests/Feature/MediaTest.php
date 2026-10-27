<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MediaTest extends TestCase
{
    public function testGalleryUploadsGenerateResizedConversions()
    {
        $event = $this->createEventWithTicket()->event;
        $album = GalleryAlbum::forceCreate(['name' => 'Photos', 'slug' => 'photos', 'event_id' => $event->id]);

        $media = $album->addMedia(UploadedFile::fake()->image('photo.jpg', 2400, 1200))
            ->toMediaCollection('images');

        $this->assertTrue($media->fresh()->hasGeneratedConversion('thumb'));
        $this->assertSame([300, 300], array_slice(getimagesize($media->getPath('thumb')), 0, 2));
        $this->assertSame([2000, 1000], array_slice(getimagesize($media->getPath('optimized')), 0, 2));
    }

    public function testGalleryListsAlbumsNewestEventFirst()
    {
        $older = $this->createEventWithTicket(['status' => 'PUBLISHED', 'start' => '2020-01-01 10:00:00', 'end' => '2020-01-02 18:00:00'])->event;
        $newer = $this->createEventWithTicket(['status' => 'PUBLISHED', 'start' => '2030-01-01 10:00:00', 'end' => '2030-01-02 18:00:00'])->event;

        GalleryAlbum::forceCreate(['name' => 'Album Older', 'slug' => 'album-older', 'status' => 'PUBLISHED', 'event_id' => $older->id]);
        GalleryAlbum::forceCreate(['name' => 'Album Newer', 'slug' => 'album-newer', 'status' => 'PUBLISHED', 'event_id' => $newer->id]);
        GalleryAlbum::forceCreate(['name' => 'Album No Event', 'slug' => 'album-no-event', 'status' => 'PUBLISHED', 'created_at' => '2025-06-01 00:00:00']);

        $this->get('/gallery')
            ->assertOk()
            ->assertSeeInOrder(['Album Newer', 'Album No Event', 'Album Older']);
    }

    public function testCaptchaRendersAnImage()
    {
        $response = $this->get('/captcha/default');

        $response->assertOk();
        $this->assertStringStartsWith('image/', $response->headers->get('Content-Type'));
        $this->assertNotFalse(imagecreatefromstring($response->getContent()));
    }
}
