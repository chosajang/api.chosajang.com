<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property int $user_seq
 * @property string $id
 * @property string $name
 * @property string $nickname
 * @property string $password
 * @property string $email
 * @property string $tel
 * @property string $comment
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int $profile_file_seq
 * 
 * @property Collection|File[] $file
 * 
 * @package App\Models
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'tb_user';
    protected $primaryKey = 'user_seq';

    const USER_SEQ = 'user_seq';
    const ID = 'id';
    const NAME = 'name';
    const NICKNAME = 'nickname';
    const PASSWORD = 'password';
    const EMAIL = 'email';
    const TEL = 'tel';
    const COMMENT = 'comment';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const PROFILE_FILE_SEQ = 'profile_file_seq';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        self::ID,
        self::NAME,
        self::NICKNAME,
        self::PASSWORD,
        self::EMAIL,
        self::TEL,
        self::COMMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PROFILE_FILE_SEQ
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        self::PASSWORD
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [
            'id' => $this->id,
            'email' => $this->email,
        ];
    }

    public function file() {
        return this->hasMany(File::class, FILE::FILE_SEQ, self::PROFILE_FILE_SEQ);
    }

}
