<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Label;
use App\Models\LabelNotes;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use PDO;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Exceptions\FundoNotesException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;


class NotesController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/createnote",
     *   summary="create note",
     *   description="create note",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"title","description"},
     *               @OA\Property(property="title", type="string"),
     *               @OA\Property(property="description", type="string"),
     *               @OA\Property(property="label_id"),  
     *               @OA\Property(property="pin"),  
     *               @OA\Property(property="archive"),  
     *               @OA\Property(property="colour"),
     *               @OA\Property(property="collaborator_email")         
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="Note created Sucessfully"),
     *   @OA\Response(response=401, description="Invalid token"),
     * security={
     *       {"Bearer": {}}
     *     }
     * )
     * Create Note.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    function createNote(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|min:3|max:30',
                'description' => 'required|string|min:3|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $currentUser = JWTAuth::authenticate($request->token);
            $user_id = $currentUser->id;

            if (!$currentUser) {
                Log::error('Invalid Authorization Token');
                throw new FundoNotesException('Invalid Authorization Token', 401);
            } else {

                $note = Note::create([
                    'title' => $request->title,
                    'description' => $request->description,
                    'user_id' => $user_id,

                ]);
                return response()->json([
                    'message' => 'Note created successfully',
                    'note' => $note
                ], 200);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/readnotebyid",
     *   summary="Read Note",
     *   description=" Read Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Note found Sucessfully"),
     *   @OA\Response(response=404, description="Notes not Found"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * Read note by id
     * @return \Illuminate\Http\JsonResponse
     */

    function readNoteById(Request $request)
    {

        $validator = Validator::make($request->only('id'), [
            'id' => 'required|integer',
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid'], 400);
        }

        $currentUser = JWTAuth::authenticate($request->token);

        if (!$currentUser) {
            return response()->json([
                'message' => 'Invalid Authorization Token',
            ], 401);
        }

        $currentid = $currentUser->id;
        //$note = Note::where('id', $request->id)->first();
        $note = Note::where('user_id', $currentid)->where('id', $request->id)->first();

        if (!$note) {
            return response()->json([
                'message' => 'Invalid id'
            ], 401);
        } else {
            return response()->json(['note' => $note], 200);
        }
    }

    /**
     *   @OA\Get(
     *   path="/api/readallnotes",
     *   summary="read notes",
     *   description="user read notes",
     *   @OA\RequestBody(
     *    ),
     *   @OA\Response(response=201, description="Notes shown suucessfully"),
     *   @OA\Response(response=401, description="No note created by this user"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes access token and note id and finds
     * if there is any note existing on that User id and note id if so
     * it successfully returns that note id
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function readAllNotes(Request $request)
    {
        try {

            $currentUser = JWTAuth::authenticate($request->token);

            if (!$currentUser) {
                return response()->json([
                    'message' => 'Invalid Authorization Token',
                ], 401);
            }
            $notes = Note::getAllNotes($currentUser);

            if (!$notes) {
                return response()->json([
                    'message' => 'No note created by this user',
                ], 401);
            } else {
                return response()->json([
                    'notes' => $notes,
                ], 200);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     *   @OA\Post(
     *   path="/api/updatenotebyid",
     *   summary="update note",
     *   description="update user note",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"Updated_title","Updated_description","id"},
     *               @OA\Property(property="Updated_title", type="string"),
     *               @OA\Property(property="Updated_description", type="string"),
     *               @OA\Property(property="id"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="Note successfully updated"),
     *   @OA\Response(response=402, description="Notes not found"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes the User access token and note id which
     * user wants to update and finds the note id if it is existed
     * or not if so, updates it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function updateNoteById(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'Updated_title' => 'required|string|min:3|max:30',
            'Updated_description' => 'required|string|min:3|max:1000',
            'id' => 'required',
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid'], 400);
        }

        $currentUser = JWTAuth::authenticate($request->token);

        if (!$currentUser) {
            return response()->json([
                'message' => 'Invalid Authorization Token',
            ], 401);
        }

        $note = Note::where('user_id', $currentUser->id)->where('id', $request->id)->first();

        if (!$note) {

            return response()->json([
                'message' => 'Invalid id'
            ], 402);
        }

        $note->update([
            'title' => $request->Updated_title,
            'description' => $request->Updated_description,
            'user_id' => $currentUser->id,
        ]);

        return response()->json([
            'note' => $note,
        ], 200);
    }

    /**
     *   @OA\Delete(
     *   path="/api/deletenotebyid",
     *   summary="delete note",
     *   description="delete user note",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="Note successfully deleted"),
     *   @OA\Response(response=404, description="Notes not found"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes the User access token and note id which
     * user wants to delete and finds the note id if it is existed
     * or not if so, deletes it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function deleteNoteById(Request $request)
    {

        try {

            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer',
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => 'Invalid'], 400);
            }

            $currentUser = JWTAuth::authenticate($request->token);

            if (!$currentUser) {
                log::warning('Invalid Authorisation Token ');
                throw new FundoNotesException('Invalid Authorization Token', 401);
            }

            $note = Note::where('id', $request->id)->first();

            if (!$note) {
                Log::error('Notes Not Found');
                throw new FundoNotesException('Notes Not Found', 404);
            } else {
                $note->delete($note->id);
                return response()->json([
                    'mesaage' => 'Note deleted Successfully',
                ], 200);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     *   @OA\Post(
     *   path="/api/addnotelabel",
     *   summary="Add note label",
     *   description="Adiing note label",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"label_id","note_id"},
     *               @OA\Property(property="label_id", type="string"),
     *               @OA\Property(property="note_id", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="Note successfully updated"),
     *   @OA\Response(response=402, description="Notes not found"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes the User access token and note id which
     * user wants to update and finds the note id if it is existed
     * or not if so, updates it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function addNoteLabel(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'label_id' => 'required|integer',
                'note_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                Log::error('Invalid Authorization Token');
                throw new FundoNotesException('Invalid Authorization Token', 401);
            }

            $notes = Note::getNotesByNoteIdandUserId($request->note_id, $user->id);
            $label = Label::getLabelByLabelIdandUserId($request->label_id, $user->id);

            if (!$notes || !$label) {
                log::error('Note or label not found for user');
                throw new FundoNotesException('Note or label not found for user', 404);
            } else {
                // $labelnote = LabelNotes::where('user_id',$user->id)
                $labelnote = LabelNotes::getLabelNotesbyLabelIdNoteIdandUserId($request->label_id, $request->note_id, $user->id);
                if ($labelnote) {
                    log::info('Note already have this label');
                    throw new FundoNotesException('Note already have this label', 401);
                } else {
                    $notelabel = LabelNotes::createNoteLabel($request, $user->id);
                    log::info('Label created Successfully');
                    return response()->json([
                        'message' => 'Label note added Successfully',
                        'notelabel' => $notelabel,
                    ], 200);
                }
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     *   @OA\Delete(
     *   path="/api/deletenotelabel",
     *   summary="Delete note label",
     *   description="Deleting note label",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"label_id","note_id"},
     *               @OA\Property(property="label_id", type="string"),
     *               @OA\Property(property="note_id", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="Notelabel successfully deleted"),
     *   @OA\Response(response=402, description="Note label not found"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes the User access token and note id which
     * user wants to update and finds the note id if it is existed
     * or not if so, updates it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function deleteNoteLabel(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'label_id' => 'required|integer',
                'note_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                log::warning('Invalid Authorisation Token');
                return response()->json([
                    'message' => 'Invalid Token',
                ], 400);
            }

            //$labelnote = LabelNotes::getLabelNotesbyLabelIdNoteIdandUserId($request->label_id, $request->note_id, $user->id);
            $labelnotes = LabelNotes::where('note_id', $request->note_id)->where('label_id', $request->label_id)->where('user_id', $user->id)->first();
            if (!$labelnotes) {
                log::error('Label note not found');
                return response()->json([
                    'message' => 'label Note not found with this credentials',
                ], 400);
            }
            $labelnotes->delete($labelnotes->id);
            log::info('label note deleted successfully');
            return response()->json([
                'message' => 'label Note deleted Successfully',
            ], 401);
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     *   @OA\Post(
     *   path="/api/pinnote",
     *   summary="pin note label",
     *   description="Pining note label",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="Pinned note successfully "),
     *   @OA\Response(response=402, description="Note not found"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes the User access token and note id which
     * user wants to update and finds the note id if it is existed
     * or not if so, updates it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function pinNoteById(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $noteObject = new Note();
            $currentUser = JWTAuth::authenticate($request->id);
            $note = $noteObject->noteId($request->id);

            if (!$note) {
                Log::error('Notes Not Found', ['user' => $currentUser, 'id' => $request->id]);
                throw new FundoNotesException('Notes Not Found', 404);
            }

            if ($note->pin == 0) {
                if ($note->archive == 1) {
                    $note->archive = 0;
                    $note->save();
                }
                $note->pin = 1;
                $note->save();

                log::info('Note pinned successfully');
                return response()->json([
                    'message' => 'Note pinned Successfully',
                ], 201);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/unpinnote",
     *   summary="Unpin Note",
     *   description=" Unpin Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Note Unpinned Sucessfully"),
     *   @OA\Response(response=404, description="Notes not Found"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
    /**
     * This function takes the User access token and checks if it
     * authorised or not and it takes the note_id and unpin  it
     * successfully if notes is exist.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function unpinNoteById(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $noteObject = new Note();
            $currentUser = JWTAuth::parseToken()->authenticate();
            $note = $noteObject->noteId($request->id);

            if (!$note) {
                Log::error('Notes Not Found', ['user' => $currentUser, 'id' => $request->id],);
                return response()->json([
                    'message' => 'Note not found'
                ], 401);
            }

            if ($note->pin == 1) {
                $note->pin = 0;
                $note->save();

                Log::info('note unpin', ['user_id' => $currentUser, 'note_id' => $request->id]);
                return response()->json([
                    'status' => 201,
                    'message' => 'Note Unpinned Sucessfully'
                ], 201);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/archivenote",
     *   summary="Archive Note",
     *   description=" Archive Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Note Archived Sucessfully"),
     *   @OA\Response(response=404, description="Notes not Found"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     * This function takes the User access token and checks if it
     * authorised or not and it takes the note_id and Archives it
     * successfully if notes exist.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function archiveNoteById(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $noteObject = new Note();
            $currentUser = JWTAuth::parseToken()->authenticate();
            $note = $noteObject->noteId($request->id);

            if (!$note) {
                Log::error('Notes Not Found', ['user' => $currentUser, 'id' => $request->id]);
                return response()->json([
                    'message' => 'Note Not Found'
                ], 401);
            }

            if ($note->archive == 0) {
                if ($note->pin == 1) {
                    $note->pin = 0;
                    $note->save();
                }
                $note->archive = 1;
                $note->save();

                Log::info('notes Archived', ['user_id' => $currentUser, 'note_id' => $request->id]);
                return response()->json([
                    'message' => 'Note Archived Sucessfully'
                ], 201);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/unarchivenote",
     *   summary="Unarchive Note",
     *   description=" Unarchive Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Note Unarchived Sucessfully"),
     *   @OA\Response(response=404, description="Notes not Found"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     * This function takes the User access token and checks if it
     * authorised or not and it takes the note_id and Unarchives it
     * successfully if notes exist.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function unarchiveNoteById(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $noteObject = new Note();
            $currentUser = JWTAuth::parseToken()->authenticate();
            $note = $noteObject->noteId($request->id);

            if (!$note) {
                Log::error('Notes Not Found', ['user' => $currentUser, 'id' => $request->id]);
                return response()->json([
                    'message' => 'Notes not Found'
                ], 404);
            }

            if ($note->archive == 1) {
                $note->archive = 0;
                $note->save();

                Log::info('notes UnArchived', ['user_id' => $currentUser, 'note_id' => $request->id]);
                return response()->json([
                    'status' => 201,
                    'message' => 'Note UnArchived Sucessfully'
                ], 201);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/colournote",
     *   summary="Colour Note",
     *   description=" Colour Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id","colour"},
     *               @OA\Property(property="id", type="integer"),
     *               @OA\Property(property="colour", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Note coloured Sucessfully"),
     *   @OA\Response(response=404, description="Notes not Found"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     * This function takes the User access token and checks if it
     * authorised or not and it takes the note_id and colours it
     * successfully if notes exist.
     *
     * @return \Illuminate\Http\JsonResponse
     */


    function colourNoteById(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'colour' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $noteObject = new Note();
            $currentUser = JWTAuth::authenticate($request->token);
            $note = $noteObject->noteId($request->id);


            if (!$note) {
                Log::error('Notes Not Found', ['user' => $currentUser, 'id' => $request->id]);
                return response()->json([
                    'status' => 404,
                    'message' => 'Notes not Found'
                ], 404);
            }

            $colours  =  array(
                'green' => 'rgb(0,255,0)',
                'red' => 'rgb(255,0,0)',
                'blue' => 'rgb(0,0,255)',
                'yellow' => 'rgb(255,255,0)',
                'grey' => 'rgb(128,128,128)',
                'purple' => 'rgb(128,0,128)',
                'brown' => 'rgb(165,42,42)',
                'orange' => 'rgb(255,165,0)',
                'pink' => 'rgb(255,192,203)',
                'black' => 'rgb(0,0,0)',
                'silver' => 'rgb(192,192,192)',
                'teal' => 'rgb(0,128,128)',
                'white' => 'rgb(255,255,255)',
            );

            $colour_name = strtolower($request->colour);

            if (isset($colours[$colour_name])) {
                $note->colour = $colours[$colour_name];
                $note->save();

                Log::info('notes coloured', ['user_id' => $currentUser, 'note_id' => $request->id]);
                return response()->json([
                    'status' => 201,
                    'message' => 'Note coloured Sucessfully'
                ], 201);
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => 'Colour Not Specified in the List'
                ], 400);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Get(
     *   path="/api/getallpinnednotes",
     *   summary="Display All Pinned Notes",
     *   description=" Display All Pinned Notes",
     *   @OA\RequestBody(),
     *   @OA\Response(response=404, description="Notes Not Found"),
     *   @OA\Response(response=401, description="Invalid Authorization Token"),
     *   @OA\Response(response=200, description="Fetched All Pinned Notes Successfully"),
     *   security = {
     *      {"Bearer" : {}}
     *   }
     * )
     * 
     * This function takes the User access token and 
     * checks if it authorised or not. 
     * If Authorized, it returns all the pinned notes successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    function getAllPinnedNotes()
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            if (!$currentUser) {
                Log::error('Invalid Authorization Token');
                throw new FundoNotesException('Invalid Authorization Token', 401);
            } else {
                $userNotes = Note::getPinnedNotesandItsLabels($currentUser);
                if (!$userNotes) {
                    Log::error('Notes Not Found For User:: ' . $currentUser->id);
                    throw new FundoNotesException('Notes Not Found', 404);
                }
                //Cache::remember($userNotes);

                return response()->json([
                    'message' => 'Fetched All Pinned Notes Successfully',
                    'notes' => $userNotes
                ], 200);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Get(
     *   path="/api/getallarchivednotes",
     *   summary="Display All Archived Notes",
     *   description=" Display All Archived Notes",
     *   @OA\RequestBody(),
     *   @OA\Response(response=404, description="Notes Not Found"),
     *   @OA\Response(response=401, description="Invalid Authorization Token"),
     *   @OA\Response(response=200, description="Fetched All Archived Notes Successfully"),
     *   security = {
     *      {"Bearer" : {}}
     *   }
     * )
     * 
     * This function takes the User access token and 
     * checks if it authorised or not. 
     * If Authorized, it returns all the archived notes successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllArchivedNotes()
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            if (!$currentUser) {
                Log::error('Invalid Authorization Token');
                throw new FundoNotesException('Invalid Authorization Token', 401);
            } else {
                $userNotes = Note::getArchivedNotesandItsLabels($currentUser);
                if (!$userNotes) {
                    Log::error('Notes Not Found For User:: ' . $currentUser->id);
                    throw new FundoNotesException('Notes Not Found', 404);
                }
                //Cache::remember('notes');

                Log::info('Fetched All Archived Notes Successfully');
                return response()->json([
                    'message' => 'Fetched All Archived Notes Successfully',
                    'notes' => $userNotes
                ], 200);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/searchnotes",
     *   summary="Search Note",
     *   description=" Search Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"search"},
     *               @OA\Property(property="search", type="string")
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="Note Fetched Sucessfully"),
     *   @OA\Response(response=404, description="Notes Not Found"),
     *   @OA\Response(response=401, description="Invalid Authorization Token"),
     *   security = {
     *      {"Bearer" : {}}
     *   }
     * )
     * 
     * This function takes the User access token and search key 
     * if the access token is valid, it returns all the notes 
     * which has given search key for that particular user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchNotes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $searchKey = $request->input('search');
            $currentUser = JWTAuth::parseToken()->authenticate();

            if ($currentUser) {
                $usernotes = Note::getSearchedNote($searchKey, $currentUser);

                if ($usernotes == '[]') {
                    return response()->json([
                        'message' => 'Notes Not Found'
                    ], 404);
                }
                return response()->json([
                    'message' => 'Fetched Notes Successfully',
                    'notes' => $usernotes
                ], 200);
            }
            Log::error('Invalid Authorization Token');
            throw new FundoNotesException('Invalid Authorization Token', 401);
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Get(
     *   path="/api/paginationNote",
     *   summary="Pagination",
     *   description="Pagination of Notes",
     *   @OA\RequestBody(),
     *   @OA\Response(response=201, description="Pagination Applied to all Notes")
     * )
     * 
     * Function to view all notes,
     * 4 notes per page will be displayed.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function paginationNote()
    {
        $allNotes = Note::paginate(4);
        return response()->json([
            'message' => 'Pagination aplied to all Notes',
            'notes' =>  $allNotes,
        ], 201);
    }
}
