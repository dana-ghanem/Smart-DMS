<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;

class Document extends Model
{
    use HasFactory;
    protected $primaryKey = 'document_id';
    protected $fillable = [
        'user_id',
        'title',
        'author_name',
        'description',
        'file_path',
        'file_hash',
        'extracted_text',
        'category_id',
        'processed_tokens',
        'token_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

   public function category()
{
    return $this->belongsTo(Category::class, 'category_id', 'category_id');
}
}
