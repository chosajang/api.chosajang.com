<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $file_seq
 * @property string $logical_name
 * @property string $physical_name
 * @property string $path
 * @property float $size
 * @property string $mimetype
 * @property string $use_yn
 * @property Carbon $created_at
 * 
 * @package App\Models
 */
class File extends Model
{
    use HasFactory;

    protected $table = 'tb_file';
    protected $primaryKey = 'file_seq';

    const FILE_SEQ = 'file_seq';
    const LOGICAL_NAME = 'logical_name';
    const PHYSICAL_NAME = 'physical_name';
    const PATH = 'path';
    const SIZE = 'size';
    const MIMETYPE = 'mimetype';
    const USE_YN = 'use_yn';
    const CREATED_AT = 'created_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        self::FILE_SEQ,
        self::LOGICAL_NAME,
        self::PHYSICAL_NAME,
        self::PATH,
        self::SIZE,
        self::MIMETYPE,
        self::USE_YN,
        self::CREATED_AT
    ];
}
