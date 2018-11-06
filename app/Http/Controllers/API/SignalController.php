<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; // Must hook this name space. Otherwise 500 error while access from front end
use App\Signal; // Link model

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

        /* Validation rule */

        $this->validate($request,[
            'symbol' => 'required|string|max:6',
            'percent' => 'required|string|max:3',
            'leverage' => 'required|numeric|max:3',
            'direction' => 'required|string|max:6',
            //'password' => 'required|string|min:6'
        ]);

        return Signal::create([
            'symbol' => $request['symbol'],
            'percent' => $request['percent'],
            'leverage' => $request['leverage'],
            'direction' => $request['direction'],
        ]);

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
            'symbol' => 'required|string|max:6',
            'percent' => 'required|numeric|max:3',
            'leverage' => 'required|numeric|max:3',
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
}
