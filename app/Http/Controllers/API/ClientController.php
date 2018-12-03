<?php

namespace App\Http\Controllers\API;

use ccxt\Exchange;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Client; // Model link
use Mockery\Exception;

class ClientController extends Controller
{
    private $api;
    private $apiSecret;

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
        $this->api = $request['api'];
        $this->apiSecret = $request['api_secret'];

        $this->validate($request, [
            'name' => 'required|string|max:20',
            'email' => 'sometimes|nullable|email',
            'api' =>
                [function ($attributes, $value, $fail) {
                    $response = \App\Classes\Client::checkBalance($this->api, $this->apiSecret, 'checkBalance');
                    if (gettype($response) != "double") {
                        $fail($response);
                    }
                },
                function ($attributes, $value, $fail) {
                    $response = \App\Classes\Client::checkSmallOrderExecution($this->api, $this->apiSecret);
                    if (gettype($response) != "array") {
                        $fail($response);
                    }
                },
                'api' => 'max:50'],
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
            'active' => true,
            'valid' => true,
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

        $this->api = $request['api'];
        $this->apiSecret = $request['api_secret'];

        /* Validation rule */
        $this->validate($request, [
            'name' => 'required|string|max:20',
            'email' => 'sometimes|nullable|email',
            'api' =>
                [function ($attributes, $value, $fail) {
                    $response = \App\Classes\Client::checkBalance($this->api, $this->apiSecret, 'checkBalance');
                    if (gettype($response) != "double") {
                        $fail($response);
                    }
                },
                    function ($attributes, $value, $fail) {
                        $response = \App\Classes\Client::checkSmallOrderExecution($this->api, $this->apiSecret);
                        if (gettype($response) != "array") {
                            $fail($response);
                        }
                    },
                    'api' => 'max:50'],
            'api_secret' => 'required|string|max:50',
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

    /**
     * Validate client via api keys access and small order execution.
     *
     * @param Request $request
     * @return boolean
     */
    public function validateClient(Request $request ){

        // Balance
        $response = \App\Classes\Client::checkBalance($request['api'], $request['api_secret'], 'checkBalance');
        if (gettype($response) == "double"){
            Client::where('id', $request['id'])->update([
                'valid' => true
            ]);
        }
        else{
            Client::where('id', $request['id'])->update([
                'valid' => false
            ]);
            throw (new Exception(json_encode($response)));
        }

        // Small order
        $response = \App\Classes\Client::checkSmallOrderExecution($request['api'], $request['api_secret']);
        if (gettype($response) == "array"){
            Client::where('id', $request['id'])->update([
                'valid' => true
            ]);
        }
        else{
            Client::where('id', $request['id'])->update([
                'valid' => false
            ]);
            throw (new Exception(json_encode($response)));
        }

        return (['message' => 'Client valid! Balance and small order check passed.']);
    }

    public function activateClient(Request $request){
        Client::where('id', $request['id'])->update([
            'active' => !Client::where('id', $request['id'])->value('active')
        ]);
        return($request);
    }

    public function getClientTradingBalance(Request $request){
        $response = \App\Classes\Client::checkBalance($request['api'], $request['api_secret'], 'getTradingBalance');
        // Parse response
        $arr = "";
        foreach ($response as $symbol){
            $arr .= $symbol['symbol'] . ":" . $symbol['currentQty'] . ", ";
        }
        // Update DB
        Client::where('id', $request['id'])->update([
            'balance_symbols' => $arr
        ]);
        return (["message" => "Client's trading balance received", "arr" => $arr]);
    }
}
