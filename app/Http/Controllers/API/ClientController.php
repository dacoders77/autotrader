<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Client;

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /* Validation rule */
        $this->validate($request,[
            'name' => 'required|string|max:20',
            'api' => 'required|string|max:50',
            'api_secret' => 'required|string|max:50'
        ]);

        return Client::create([
            'name' => $request['name'],
            'last_name' => $request['last_name'],
            'telegram' => $request['telegram'],
            //'bio' => $request['bio'],
            //'photo' => $request['photo'],
            //'password' => Hash::make($request['password']),
            //'password' => '12356',
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
        $client = Client::findOrFail($id);

        /* Validation rule */
        $this->validate($request,[
            'name' => 'required|string|max:20',
            'api' => 'required|string|max:50',
            'api_secret' => 'required|string|max:50'
        ]);

        $client->update($request->all());
        return ['message' => 'Updated client info'];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
