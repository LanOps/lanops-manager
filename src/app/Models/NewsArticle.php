<?php

namespace App\Models;

use App\Models\NewsTag;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Cviebrock\EloquentSluggable\Sluggable;

class NewsArticle extends Model
{
    use Sluggable;

    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'news_feed';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array(
        'created_at',
    );

    /*
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\NewsComment', 'news_feed_id');
    }

    public function tags()
    {
        return $this->hasMany('App\Models\NewsTag', 'news_feed_id');
    }


    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Store Tags
     * @param  Array $tags
     * @return Boolean
     */
    public function storeTags($tags)
    {
        $this->tags()->delete();
        $addedTags = array();
        foreach ($tags as $tag) {
            if (!in_array(trim($tag), $addedTags)) {
                $newsTag = new NewsTag();
                $newsTag->tag = trim($tag);
                $newsTag->news_feed_id = $this->id;
                if (!$newsTag->save()) {
                    return false;
                }
                array_push($addedTags, trim($tag));
            }
        }
        return true;
    }

    /**
     * Get a plain text excerpt of the article for news cards
     * @param  Integer $length
     * @return String
     */
    public function getExcerpt($length = 160)
    {
        // Keep a gap where block elements end, otherwise "<p>A</p><p>B</p>" becomes "AB"
        $html = preg_replace('/<br\s*\/?>|<\/(p|div|h[1-6]|li|blockquote|tr)>/i', '$0 ', $this->article ?? '');
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00a0}", ' ', $text)));

        // Articles often open with their own title, which the card already shows
        if ($this->title && mb_stripos($text, trim($this->title)) === 0) {
            $text = ltrim(mb_substr($text, mb_strlen(trim($this->title))), " \t\n\r\0\x0B:-");
        }

        return Str::limit($text, $length);
    }

    /**
     * Get Tags
     * @param  String $separator
     * @return Array
     */
    public function getTags($separator = ', ')
    {
        return implode($separator, $this->tags->pluck('tag')->toArray());
    }
}
