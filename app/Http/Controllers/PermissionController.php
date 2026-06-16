<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    /**
     * Get all permissions
     */
    public function Index(Request $request)
    {
        try {
            $permissions = DB::table('permissions')
                ->select(['id', 'model', 'type', 'name', 'notes'])
                ->get();
            
            return response()->json([
                'success' => true,
                'permissions' => $permissions
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get current user with their permissions
     */
    public function UserPermissions(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            // Get permissions through role
            $permissions = DB::table('permissions')
                ->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
                ->where('role_permission.role_id', $user->role_id)
                ->select('permissions.id', 'permissions.model', 'permissions.type', 'permissions.name', 'permissions.notes')
                ->get();
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                ],
                'permissions' => $permissions
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}