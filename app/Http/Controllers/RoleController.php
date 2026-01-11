<?php

namespace App\Http\Controllers;

use App\Models\role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{

    public function index(Request $request)
    {
        $roles = role::where('brunch_id', $request->brunch_id)->get();
        return response()->json([
            'success' => true,
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->where(function ($query) use ($request) {
                    return $query->where('brunch_id', $request->brunch_id);
                }),
            ],
            'menu' => 'required|boolean',
            'users' => 'required|boolean',
            'orders' => 'required|boolean',
            'wallet' => 'required|boolean',
            'setting' => 'required|boolean',
            'support' => 'required|boolean',
            'dashboard' => 'required|boolean',
            'customers_log' => 'required|boolean',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $role = role::create($data);

        return response()->json([
            'success' => true,
            'role' => $role
        ]);
    }


    public function update(Request $request, $id)
    {

        $role = role::find($id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required',
                Rule::unique('roles', 'name')->ignore($role->id)
            ],
            'menu' => 'required|boolean',
            'users' => 'required|boolean',
            'orders' => 'required|boolean',
            'wallet' => 'required|boolean',
            'setting' => 'required|boolean',
            'support' => 'required|boolean',
            'dashboard' => 'required|boolean',
            'customers_log' => 'required|boolean',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $role->update($data);

        return response()->json([
            'success' => true,
            'role' => $role
        ]);
    }


    public function destroy($id)
    {
        $role = role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $role->forceDelete();
        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }
}
