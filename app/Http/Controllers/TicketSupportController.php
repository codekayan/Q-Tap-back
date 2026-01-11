<?php

namespace App\Http\Controllers;

use App\Models\ticketSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketSupportController extends Controller
{
    public function index()
    {
        try {
            $tickets = ticketSupport::all();
            return response()->json($tickets);
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء جلب البيانات.'], 500);
        }
    }

    public function store(Request $request)
    {



        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['client_id'] = auth()->user()->user_id;
        $request['Customer_Name'] = auth()->user()->name;
        $request['Customer_Phone'] = auth()->user()->client->mobile;
        $request['Customer_Email'] = auth()->user()->email;


        $validator = Validator::make($request->all(), [
            'Customer_Name' => 'required|string|max:255',
            'client_id' => 'required|integer|exists:qtap_clients,id',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
            'Customer_Email' => 'required|email',
            'Customer_Phone' => 'required|string|max:15',
            'status' => 'nullable|in:open,in_progress,Done',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $ticket = ticketSupport::create($request->all());
            return response()->json(['message' => 'تم إضافة التذكرة بنجاح!', 'ticket' => $ticket], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء إضافة التذكرة.' . $e], 500);
        }
    }

    public function update(Request $request,  $id)
    {

        $ticketSupport = ticketSupport::find($id);

        if (!$ticketSupport) {
            return response()->json(['error' => 'التذكرة غير موجودة.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'Customer_Name' => 'nullable|string|max:255',
            'client_id' => 'nullable|integer',
            'brunch_id' => 'nullable|integer',
            'Customer_Email' => 'nullable|email',
            'Customer_Phone' => 'nullable|string|max:15',
            'status' => 'nullable|in:open,in_progress,Done',
            'content' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {

            $ticketSupport->update($request->all());

            return response()->json(['message' => 'تم تعديل التذكرة بنجاح!', 'ticket' => $ticketSupport]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تعديل التذكرة.'], 500);
        }
    }

    public function destroy(ticketSupport $ticketSupport)
    {
        try {
            $ticketSupport->forceDelete();
            return response()->json(['message' => 'تم حذف التذكرة بنجاح!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء حذف التذكرة.'], 500);
        }
    }
}
