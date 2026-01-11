<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;


use App\Models\meals;

class orders extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "phone",
        "comments",
        "city",
        "address",
        "latitude",
        "longitude",
        "table_id",
        "type",
        "status",
        "discount_code",
        "tax",
        "reference_number",
        "total_price",
        "meal_id",
        "variants",
        "extras",
        "size_id",
        "quantity",
        "payment_way",
            "subtotal",

        "size",
        "brunch_id"
    ];


    public function orders_processing()
    {
        return $this->hasMany(orders_processing::class, 'order_id');
    }


    public function meal()
    {
        return meals::whereIn('id', json_decode($this->meal_id))->get();
    }


    public function varian()
    {
        return meals_variants::whereIn('id', json_decode($this->variants))->get();
    }


    protected $appends = ['meals', 'variants', 'extras'];



    public function getMealsAttribute()
    {
        $ids = json_decode($this->meal_id, true);

        if (is_array($ids) && count($ids) > 0) {
            return meals::whereIn('id', $ids)->get();
        }

        return [];
    }

    public function getVariantsAttribute()
    {
        $raw = $this->attributes['variants'] ?? '[]';

        $structuredIds = is_array($raw) ? $raw : json_decode($raw, true);

        // اجمع كل المعرفات المفردة من جميع المصفوفات
        $flatIds = is_array($structuredIds) ? Arr::flatten($structuredIds) : [];

        $detailed = count($flatIds) > 0
            ? meals_variants::whereIn('id', $flatIds)->get()->keyBy('id')
            : collect();

        // أعِد البنية الأصلية مع التفاصيل داخلها
        $withDetails = collect($structuredIds)->map(function ($group) use ($detailed) {
            return collect($group)->map(function ($id) use ($detailed) {
                return $detailed[$id] ?? null;
            })->filter(); // نحذف القيم null
        });

        return [
            'raw' => $structuredIds,
            'detailed' => $withDetails,
        ];
    }



    public function getExtrasAttribute()
    {
        $raw = $this->attributes['extras'] ?? '[]';

        $structuredIds = is_array($raw) ? $raw : json_decode($raw, true);

        $flatIds = is_array($structuredIds) ? Arr::flatten($structuredIds) : [];

        $detailed = count($flatIds) > 0
            ? meals_extra::whereIn('id', $flatIds)->get()->keyBy('id')
            : collect();

        $withDetails = collect($structuredIds)->map(function ($group) use ($detailed) {
            return collect($group)->map(function ($id) use ($detailed) {
                return $detailed[$id] ?? null;
            })->filter();
        });

        return [
            'raw' => $structuredIds,
            'detailed' => $withDetails,
        ];
    }





    // public function getVariantsAttribute()
    // {
    //     $variants = $this->attributes['variants'] ?? '[]'; // خذ القيمة الأصلية مش المعدلة

    //     if (is_array($variants)) {
    //         $ids = $variants; // لو مصفوفة جاهزة، استخدمها كما هي
    //     } else {
    //         $ids = json_decode($variants, true); // لو نص، فك التشفير
    //     }

    //     if (is_array($ids) && count($ids) > 0) {
    //         $ids = Arr::flatten($ids); // تسطيح المصفوفة هنا قبل whereIn
    //         return meals_variants::whereIn('id', $ids)->get();
    //     }

    //     return [];
    // }

    // public function getExtrasAttribute()
    // {
    //     $extras = $this->attributes['extras'] ?? '[]'; // خذ القيمة الأصلية مش المعدلة

    //     if (is_array($extras)) {
    //         $ids = $extras; // لو مصفوفة جاهزة، استخدمها كما هي
    //     } else {
    //         $ids = json_decode($extras, true); // لو نص، فك التشفير
    //     }

    //     if (is_array($ids) && count($ids) > 0) {
    //         $ids = Arr::flatten($ids); // تسطيح المصفوفة هنا قبل whereIn
    //         return meals_extra::whereIn('id', $ids)->get(); // هنا ضع اسم الموديل المناسب لـ extras
    //     }

    //     return [];
    // }








    public function orders_process()
    {

        return $this->hasMany(orders_processing::class, 'id', 'order_id');
    }
}
