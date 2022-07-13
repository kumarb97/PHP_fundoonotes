<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Label extends Model implements JWTSubject
{
    use HasApiTokens,HasFactory, Notifiable;

    protected $fillable = [
        'label_name',
        'user_id',
    ];

    public function getJWTIdentifier() {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }

    public function user_id()
    {
        return $this->belongsTo(User::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Function to get label by the label_id and user_id
     * passing label_id and user_id as parameters
     * 
     * @return array
     */
    public static function getLabelByLabelIdandUserId($label_id, $user_id)
    {
        $label = Label::where('id', $label_id)->where('user_id', $user_id)->first();
        return $label;
    }

    /**
     * Function to get label by the label_name and user_id
     * passing label_name and user_id as parameters
     * 
     * @return array
     */
    public static function getLabelByLabelNameandUserId($label_name, $user_id)
    {
        $label = Label::where('labelname', $label_name)->where('user_id', $user_id)->first();
        return $label;
    }
}
