<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\bookstore;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    public function bookStore(Request $request)
    {
        $validator = Validator::make($request->all (),[
            'bookname' => 'required|string|between:2,100',
            'authorname' => 'required|string|between:2,100',
            'bookprice' => 'required|integer|between:2,100',
            ]);
            if ($validator->fails()) {
                return response() -> json($validator->errors()->toJson([
                    'message'=> 'Invalid entry'
                ]), 400);
            }
            // $this->validate($request, [
            //     'token' => 'required'
            // ]);

        $user = JWTAuth::authenticate($request->token);

        if(!$user)
        {
            return response()->json([
                'message'=>'first login is requred'
            ],406);
        }
        else{
                $book = bookstore::create([
                    'bookname' => $request->bookname,
                    'authorname' => $request->authorname,
                    'bookprice' => $request->bookprice,
                ]);
        
                return response()->json([
                    'message' => 'User successfully stored data',
                    'book'=> $book
                ],200);
            
        }

    }
}
