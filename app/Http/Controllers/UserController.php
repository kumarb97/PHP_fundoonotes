<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\mailer;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use App\Notifications\PasswordResetRequest;
use App\Exceptions\FundoNotesException;

class UserController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/register",
     *   summary="register",
     *   description="register the user for login",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"firstname","lastname","email", "password", "password_confirmation"},
     *               @OA\Property(property="firstname", type="string"),
     *               @OA\Property(property="lastname", type="string"),
     *               @OA\Property(property="email", type="string"),
     *               @OA\Property(property="password", type="password"),
     *               @OA\Property(property="password_confirmation", type="password")
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="User successfully registered"),
     *   @OA\Response(response=401, description="The email has already been taken"),
     * )
     * Register user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'firstname' => 'required|string|between:2,100',
                'lastname' => 'required|string|between:2,100',
                'email' => 'required|string|email|max:150',
                'password' => 'required|string|min:6',
                'password_confirmation' => 'required|same:password'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $userCheck = User::getUserByEmail($request->email);
            if ($userCheck) {
                Log::info('The email has already been taken: ');
                throw new FundoNotesException('The email has already been taken.', 401);
            }

            $user = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            return response()->json([
                'message' => 'User successfully registered',
                'user' => $user
            ], 201);
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/login",
     *   summary="login",
     *   description="login",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email", "password"},
     *               @OA\Property(property="email", type="string"),
     *               @OA\Property(property="password", type="password"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="login Success"),
     *   @OA\Response(response=401, description="we can not find the user with that e-mail address You need to register first"),
     * )
     * login user
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function login(Request $request)
    {
        try {

            $credentials = $request->only('email', 'password');

            //valid credential
            $validator = Validator::make($credentials, [
                'email' => 'required|email',
                'password' => 'required|string|min:6|max:50'
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => 'Invalid credentials entered'], 400);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                Log::error('Not a Registered Email');
                throw new FundoNotesException('Not a Registered Email', 404);
                return response()->json([
                    'message' => 'Email is not registered',
                ], 404);
            } elseif (!Hash::check($request->password, $user->password)) {
                Log::error('Wrong Password');
                throw new FundoNotesException('Wrong Password', 402);
                return response()->json([
                    'message' => 'Wrong password'
                ], 402);
            }
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                ], 400);
            }

            //Token created, return with success response and jwt token
            Log::info('Login Successful');
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
            ], 200);
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     *   @OA\Post(
     *   path="/api/logout",
     *   summary="logout",
     *   description="logout",
     *   @OA\RequestBody(
     *   @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"token"},
     *               @OA\Property(property="token", type="string"),
     *    ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="User successfully registered"),
     *   @OA\Response(response=401, description="The email has already been taken"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * Logout user
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function logout(Request $request)
    {
        $user = JWTAuth::authenticate($request->token);

        if (!$user) {
            log::warning('Invalid Authorisation ');
            return response()->json([
                'message' => 'Invalid token'
            ], 401);
        } else {
            JWTAuth::invalidate($request->token);
            log::info('User successfully logged out');
            return response()->json([
                'success' => true,
                'message' => 'User has been logged out'
            ], 200);
        }
    }

    /**
     * * @OA\Get(
     *   path="/api/getuser",
     *   summary="getuser",
     *   description="getuser",
     *   @OA\RequestBody(
     *    ),
     *   @OA\Response(response=201, description="Found User successfully"),
     *   @OA\Response(response=401, description="User cannot be found"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * getuser
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getUser(Request $request)
    {
        $user = JWTAuth::authenticate($request->token);

        if (!$user) {
            log::error('Invalid authorisation token');
            return response()->json([
                'message' => 'Invalid token'
            ], 401);
        } else {
            return response()->json([
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
            ], 200);
        }
    }


    /**
     * @OA\Post(
     *  path="/api/forgotpassword",
     *  summary="Forgot Password",
     *  description="Forgot Password for an user",
     *  @OA\RequestBody(
     *      @OA\JsonContent(),
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              type="object",
     *              required={"email"},
     *              @OA\Property(property="email", type="string")
     *          ),  
     *      ),
     *  ),
     *  @OA\Response(response=404, description="Not a Registered Email"),
     *  @OA\Response(response=201, description="Reset Password Token Sent to your Email")
     * )
     * 
     * This Function takes user authorization token and email and
     * send a forgot password mail to that user having the token to reset password
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {

        try {

            $email = $request->only('email');

            //validate email
            $validator = Validator::make($email, [
                'email' => 'required|email'
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                log::error('Not a registered email');
                throw new FundoNotesException('Not a Registered Email', 404);
            } else {
                $name = $user->firstname;

                $token = JWTAuth::fromUser($user);

                if ($user) {
                    $delay = now()->addSeconds(120);
                    $user->notify((new PasswordResetRequest($user->email, $token))->delay($delay));
                    Log::info('Reset Password Token Sent to your Email');
                    return response()->json([
                        'message' => 'Reset Password Token Sent to your Email',
                        'delay' => $delay,
                    ], 201);
                }
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }

    /**
     * @OA\Post(
     *  path="/api/resetpassword",
     *  summary="Reset User Password",
     *  description="Reset User Password using the token sent to the mail",
     *  @OA\RequestBody(
     *      @OA\JsonContent(),
     *      @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              type="object",
     *              required={"new_password","password_confirmation"},
     *              @OA\Property(property="new_password", type="string"),
     *              @OA\Property(property="password_confirmation", type="string")
     *          ),  
     *      ),
     *  ),
     *  @OA\Response(response=401, description="Invalid Authorization Token"),
     *  @OA\Response(response=201, description="Password Reset Successful"),
     *  security={
     *      {"Bearer": {}}
     *  }
     * )
     * 
     * This function takes user authorization token and reset the password
     * with the new password and update the new password of user
     * 
     * @return \Illuminate\Http\JsonResponse
     */

    public function resetPassword(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'new_password' => 'required|string|min:6|max:50',
                'password_confirmation' => 'required|same:new_password',
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
            $currentUser = JWTAuth::authenticate($request->token);

            if (!$currentUser) {
                log::warning('Invalid Authorisation Token ');
                throw new FundoNotesException('Invalid Authorization Token', 401);
            } else {
                $user = User::updatePassword($currentUser, $request->new_password);
                log::info('Password updated successfully');
                return response()->json([
                    'message' => 'Password Reset Successful'
                ], 201);
            }
        } catch (FundoNotesException $exception) {
            return response()->json([
                'message' => $exception->message()
            ], $exception->statusCode());
        }
    }
}
