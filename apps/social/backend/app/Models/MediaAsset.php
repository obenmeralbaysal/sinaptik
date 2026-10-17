<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'brand_id', 'disk', 'path', 'original_name', 'mime',
        'size_bytes', 'width', 'height', 'uploaded_by',
    ];
}
