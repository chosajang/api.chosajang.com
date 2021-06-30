<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $table = 'tb_file';
    protected $primaryKey = 'file_seq';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'file_seq', 'logical_name', 'physical_name', 'path', 'size', 'mimetype', 'use_yn', 'created_at'
    ];    
}
