<?php

namespace App\Http\Controllers;

use App\Models\faq_qtap;
use Illuminate\Http\Request;

class FaqQtapController extends Controller
{
    // عرض جميع الأسئلة
    public function index()
    {
        $faqs = faq_qtap::all();
        return response()->json($faqs);
    }

    // تخزين سؤال جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        

        $faq = faq_qtap::create($validated);
        return response()->json($faq, 201);
    }

    // تعديل سؤال موجود
    public function update(Request $request, faq_qtap $faq_qtap)
    {
        $validated = $request->validate([
            'question' => 'sometimes|required|string|max:255',
            'answer' => 'sometimes|required|string',
        ]);

        $faq_qtap->update($validated);
        return response()->json($faq_qtap);
    }

    // حذف سؤال
    public function destroy(faq_qtap $faq_qtap)
    {
        $faq_qtap->delete();
        return response()->json(['message' => 'تم الحذف بنجاح']);
    }
}


