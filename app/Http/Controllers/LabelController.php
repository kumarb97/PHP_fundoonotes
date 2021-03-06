<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Label;
use App\Exceptions\FundoNotesException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;


class LabelController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/createlabel",
     *   summary="create label",
     *   description="create label",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"label_name"},
     *               @OA\Property(property="label_name", type="string"),          
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="label created Sucessfully"),
     *   @OA\Response(response=401, description="Invalid token"),
     * security={
     *       {"Bearer": {}}
     *     }
     * )
     * Create Label.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function createLabel(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'label_name' => 'required|string|between:2,15',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = JWTAuth::authenticate($request->token);

            if (!$user) {
                Log::error('Invalid Authorization Token');
                throw new FundoNotesException('Invalid Authorization Token', 401);
            }

            $label = Label::getLabelByLabelNameandUserId($request->labelname, $user->id);
            if ($label) {
                Log::info('Label Name Already Exists');
                throw new FundoNotesException('Label Name Already Exists', 409);
            }

            $label = Label::create([
                'label_name' => $request->label_name,
                'user_id' => $user->id,
            ]);
            Log::info('Label Added Sucessfully');
            return response()->json([
                'message' => 'label created sucessfully',
                'label' => $label,
            ], 201);
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/readlabelbyid",
     *   summary="Read Label",
     *   description=" Read Label ",
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
     *   @OA\Response(response=201, description="label found Sucessfully"),
     *   @OA\Response(response=404, description="label not Found"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * Read label by id
     * @return \Illuminate\Http\JsonResponse
     */

    function readLabelById(Request $request)
    {
        try {

            $validator = Validator::make($request->only('id'), [
                'id' => 'required'
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => 'Invalid'], 400);
            }

            $currentUser = JWTAuth::authenticate($request->token);

            if (!$currentUser) {
                Log::error('Invalid Authorization Token');
                throw new FundoNotesException('Invalid Authorization Token', 401);
            }

            $currentid = $currentUser->id;
            $label = Label::where('user_id', $currentid)->where('id', $request->id)->first();

            if (!$label) {
                Log::info('Label Not Found');
                throw new FundoNotesException('Label Not Found', 404);
            } else {
                return response()->json(['label' => $label], 201);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     *   @OA\Get(
     *   path="/api/readalllabels",
     *   summary="read labels",
     *   description="user read labels",
     *   @OA\RequestBody(
     *    ),
     *   @OA\Response(response=201, description="labels shown suucessfully"),
     *   @OA\Response(response=401, description="No label created by this user"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function readAllLabel(Request $request)
    {
        try {

            $currentUser = JWTAuth::authenticate($request->token);

            if (!$currentUser) {
                Log::error('Invalid Authorization Token');
                throw new FundoNotesException('Invalid Authorization Token', 401);
            }

            Cache::put('labels', Label::getLabelsByUserId($currentUser->id), 60 * 60 * 24);
            $labels = Cache::get('labels');
            //$labels = Label::where('user_id', $currentUser->id)->get();

            if (!$labels) {
                Log::error('Labels Not Found');
                throw new FundoNotesException('Labels Not Found', 404);
            } else {
                return response()->json([
                    'labels' => $labels,
                ], 201);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     *   @OA\Post(
     *   path="/api/updatelabelbyid",
     *   summary="update label",
     *   description="update user label",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"Updated_name","id"},
     *               @OA\Property(property="Updated_name", type="string"),
     *               @OA\Property(property="id"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="Label successfully updated"),
     *   @OA\Response(response=402, description="labels not found"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function updateLabelById(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'Updated_name' => 'required|string|min:3|max:30',
                'id' => 'required|integer'
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => 'Invalid'], 400);
            }

            $currentUser = JWTAuth::authenticate($request->token);

            if (!$currentUser) {
                Log::error('Invalid Authorization Token');
                throw new FundoNotesException('Invalid Authorization Token', 401);
            }

            $label = Label::where('user_id', $currentUser->id)->where('id', $request->id)->first();

            if (!$label) {
                Log::error('Label Not Found');
                throw new FundoNotesException('Label Not Found', 404);
            }

            $label->update([
                'label_name' => $request->Updated_name,
                'user_id' => $currentUser->id,
            ]);
            Cache::forget('labels');
            Cache::forget('notes');

            return response()->json([
                'message' => 'label updated successfully',
                'label' => $label,
            ], 201);
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     *   @OA\Delete(
     *   path="/api/deletelabelbyid",
     *   summary="delete label",
     *   description="delete user label",
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
     *   @OA\Response(response=200, description="label successfully deleted"),
     *   @OA\Response(response=404, description="labels not found"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function deleteLabelById(Request $request)
    {
        try {

            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer'
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => 'Invalid'], 400);
            }

            $currentUser = JWTAuth::authenticate($request->token);

            if (!$currentUser) {
                Log::error('Invalid Authorization Token');
                throw new FundoNotesException('Invalid Authorization Token', 401);
            }

            $label = Label::where('id', $request->id)->first();

            if (!$label) {
                Log::error('Label Not Found');
                throw new FundoNotesException('Label Not Found', 404);
            } else {
                Cache::forget('labels');
                Cache::forget('notes');

                $label->delete($label->id);
                return response()->json([
                    'mesaage' => 'label deleted Successfully',
                ], 200);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }
}
