<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    // Allow mass assignment
    protected $fillable = [
        'title',
        'author',
        'description',
        'file_path',
        'user_id',
        'category_id',
    ];

    // Relation with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation with Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}