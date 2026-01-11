<?php

namespace App\Http\Controllers;

use App\Models\we_serv;
use Illuminate\Http\Request;

class WeServController extends Controller
{


    public function index()
    {
        $we_serv = we_serv::all();
        if ($we_serv) {
            return response()->json([
                'success' => true,
                'all we serve' => $we_serv
            ]);
        }
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'nullable|string',
        ]);

        if ($request->hasFile('img')) {
            $imagePath = $request->file('img')->store('images/we_serv', 'public');
            $data['img'] = 'storage/' . $imagePath;
        }

        $weServ = we_serv::create($data);
        return response()->json([
            'success' => true,
            'we_serv' => $weServ
        ]);
    }



    public function update(Request $request,  $id)
    {

        $we_serv = we_serv::find($id);

        if ($we_serv) {
            # code...
            $data = $request->validate([
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'name' => 'nullable|string',
            ]);

            if ($request->hasFile('img')) {
                $imagePath = $request->file('img')->store('images/we_serv', 'public');
                $data['img'] = 'storage/' . $imagePath;
            }

              $we_serv->update($data);


            return response()->json([
                'success' => true,
                'we_serv' => $we_serv
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'not found'
            ]);
        }
    }


    public function destroy($id)
    {
        $we_serv = we_serv::find($id);

        if ($we_serv) {
            $we_serv->delete();
            response()->json([
                'success' => true,
                'message' => 'deleted successfuly'
            ]);
        } else {
            response()->json([
                'success' => false,
                'message' => 'not found'
            ]);
        }
    }
}
