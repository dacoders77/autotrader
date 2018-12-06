<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Symbol;
use Illuminate\Support\Facades\Cache;

class SymbolController extends Controller
{
    private $request;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Symbol::paginate();
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
     * @param  \Illuminate\Http\Request  $request
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

        $this->request = $request;

        /* Validation rule */
        $this->validate($request,[
            //'execution_name' => 'required|string|unique:symbols|max:10',
            'execution_name' => [
                function ($attributes, $value, $fail){
                    // Add master account in order to have trusted api keys
                    $response = \App\Classes\Client::checkSmallOrderExecution($_ENV['BITMEX_PUBLIC_API_KEY'], $_ENV['BITMEX_PRIVATE_API_KEY'], $this->request['execution_name']);
                    if (gettype($response) != "array") {
                        $fail($response);
                    }
                },
                'execution_name' => 'unique:symbols'
            ],
            //'leverage_name' => 'required|string|unique:symbols|max:10',
            'leverage_name' => [
                function ($attributes, $value, $fail){

                    $response = \App\Classes\Client::setLeverageCheck($_ENV['BITMEX_PUBLIC_API_KEY'], $_ENV['BITMEX_PRIVATE_API_KEY'], $this->request['leverage_name']);
                    if (gettype($response) != "array") {
                        $fail($response);
                    }
                },
                'leverage_name' => 'unique:symbols'
            ],
            'formula' => 'required|string|max:40',
            //'min_exec_quantity' => 'required|string|max:8',
            'info' => 'sometimes|nullable|string|max:50'
        ]);

        /* Start websocket subscription */
        $arr = array();
        array_push($arr, 'instrument:' . $request['leverage_name']);
        Cache::put('object', ['subscribe' =>  $arr], 5);

        return Symbol::create([
            'execution_name' => $request['execution_name'],
            'leverage_name' => $request['leverage_name'],
            'formula' => $request['formula'],
            'min_exec_quantity' => 1,
            'info' => $request['info'],
        ]);

        return $request;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $symbol = Symbol::findOrFail($id);
        $symbol->delete();
        /* Sop websocket subscription */
        //Cache::put('object', ['unsubscribe' => true], 5);
        return ['message' => 'Symbol deleted'];
    }
}
