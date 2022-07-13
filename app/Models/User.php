<?php
namespace App\Models;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens,HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
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
    
    /**
     * Function to Update the password with new password
     * Passing the User and the new_password as parameters
     * 
     * @return array
     */
    public static function updatePassword($user, $new_password)
    {
        $user->password = bcrypt($new_password);
        $user->save();
        return $user;
    }

    /**
     * Mutator for first name attribute
     * Before saving it to database first letter will be changed to upper case
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['firstname'] = ucfirst($value);
    }

    /**
     * Mutator for last name attribute
     * Before saving it to database first letter will be changed to upper case
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['lastname'] = ucfirst($value);
    }

    /**
     * Accessor for first name attribute
     * When user is retrived from database, 
     * first letter of first name will be upper case and 
     * Mr/s. will be added while displaying
     */
    public function getFirstNameAttribute($value)
    {
        return 'Mr/s. ' . ucfirst($value);
    }

     /**
     * Function to get user details by email
     * Passing the email as parameter
     * 
     * @return array
     */
    public static function getUserByEmail($email){
        $user = User::where('email', $email)->first();
        return $user;
    }

    public function collaborators()
    {
        return $this->hasMany('App\Models\Collaborator');
    }

    public function notes()
    {
        return $this->hasMany('App\Models\Note');
    }  
    public function labels()
    {
        return $this->hasmany('App\Models\Label');
    }
    public function label_notes()
    {
        return $this->hasmany('App\Models\LabelNotes');
    }

    /**
     * Creates a new user with the attributes given
     * 
     * @return array
     */
    public static function createUser($request)
    {
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        return $user;
    }
}