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

    public function testCaptchaRendersAnImage()
    {
        $response = $this->get('/captcha/default');

        $response->assertOk();
        $this->assertStringStartsWith('image/', $response->headers->get('Content-Type'));
        $this->assertNotFalse(imagecreatefromstring($response->getContent()));
    }
}
