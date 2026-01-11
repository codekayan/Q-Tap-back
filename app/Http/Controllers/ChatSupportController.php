<?php

namespace App\Http\Controllers;

use App\Models\ChatSupport;
use App\Models\MessageChatSupport;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\chat1;
use App\Events\notify_msg;

class ChatSupportController extends Controller
{

   /* public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'chat_id' => 'nullable|integer|exists:chat_supports,id',
            'name' => $request->filled('chat_id') ? 'nullable|string' : 'required|string',
            'email' => $request->filled('chat_id') ? 'nullable|email' : 'required|email',
        ]);

        // إذا كان هناك chat_id، البحث عن المحادثة
        if ($request->filled('chat_id')) {
            $chat = ChatSupport::where("status", "pending")->find($request->chat_id);

            if (!$chat) {
                return response()->json([
                    'error' => 'المحادثة غير موجودة أو تم إغلاقها'
                ], 404);
            }
        } else {
            // البحث عن محادثات pending لنفس البريد الإلكتروني وإغلاقها
            $existingChats = ChatSupport::where('email', $request->email)
                ->where('status', 'pending')
                ->get();

            foreach ($existingChats as $existingChat) {
                $existingChat->update(['status' => 'closed']);
            }

            // إنشاء محادثة جديدة
            $chat = ChatSupport::create([
                'name' => $request->name,
                'email' => $request->email,
                'status' => 'pending',
            ]);
        }

        // تخزين الرسالة وربطها بالمحادثة
        $message = MessageChatSupport::create([
            'message' => $request->message,
            'type' => 'user',
            'status' => 'pending',
            'chat_support_id' => $chat->id,
        ]);

        $messageData = [
            'chat_id' => $chat->id,
            'message' => $request->message,
            'type' => $message->type,
        ];

        broadcast(new chat1($messageData))->toOthers();

        $type = 'notfy';
        $content_notify = [
            'chat_id' => $chat->id, // تم التصحيح من $chat->title إلى $chat->id
            'message' => $request->message,
        ];

        event(new notify_msg($content_notify, $type));

        return response()->json([
            'chat' => $chat,
            'message' => $message,
        ], 201);
    }*/
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'chat_id' => 'nullable|integer|exists:chat_supports,id',
            'name' => $request->filled('chat_id') ? 'nullable|string' : 'required|string',
            'email' => $request->filled('chat_id') ? 'nullable|email' : 'required|email',
        ]);

        // إذا كان هناك chat_id، البحث عن المحادثة
        if ($request->filled('chat_id')) {
            $chat = ChatSupport::where("status", "pending")->find($request->chat_id);

            if (!$chat) {
                return response()->json([
                    'error' => 'المحادثة غير موجودة أو تم إغلاقها'
                ], 404);
            }

            // تحديث حالة رسائل المشرف إلى opened عند إرسال المستخدم رسالة جديدة
            MessageChatSupport::where('chat_support_id', $chat->id)
                ->where('status', 'pending')
                ->where('type', 'admin') // تحديث رسائل المشرف فقط
                ->update(['status' => 'opened']);

        } else {
            // البحث عن محادثات pending لنفس البريد الإلكتروني وإغلاقها
            $existingChats = ChatSupport::where('email', $request->email)
                ->where('status', 'pending')
                ->get();

            foreach ($existingChats as $existingChat) {
                $existingChat->update(['status' => 'closed']);
            }

            // إنشاء محادثة جديدة
            $chat = ChatSupport::create([
                'name' => $request->name,
                'email' => $request->email,
                'status' => 'pending',
            ]);
        }

        // تخزين الرسالة وربطها بالمحادثة
        $message = MessageChatSupport::create([
            'message' => $request->message,
            'type' => 'user',
            'status' => 'pending',
            'chat_support_id' => $chat->id,
        ]);

        $messageData = [
            'chat_id' => $chat->id,
            'message' => $request->message,
            'type' => $message->type,
        ];

        broadcast(new chat1($messageData))->toOthers();

        $type = 'notfy';
        $content_notify = [
            'chat_id' => $chat->id,
            'message' => $request->message,
        ];

        event(new notify_msg($content_notify, $type));

        return response()->json([
            'chat' => $chat,
            'message' => $message,
        ], 201);
    }



    public function index(Request $request)
    {
        $query = ChatSupport::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('email')) {
            $query->where('email', $request->email);
        }

        // ترتيب حسب الأحدث
        $chats = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'chats' => $chats
        ]);
    }



    public function show($id)
    {
        $chat = ChatSupport::with('MessageChatSupport')->find($id);

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        // تحديث جميع الرسائل إلى 'opened'
        MessageChatSupport::where('chat_support_id', $chat->id)
            ->where('status', 'pending')
            ->update(['status' => 'opened']);

        // إعادة تحميل العلاقة بعد التحديث
        $chat->load('MessageChatSupport');

        return response()->json([
            'chat' => $chat
        ]);
    }


    public function store_replay(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'chat_id' => 'nullable|integer|exists:chat_supports,id',

        ]);

        $chat = ChatSupport::where("status" , "pending")->find($request->chat_id);

        // تخزين الرسالة وربطها بالمحادثة
        $message = MessageChatSupport::create([
            'message' => $request->message,
            'type' => 'admin',
            'status' => 'pending',
            'chat_support_id' => $chat->id,
        ]);


        $message =[
            'chat_id' =>$chat->id,
            'message' => $request->message,
            'type' => $message->type,

        ];

        MessageChatSupport::where('chat_support_id', $chat->id)
            ->where('status', 'pending')
            ->where('type', 'user')
            ->update(['status' => 'opened']);
        $chat->load('MessageChatSupport');

        broadcast(new chat1($message))->toOthers();
        return response()->json([
            'chat' => $chat,
            'message' => $message,
        ], 201);
    }


    public function get_last_user(Request $request)
    {
        // ✅ التحقق من صحة البريد الإلكتروني
        $request->validate([
            'email' => 'required|email'
        ]);

        // 🔍 جلب آخر محادثة ذات الحالة pending لهذا البريد الإلكتروني
        $chat = ChatSupport::where('email', $request->email)
            ->where('status', 'pending') // تصفية حسب الحالة
            ->orderBy('created_at', 'desc') // ترتيب من الأحدث
            ->with(['MessageChatSupport' => function ($query) {
                $query->orderBy('created_at', 'desc'); // ترتيب الرسائل من الأحدث
            }])
            ->first(); // جلب واحدة فقط

        // ⚠️ التحقق من وجود المحادثة
        if (!$chat) {
            return response()->json(['error' => 'No pending chat found for this email'], 404);
        }

        // ✅ إرسال البيانات
        return response()->json([
            'chat' => $chat
        ]);
    }



    public function closeChatByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:chat_supports,email',
            'chat_id' => 'required|integer|exists:chat_supports,id'
        ]);

        // التحقق من أن المحادثة المحددة تنتمي لنفس البريد الإلكتروني
        $specificChat = ChatSupport::where('id', $request->chat_id)
            ->where('email', $request->email)
            ->first();

        if (!$specificChat) {
            return response()->json([
                'message' => 'Chat not found or does not belong to this email',
                'chat_id' => $request->chat_id,
                'email' => $request->email
            ], 404);
        }

        // البحث عن جميع المحادثات النشطة لهذا البريد الإلكتروني
        $chats = ChatSupport::where('email', $request->email)
            ->where('status', 'pending')
            ->get();

        if ($chats->isEmpty()) {
            return response()->json([
                'message' => 'No active chats found for this email',
                'closed_count' => 0
            ], 404);
        }

        $closedCount = 0;
        $closedChats = [];

        foreach ($chats as $chat) {
            // تحديث حالة المحادثة إلى مغلقة
            $chat->update(['status' => 'closed']);
            $closedChats[] = $chat->id;
            $closedCount++;
        }

        return response()->json([
            'message' => 'All chats closed successfully',
            'closed_count' => $closedCount,
            'closed_chats' => $closedChats,
            'email' => $request->email,
            'original_chat_id' => $request->chat_id
        ], 200);
    }


}
