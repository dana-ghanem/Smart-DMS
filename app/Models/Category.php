<?php

namespace App\Models;

<<<<<<< HEAD
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Document;

class Category extends Model
{
    use HasFactory;

    protected $primaryKey = 'category_id';

    protected $fillable = [
        'name',
        'description',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'category_id');
    }
=======
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';   // optional if Laravel can infer
    protected $primaryKey = 'category_id'; // THIS is important
>>>>>>> Lora-Sobh
}