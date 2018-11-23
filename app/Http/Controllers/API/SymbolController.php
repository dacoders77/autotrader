<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Symbol;

class SymbolController extends Controller
{
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

        /* Validation rule */
        $this->validate($request,[
            'execution_name' => 'required|string|unique:symbols|max:10',
            'leverage_name' => 'required|string|unique:symbols|max:10',
            'formula' => 'required|string|max:40',
            'min_exec_quantity' => 'required|string|max:8',
            'info' => 'sometimes|nullable|string|max:50'
        ]);

        return Symbol::create([
            'execution_name' => $request['execution_name'],
            'leverage_name' => $request['leverage_name'],
            'formula' => $request['formula'],
            'min_exec_quantity' => $request['min_exec_quantity'],
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
        return ['message' => 'Symbol deleted'];
    }
}
