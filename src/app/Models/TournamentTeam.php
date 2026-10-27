<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class TournamentTeam extends Model
{
    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'tournament_teams';

    protected $fillable = [
        'name',
        'tournament_id',
    ];

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
    public function tournament()
    {
        return $this->belongsTo('App\Models\Tournament', 'tournament_id');
    }

    public function participants()
    {
        return $this->hasMany('App\Models\TournamentParticipant');
    }

    public function getParticipantCount()
    {
        return $this->participants()->count();
    }

    /**
     * Check a password for joining this team. Teams made before passwords
     * were hashed still have theirs in plain text.
     * @param  string|null $password
     * @return Boolean
     */
    public function checkPassword($password)
    {
        if ($this->password == null) {
            return true;
        }
        if (!is_string($password) || $password === '') {
            return false;
        }
        if (password_get_info($this->password)['algo'] === null) {
            return hash_equals($this->password, $password);
        }
        return Hash::check($password, $this->password);
    }
}
