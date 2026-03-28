<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;

class Document extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'title',
        'author_name',
        'description',
        'file_path',
        'category_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

<<<<<<< HEAD
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
=======
    // Relation with Category
   public function category() {
    return $this->belongsTo(Category::class); // assuming category_id
}
public function author() {
    return $this->belongsTo(Author::class);
}
}
>>>>>>> Lora-Sobh
