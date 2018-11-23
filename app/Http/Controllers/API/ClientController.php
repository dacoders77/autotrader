<?php

namespace App\Http\Controllers\API;

use ccxt\Exchange;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Client; // Model link
use Mockery\Exception;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Client::latest()->paginate(5);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /*
        $error = \Illuminate\Validation\ValidationException::withMessages([
            'field_name_1' => ['Validation Message #1'],
            'field_name_2' => ['Validation Message #2'],
        ]);
        throw $error;
        */

        /** Validation rule
         * @see https://laravel-news.com/custom-validation-rule-objects
         */
        $this->validate($request, [
            'name' => 'required|string|max:20',
            'email' => 'sometimes|nullable|email',
            'api' =>
                [function ($attributes, $value, $fail) {
                    if (gettype(\App\Classes\Client::checkBalance()) != "double") {
                        $fail("Can't get account balance! Wrong API keys or no privileges.");
                    }
                },
                function ($attributes, $value, $fail) {
                    if (\App\Classes\Client::checkSmallOrderExecution()) {
                        $fail("Can't execute order on provided account! Wrong API keys or no privileges.");
                    }
                },
                'api' => 'max:20'],
            'api_secret' => 'required|unique:clients|string|max:50',

        ]);


        /*
        if (\App\Classes\Client::checkBalance($request['api'], $request['api_secret'])){
            //
        }else{
            $error = \Illuminate\Validation\ValidationException::withMessages([
                'field_name_1' => ["Can't access client's account with given API key pair"],
                'field_name_2' => ['Validation Message #2'],
            ]);
            //throw $error;
            //throw (new Exception('Cant create client. ClientController.php'));
        }
        */


        // Small order check. executeSmallOrderCheck

        return Client::create([
            'name' => $request['name'],
            'last_name' => $request['last_name'],
            'telegram' => $request['telegram'],
            'email' => $request['email'],
            'api' => $request['api'],
            'api_secret' => $request['api_secret'],
            'info' => $request['info'],
            //'bio' => $request['bio'],
            //'photo' => $request['photo'],
            //'password' => Hash::make($request['password']),
            //'password' => '12356',
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        /* Validation rule */
        $this->validate($request, [
            'name' => 'required|string|max:20',
            'email' => 'sometimes|nullable|email',
            'api' => 'required|string|unique:clients|max:50',
            'api_secret' => 'required|string|unique:clients|max:50'
        ]);

        $client->update($request->all());
        return ['message' => 'Updated client info'];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();
        return ['message' => 'Client deleted'];
    }
}
