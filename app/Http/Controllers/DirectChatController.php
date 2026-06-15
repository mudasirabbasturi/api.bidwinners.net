<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DirectChatController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | User List  (used by Sidebar "Direct Chat" tab)
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/chat-user-list
     * Returns all users (except the authenticated user) with id and name only.
     */
    public function chatUserList(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $authId = $request->user()->id;

        $users = DB::table('users')
            ->where('id', '!=', $authId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json(['users' => $users]);
    }

    /*
    |--------------------------------------------------------------------------
    | Direct Chat – Messages
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/direct-chat-messages/{receiver_id}
     * Returns all messages between the authenticated user and the given user,
     * ordered by created_at ascending.
     */
    public function getDirectMessages(Request $request, $receiver_id)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $authId = $request->user()->id;

        $messages = DB::table('direct_chats')
            ->where(function ($q) use ($authId, $receiver_id) {
                $q->where('direct_chats.sender_id', $authId)->where('direct_chats.receiver_id', $receiver_id);
            })
            ->orWhere(function ($q) use ($authId, $receiver_id) {
                $q->where('direct_chats.sender_id', $receiver_id)->where('direct_chats.receiver_id', $authId);
            })
            ->leftJoin('direct_chats as reply_msg', 'direct_chats.reply_to_id', '=', 'reply_msg.id')
            ->leftJoin('users as sender_user', 'direct_chats.sender_id', '=', 'sender_user.id')
            ->leftJoin('users as reply_sender', 'reply_msg.sender_id', '=', 'reply_sender.id')
            ->select(
                'direct_chats.id',
                'direct_chats.sender_id',
                'direct_chats.receiver_id',
                'direct_chats.message',
                'direct_chats.reply_to_id',
                'direct_chats.created_at',
                'sender_user.name as user_name',
                'reply_msg.message as reply_to_message',
                'reply_sender.name as reply_to_user_name'
            )
            ->orderBy('direct_chats.created_at', 'asc')
            ->get();

        // Get all message IDs
        $messageIds = $messages->pluck('id')->toArray();

        // Fetch file attachments from media table
        $attachments = [];
        if (!empty($messageIds)) {
            $attachments = DB::table('media')
                ->where('model_type', 'App\\Models\\DirectChat')
                ->whereIn('model_id', $messageIds)
                ->select('model_id', 'file_path', 'id as media_id', 'category')
                ->get()
                ->keyBy('model_id');
        }

        // Format messages with file attachments
        $formattedMessages = $messages->map(function ($row) use ($attachments) {
            $attachment = $attachments[$row->id] ?? null;
            
            $file = null;
            if ($attachment) {
                $file = [
                    'name' => basename($attachment->file_path),
                    'url'  => '/' . $attachment->file_path,
                    'type' => $attachment->category ?? 'direct_chat',
                ];
            }

            return [
                'id'                 => $row->id,
                'senderId'           => $row->sender_id,
                'receiverId'         => $row->receiver_id,
                'user_id'            => $row->sender_id,
                'user_name'          => $row->user_name,
                'user_avatar'        => null,
                'content'            => $row->message ?: '',
                'message'            => $row->message ?: '',
                'file'               => $file,
                'reply_to_id'        => $row->reply_to_id,
                'reply_to_message'   => $row->reply_to_message ?? null,
                'reply_to_user_name' => $row->reply_to_user_name ?? null,
                'timestamp'          => $row->created_at,
                'created_at'         => $row->created_at,
            ];
        });

        return response()->json(['chat_messages' => $formattedMessages]);
    }

    /**
     * POST /api/direct-chat-send-message
     * Send a direct message (with optional file attachment and reply).
     * Files stored in: public/uploads/media/direct_chat/
     * File metadata stored in: media table
     */
    public function sendDirectMessage(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'message'     => 'nullable|string|max:5000',
            'file'        => 'nullable|file|max:20480',
            'reply_to_id' => 'nullable|integer',
        ]);

        if (!$request->message && !$request->hasFile('file')) {
            return response()->json(['success' => false, 'error' => 'Message or file required'], 422);
        }

        // Insert message first (without file_path and file_type)
        $msgId = DB::table('direct_chats')->insertGetId([
            'sender_id'   => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message ?? '',
            'reply_to_id' => $request->reply_to_id,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $fileData = null;

        // Handle file upload - store in media table (like ProjectChat)
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = public_path('uploads/media/direct_chat');
            
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            
            $file->move($path, $fileName);
            
            // Insert into media table
            $mediaId = DB::table('media')->insertGetId([
                'user_id'    => $request->user()->id,
                'file_path'  => 'uploads/media/direct_chat/' . $fileName,
                'category'   => 'direct_chat',
                'model_type' => 'App\\Models\\DirectChat',
                'model_id'   => $msgId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $fileData = [
                'id'   => $mediaId,
                'name' => $fileName,
                'url'  => '/uploads/media/direct_chat/' . $fileName,
                'type' => $file->getMimeType(),
            ];
        }

        // Get the inserted message with user info
        $row = DB::table('direct_chats')
            ->where('direct_chats.id', $msgId)
            ->leftJoin('direct_chats as reply_msg', 'direct_chats.reply_to_id', '=', 'reply_msg.id')
            ->leftJoin('users as sender_user', 'direct_chats.sender_id', '=', 'sender_user.id')
            ->leftJoin('users as reply_sender', 'reply_msg.sender_id', '=', 'reply_sender.id')
            ->select(
                'direct_chats.id',
                'direct_chats.sender_id',
                'direct_chats.receiver_id',
                'direct_chats.message',
                'direct_chats.reply_to_id',
                'direct_chats.created_at',
                'sender_user.name as user_name',
                'reply_msg.message as reply_to_message',
                'reply_sender.name as reply_to_user_name'
            )
            ->first();

        // Format response
        $message = [
            'id'                 => $row->id,
            'senderId'           => $row->sender_id,
            'receiverId'         => $row->receiver_id,
            'user_id'            => $row->sender_id,
            'user_name'          => $row->user_name,
            'user_avatar'        => null,
            'content'            => $row->message ?: '',
            'message'            => $row->message ?: '',
            'file'               => $fileData,
            'reply_to_id'        => $row->reply_to_id,
            'reply_to_message'   => $row->reply_to_message ?? null,
            'reply_to_user_name' => $row->reply_to_user_name ?? null,
            'timestamp'          => $row->created_at,
            'created_at'         => $row->created_at,
        ];

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * DELETE /api/direct-chat-message/{id}
     * Delete a direct message (only by the original sender).
     */
    public function deleteDirectMessage(Request $request, $id)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $msg = DB::table('direct_chats')->where('id', $id)->first();

        if (!$msg) {
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
        }

        if ((int) $msg->sender_id !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        // Find and delete attached media files
        $mediaFiles = DB::table('media')
            ->where('model_type', 'App\\Models\\DirectChat')
            ->where('model_id', $id)
            ->get();

        foreach ($mediaFiles as $media) {
            $filePath = public_path($media->file_path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Delete media records
        DB::table('media')
            ->where('model_type', 'App\\Models\\DirectChat')
            ->where('model_id', $id)
            ->delete();

        // Delete message
        DB::table('direct_chats')->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }
}