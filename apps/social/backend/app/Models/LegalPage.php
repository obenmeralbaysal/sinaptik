<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalPage extends Model
{
    protected $fillable = ['slug', 'locale', 'title', 'body_markdown', 'updated_by'];
}
