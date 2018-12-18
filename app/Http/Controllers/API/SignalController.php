<?php

namespace App\Http\Controllers\API;

use App\Classes\QueLock;
use App\Jobs\CalculateClientOrderVolume;
use App\Jobs\GetClientFundsCheck;
use App\Jobs\GetSignalSymbolQuote;
use ccxt\bitmex;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller; // Must hook this name space. Otherwise 500 error while access from front end
use App\Client; // Link model
use App\Signal; // Link model
use App\Execution;
use Mockery\Exception; // Link model


class SignalController extends Controller
{
    public $symbolQuote;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //return Signal::latest()->paginate(5);
        return Signal::paginate(); // No pagination. For testing purposes. Real-time websocket data is loaded from WebSocketStream.php
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

        /* Action is not allowed if job and failed_job tables are not empty. Que tasks may be in progress */
        if (!QueLock::getStatus()){
            throw (new Exception('Some jobs are in progress! Wait until them finish or truncate Job and Failed job tables.'));
        }

        /* Validation rules */
        $this->validate($request,[
            'symbol' => 'required|string|max:8',
            'percent' => 'required|numeric|max:100',
            'leverage' => 'required|numeric|max:100',
            'direction' => 'required|string|max:6',
            'stop_loss_price' => 'required|string|max:14',
        ]);

        $response = Signal::create([
            'symbol' => $request['symbol'],
            'multiplier' => $request['multiplier'],
            'percent' => $request['percent'],
            'leverage' => $request['leverage'],
            'direction' => $request['direction'],
            'stop_loss_price' => $request['stop_loss_price']
        ]);

        $id = (array)$response;
        self::fillExecutionsTable($request, $id["\x00*\x00attributes"]['id']);

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
        /* Action is not allowed if job and failed_job tables are not empty. Que tasks may be in progress */
        if (!QueLock::getStatus()){
            throw (new Exception('Some jobs are in progress! Wait until them finish or truncate Job and Failed job tables.'));
        }

        $signal = Signal::findOrFail($id);

        /* Validation rule */
        $this->validate($request,[
            'symbol' => 'required|string|max:8',
            'percent' => 'required|numeric|max:100',
            'leverage' => 'required|numeric|max:100',
            'direction' => 'required|string|max:6',
            'stop_loss_price' => 'required|string|max:14',
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
     *
     * @param Request $request
     */
    private function fillExecutionsTable(Request $request, $id){
        foreach (Client::where('active', 1)->get() as $client){
            $execution = Execution::create([
                'signal_id' => $id,
                'client_id' => $client->id,
                'client_name' => $client->name,
                'symbol' => $request['symbol'],
                'direction' => $request['direction'],
                'percent' => $request['percent'],
                'leverage' => $request['leverage'],
                'status' => 'new',
            ]);
            GetClientFundsCheck::dispatch(new bitmex(), $execution);
        }

        GetSignalSymbolQuote::dispatch(new bitmex(), $request['symbol'], $id);
        // Add volume calculate job
        CalculateClientOrderVolume::dispatch($id);
    }

    /**
     * Calculate and fill volume for each client (each record in the table).
     *
     * @param Request $request
     * @param bitmex $exchange
     * @return string
     */
    public function fillVolume(Request $request){
        $exchange = new bitmex();
        /* Get quote */
        try {
            $this->symbolQuote = $exchange->fetch_ticker($request['symbol'])['last'];
        } catch (\Exception $e) {
            throw (new Exception($e->getMessage()));
            //return $e->getMessage();
        }

        /**
         * Run through all records in executions table
         * With a specific signal id
         * And where client funds != 0. If == 0 it means that API keys did not work and we did not get the balance
         */
    }
}
