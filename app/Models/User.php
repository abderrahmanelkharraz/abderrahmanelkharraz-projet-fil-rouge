<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the user's role in a readable format.
     *
     * @param string $value
     * @return string
     */
    public function getRoleAttribute($value): string
    {
        $roles = [
            'aucun' => 'Aucun',
            'admin' => 'admin',
            'employer' => 'Employer',
            'comptable' => 'Comptable',
        ];

        return $roles[$value] ?? 'Aucun';
    }
}
