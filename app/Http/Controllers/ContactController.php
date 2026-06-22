<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function sendEmail(Request $request)
    {
        // Validate
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'service' => 'required|string|max:255',
            'project_details' => 'required|string|max:5000',
        ]);

        try {
            // Store in database
            DB::table('contact_submissions')->insert([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone ?? null,
                'service' => $request->service,
                'project_details' => $request->project_details,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your message has been submitted successfully! We will get back to you within 24 hours.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Contact form error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit message. Please try again.'
            ], 500);
        }
    }
}