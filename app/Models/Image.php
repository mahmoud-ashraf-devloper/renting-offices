<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use phpDocumentor\Reflection\Types\This;

class Image extends Model
{
    use HasFactory;

    public function resource() : MorphTo
    {
        return $this->morphTo();
    }
}
