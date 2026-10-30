<?php

namespace Tests\Feature;

use App\Models\EventVenue;
use App\Models\GalleryAlbum;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Admin pages: a banned admin loses access, and image deletes only touch
 * images that belong to the album or venue in the URL.
 */
class AdminSafetyTest extends TestCase
{
    protected function addImage($model): Media
    {
        // A text file is enough here and skips the image conversions
        return $model->addMediaFromString('not really an image')
            ->usingFileName('photo.txt')
            ->toMediaCollection('images');
    }

    public function testBannedAdminCannotUseAdminPages()
    {
        $admin = $this->createUser(['admin' => 1, 'banned' => 1]);

        $this->actingAs($admin)->get('/admin/venues')
            ->assertRedirect('/')
            ->assertSessionHas('alert-danger', 'You have been banned!');
    }

    public function testGalleryImageFromAnotherAlbumIsNotDeleted()
    {
        $admin = $this->createUser(['admin' => 1]);
        $album = GalleryAlbum::forceCreate(['name' => 'Album One', 'slug' => 'album-one']);
        $other = GalleryAlbum::forceCreate(['name' => 'Album Two', 'slug' => 'album-two']);
        $image = $this->addImage($other);

        $this->actingAs($admin)->delete('/admin/gallery/' . $album->slug . '/' . $image->id)->assertNotFound();
        $this->assertNotNull(Media::find($image->id));

        $this->actingAs($admin)->delete('/admin/gallery/' . $other->slug . '/' . $image->id)
            ->assertSessionHas('alert-success', 'Successfully deleted Image!');
        $this->assertNull(Media::find($image->id));
    }

    public function testVenueImageFromAnotherVenueIsNotDeleted()
    {
        $admin = $this->createUser(['admin' => 1]);
        $venue = factory(EventVenue::class)->create();
        $image = $this->addImage(factory(EventVenue::class)->create());

        $this->actingAs($admin)->delete('/admin/venues/' . $venue->slug . '/' . $image->id)->assertNotFound();
        $this->assertNotNull(Media::find($image->id));
    }

    public function testVenueCountryIsChecked()
    {
        $admin = $this->createUser(['admin' => 1]);
        $venue = [
            'name'             => 'Test Venue',
            'address_1'        => '1 Test Road',
            'address_street'   => 'Test Road',
            'address_city'     => 'London',
            'address_postcode' => 'SW1A 1AA',
        ];

        $this->actingAs($admin)->post('/admin/venues', $venue + ['address_country' => 'Narnia'])
            ->assertSessionHas('alert-danger', 'That country appears to be invalid. Please use a valid one.');

        $this->actingAs($admin)->post('/admin/venues', $venue + ['address_country' => 'United Kingdom'])
            ->assertSessionHas('alert-success');
        $this->assertSame('United Kingdom', EventVenue::where('display_name', 'Test Venue')->value('address_country'));
    }
}
