<?php

namespace App\Http\Controllers;

use App\Models\chat;
use Illuminate\Http\Request;
use App\Events\PostCreated;

use App\Events\notify_msg;



class ChatController extends Controller
{


    public function index(Request $request)
    {
        $customer_id = $request->input('customer_id');

        $customer = chat::where('sender_id', $customer_id)->where('sender_type', 'customer')->get();
        $support = chat::where('receiver_id', $customer_id)->where('sender_type', 'support')->get();


        return response()->json([
            'success' => true,
            'customer' => $customer,
            'support' => $support,
        ]);
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'sender_id' => 'required|integer',
            'receiver_id' => 'required|integer',
            'message' => 'required|string|max:255',
            'sender_type' => 'required|in:customer,support',
        ]);

        $message = chat::create([
            'sender_id' => $validatedData['sender_id'],
            'receiver_id' => $validatedData['receiver_id'],
            'sender_type' => $validatedData['sender_type'],
            'message' => $validatedData['message'],
        ]);

        

        $type = 'chat';



        event(new notify_msg($message, $type));


        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully!',
            'data' => $message,
        ], 201);
    }


    public function update(Request $request, $id)
    {

        $chat = chat::find($id);


        $validatedData = $request->validate([
            'message' => 'required|string|max:255',
        ]);



        $chat->update([
            'message' => $validatedData['message'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message updated successfully!',
            'data' => $chat,
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(chat $chat)
    {
        //
    }
}
