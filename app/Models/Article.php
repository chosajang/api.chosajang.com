<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $table = 'tb_article';
    protected $primaryKey = 'article_seq';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'article_seq', 
        'title', 
        'contents', 
        'description', 
        'use_yn', 
        'post_yn', 
        'created_at', 
        'updated_at', 
        'user_seq',
        'thumbnail_file_seq'
    ];

}
