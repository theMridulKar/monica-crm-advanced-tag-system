<?php

namespace App\Models;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Taggable extends MorphPivot
{
    protected $table = 'taggables';

    protected $fillable = [
        'tag_id',
        'taggable_id',
        'taggable_type',
    ];

    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}