<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

class Note extends Model implements JWTSubject
{
    use HasApiTokens,HasFactory, Notifiable;

    protected $fillable = [
        'title',
        'description',
        'user_id',
        'pin',
        'archive',
        'colour',
        'label'
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function user_id()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Function to get Notes by Note_Id and User_Id
     * Passing the Note_id and User_id as the parameter
     * 
     * @return array
     */
    public static function getNotesByNoteIdandUserId($id, $user_id)
    {
        $notes = Note::where('id', $id)->where('user_id', $user_id)->first();
        return $notes;
    }
    
    public function noteId($id) {
        return Note::where('id', $id)->first();
    }

    /**
     * Function to get the pinned notes and their labels
     * Passing the user as a parameter
     * 
     * @return array
     */
    public static function getPinnedNotesandItsLabels($user)
    {
        $notes = Note::leftJoin('labelnotes', 'labelnotes.note_id', '=', 'notes.id')
            ->leftJoin('labels', 'labels.id', '=', 'labelnotes.label_id')
            ->select('notes.id', 'notes.title', 'notes.description', 'notes.pin', 'notes.archive', 'notes.colour', 'labels.label_name')
            ->where([['notes.user_id', '=', $user->id], ['pin', '=', 1]])->paginate(4);

        return $notes;
    }

     /**
     * Function to get the archived notes and their labels
     * Passing the user as a parameter
     * 
     * @return array
     */
    public static function getArchivedNotesandItsLabels($user)
    {
        $notes = Note::leftJoin('labelnotes', 'labelnotes.note_id', '=', 'notes.id')
            ->leftJoin('labels', 'labels.id', '=', 'labelnotes.label_id')
            ->select('notes.id', 'notes.title', 'notes.description', 'notes.pin', 'notes.archive', 'notes.colour', 'labels.label_name')
            ->where([['notes.user_id', '=', $user->id], ['archive', '=', 1]])->paginate(4);

        return $notes;
    }

    /**
     * Function to get a searched Note 
     * Passing the Current User Data and Search Key as parameters
     * 
     * @return array
     */
    public static function getSearchedNote($searchKey, $currentUser){
        $usernotes = Note::leftJoin('collaborators', 'collaborators.note_id', '=', 'notes.id')
        ->leftJoin('labelnotes', 'labelnotes.note_id', '=', 'notes.id')
        ->leftJoin('labels', 'labels.id', '=', 'labelnotes.label_id')
        ->select('notes.id', 'notes.title', 'notes.description', 'notes.pin', 'notes.archive', 'notes.colour', 'collaborators.email as Collaborator', 'labels.label_name')
        ->where('notes.user_id', '=', $currentUser->id)->Where('notes.title', 'like', '%' . $searchKey . '%')
        ->orWhere('notes.user_id', '=', $currentUser->id)->Where('notes.description', 'like', '%' . $searchKey . '%')
        ->orWhere('notes.user_id', '=', $currentUser->id)->Where('labels.label_name', 'like', '%' . $searchKey . '%')
        ->orWhere('collaborators.email', '=', $currentUser->email)->Where('notes.title', 'like', '%' . $searchKey . '%')
        ->orWhere('collaborators.email', '=', $currentUser->email)->Where('notes.description', 'like', '%' . $searchKey . '%')
        ->orWhere('collaborators.email', '=', $currentUser->email)->Where('labels.label_name', 'like', '%' . $searchKey . '%')
        ->get();

        return $usernotes;
    }

    public static function getAllNotes($user)
    {
        // $notes = Note::leftJoin('labelnotes', 'labelnotes.note_id', '=', 'notes.id')
        //     ->leftJoin('labels', 'labels.id', '=', 'labelnotes.label_id')
        //     ->leftjoin('collaborators','collaborators.note_id', '=', 'notes.id')
        //     ->select('notes.id', 'notes.title', 'notes.description', 'notes.pin', 'notes.archive', 'notes.colour', 'labels.label_name', 'collaborators.email as collaborator')
        //     ->where([['notes.user_id', '=', $user->id], ['archive', '=', 0], ['pin', '=', 0]])->get();
        //     //->orWhere([['archive', '=', 0], ['pin', '=', 0],['collaborators.email', '=', $user->email]])
        //     //->get();
        
        // return $notes;

        $notes = User::leftjoin('notes','notes.user_id', '=', 'users.id')
        ->leftJoin('collaborators', 'collaborators.note_id', '=', 'notes.id')
        ->leftJoin('labelnotes', 'labelnotes.note_id', '=', 'notes.id')
        ->leftJoin('labels', 'labels.id', '=', 'labelnotes.label_id')
        ->select('users.id','notes.id', 'notes.title', 'notes.description', 'notes.pin', 'notes.archive', 'notes.colour', 'labels.label_name', 'collaborators.email as collaborator')
        ->where([['notes.user_id', '=', $user->id], ['archive', '=', 0], ['pin', '=', 0]])
        ->orwhere([['collaborators.email', '=', $user->email], ['archive', '=', 0], ['pin', '=', 0]])
        ->get();

        return $notes;

        // $notes = DB::table('users')
        //     ->leftjoin('notes','notes.user_id', '=', 'users.id')
        //     ->leftJoin('collaborators', 'collaborators.user_id', '=', 'users.id')
        //     ->leftJoin('labelnotes', 'labelnotes.note_id', '=', 'notes.id')
        //     ->leftJoin('labels', 'labels.id', '=', 'labelnotes.label_id')
        //     ->select('users.id','notes.id', 'notes.title', 'notes.description', 'notes.pin', 'notes.archive', 'notes.colour', 'labels.label_name', 'collaborators.email as collaborator'
        //     	,DB::raw("(GROUP_CONCAT(labels.label_name SEPARATOR '@')) as `labelname`"))
        //         ->where([['users.id', '=', $user->id], ['archive', '=', 0], ['pin', '=', 0]])
        //         ->orwhere([['collaborators.email', '=', $user->email], ['archive', '=', 0], ['pin', '=', 0]])
        //     ->groupBy('notes.id')
        //     ->get();

        //     return $notes;

    }
}
