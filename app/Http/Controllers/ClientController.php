<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function Index(Request $request)
    {
        try {
            $clients = DB::table('clients')->select('id', 'name', 'notes')->get();
            return response()->json([
                'status' => true,
                'data' => $clients,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
