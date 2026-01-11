<?php

namespace App\Http\Controllers;

use App\Models\clients_transactions;
use Illuminate\Http\Request;

class ClientsTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients_transactions = clients_transactions::all();
        return response()->json([
            'success' => true,
            'clients_transactions' => $clients_transactions
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(clients_transactions $clients_transactions)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(clients_transactions $clients_transactions)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, clients_transactions $clients_transactions)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(clients_transactions $clients_transactions)
    {
        //
    }
}
