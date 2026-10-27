<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GalleryAlbum;

use App\Http\Controllers\Controller;

use Illuminate\Validation\ValidationException;

class GalleryController extends Controller
{
    /**
     * Show Gallery Index Page
     * @return View
     */
    public function index()
    {
        seo()
            ->title('Gallery')
            ->description('Browse photo galleries from ' . config('app.name') . ' events.')
            ->url(url('/gallery'));

        $event = Event::where('start', '>=', date("Y-m-d 00:00:00"))->first();
        // Newest first, by the album's event date (or the album's own date if it has no event)
        $albums = GalleryAlbum::with('event')->get()
            ->sortByDesc(fn ($album) => (string) ($album->event?->start ?? $album->created_at))
            ->values();
        return view('gallery.index')
            ->withAlbums($albums)
            ->withEvent($event);
    }

    /**
     * Show Gallery Page
     * @param  GalleryAlbum $album
     * @return View
     */
    public function show(GalleryAlbum $album)
    {
        $description = $album->description ?: 'Photos from ' . $album->name;
        if ($album->event) {
            $description = 'Photos from ' . $album->event->display_name . '. ' . $description;
        }

        seo()
            ->title($album->name . ' — Gallery')
            ->description($description)
            ->url(url('/gallery/' . $album->slug));

        try {
            seo()->image($album->getFirstMedia('images')->getUrl('optimized'));
        } catch (\Exception $e) {
        }

        return view('gallery.show')
            ->withAlbum($album);
    }
}
