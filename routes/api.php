<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CollaboratorController;
//use OpenApi\Examples\PetstoreSwaggerIo\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register',[UserController::class,'register']);
Route::post('login',[UserController::class,'login']);
Route::post('forgotpassword',[UserController::class,'forgotPassword']);
Route::get('paginationnote',[NotesController::class,'paginationnote']);

Route::group(['middleware' => ['jwt.verify']], function() {
Route::post('logout',[UserController::class,'logout']);
Route::get('getuser',[UserController::class,'getUser']);
Route::post('resetpassword',[UserController::class,'resetPassword']);
Route::post('createnote',[NotesController::class,'createNote']);
Route::post('readnotebyid',[NotesController::class,'readNoteById']);
Route::get('readallnotes',[NotesController::class,'readAllNotes']);
Route::post('updatenotebyid',[NotesController::class,'updateNoteById']);
Route::delete('deletenotebyid',[NotesController::class,'deleteNoteById']);
Route::post('createlabel',[LabelController::class,'createLabel']);
Route::post('readlabelbyid',[LabelController::class,'readLabelById']);
Route::post('readalllabels',[LabelController::class,'readAllLabels']);
Route::post('updatelabelbyid',[LabelController::class,'updateLabelById']);
Route::delete('deletelabelbyid',[LabelController::class,'deleteLabelById']);
Route::post('addnotelabel',[NotesController::class,'addNoteLabel']);
Route::delete('deletenotelabel',[NotesController::class,'deleteNoteLabel']);
Route::post('pinnote',[NotesController::class,'pinNoteById']);
Route::post('unpinnote',[NotesController::class,'unpinNoteById']);
Route::post('archivenote',[NotesController::class,'archiveNoteById']);
Route::post('unarchivenote',[NotesController::class,'unarchiveNoteById']);
Route::post('colournote',[NotesController::class,'colourNoteById']);
Route::get('getallpinnednotes',[NotesController::class,'getAllPinnedNotes']);
Route::get('getallarchivednotes',[NotesController::class,'getAllArchivedNotes']);
Route::post('searchnotes',[NotesController::class,'searchNotes']);
Route::post('addcollaborator',[CollaboratorController::class,'addCollaboratorByNoteId']);
Route::post('updatecollaborator',[CollaboratorController::class,'updateCollaboratorById']);
Route::delete('deletecollaborator',[CollaboratorController::class,'deleteCollaboratorById']);
Route::get('getallcollaborator',[CollaboratorController::class,'getAllCollaborators']);
Route::get('pagination',[NotesController::class,'paginationNote']);

Route::post('book',[BookController::class,'bookStore']);

});
