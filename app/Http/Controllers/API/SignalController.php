<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; // Must hook this name space. Otherwise 500 error while access from front end
use App\Client; // Link model
use App\Signal; // Link model
use App\Execution; // Link model


class SignalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Signal::latest()->paginate(5);
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
        /* Validation rules */
        $this->validate($request,[
            'symbol' => 'required|string|max:8',
            //'multiplier' => 'required|string|max:10',
            'percent' => 'required|string|max:4',
            'leverage' => 'required|string|max:4',
            'direction' => 'required|string|max:6',
            //'password' => 'required|string|min:6'
        ]);

        $response = Signal::create([
            'symbol' => $request['symbol'],
            'multiplier' => $request['multiplier'],
            'percent' => $request['percent'],
            'leverage' => $request['leverage'],
            'direction' => $request['direction'],
        ]);

        $id = (array)$response;
        //dump($x["\x00*\x00attributes"]['id']);
        self::fillExecutionsTable($request, $id["\x00*\x00attributes"]['id']);



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
        $signal = Signal::findOrFail($id);

        /* Validation rule */
        $this->validate($request,[
            'symbol' => 'required|string|max:8',
            //'multiplier' => 'required|numeric|max:10',
            'percent' => 'required|string|max:4',
            'leverage' => 'required|string|max:4',
            'direction' => 'required|string|max:6',
            //'password' => 'required|string|min:6'
        ]);

        $signal->update($request->all());
        return ['message' => 'Updated signal info'];

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $signal = Signal::findOrFail($id);
        $signal->delete();
        return ['message' => 'Signal deleted'];
    }

    /* NON DEFAULT API METHODS */

    /**
     * Fill executions table with a job. A job - symbolize a signal executed on a client account.
     * Clone signal to all clients.
     * Quantity of records = quantity of clients
     * @param Request $request
     */
    private function fillExecutionsTable(Request $request, $id){
        foreach (Client::where('active', 1)->get() as $client){
            Execution::create([
                'signal_id' => $id,
                'client_id' => $client->id,
                'client_name' => $client->name,
                'symbol' => $request['symbol'],

                'direction' => $request['direction'],
                'percent' => $request['percent'],
                'leverage' => $request['leverage'],

                'status' => 'new'
            ]);
        }
    }


}
