<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EgyptLocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
 public function run()
    {
        $governorates = [
            [
                'name_ar' => 'القاهرة',
                'name_en' => 'Cairo',
                'code' => 'EG-C',
                'cities' => [
                    ['name_ar' => 'المعادي', 'name_en' => 'Maadi'],
                    ['name_ar' => 'المقطم', 'name_en' => 'Mokattam'],
                    ['name_ar' => 'مدينة نصر', 'name_en' => 'Nasr City'],
                    ['name_ar' => 'الزمالك', 'name_en' => 'Zamalek'],
                    ['name_ar' => 'الزيتون', 'name_en' => 'Zaytoun'],
                    ['name_ar' => 'حدائق القبة', 'name_en' => 'Hadayek El Qobbah'],
                    ['name_ar' => 'شبرا', 'name_en' => 'Shubra'],
                    ['name_ar' => 'مصر الجديدة', 'name_en' => 'Heliopolis'],
                ]
            ],
            [
                'name_ar' => 'الجيزة',
                'name_en' => 'Giza',
                'code' => 'EG-GZ',
                'cities' => [
                    ['name_ar' => 'الدقي', 'name_en' => 'Dokki'],
                    ['name_ar' => 'المهندسين', 'name_en' => 'Mohandessin'],
                    ['name_ar' => 'الهرم', 'name_en' => 'Haram'],
                    ['name_ar' => 'العجوزة', 'name_en' => 'Agouza'],
                    ['name_ar' => 'الوراق', 'name_en' => 'Warraq'],
                    ['name_ar' => 'البدرشين', 'name_en' => 'Badrasheen'],
                    ['name_ar' => 'العمرانية', 'name_en' => 'Omraniya'],
                ]
            ],
            [
                'name_ar' => 'الإسكندرية',
                'name_en' => 'Alexandria',
                'code' => 'EG-ALX',
                'cities' => [
                    ['name_ar' => 'المنتزه', 'name_en' => 'Montaza'],
                    ['name_ar' => 'سموحة', 'name_en' => 'Smouha'],
                    ['name_ar' => 'العصافرة', 'name_en' => 'Asafra'],
                    ['name_ar' => 'المنشية', 'name_en' => 'Mansheya'],
                    ['name_ar' => 'اللبان', 'name_en' => 'Labban'],
                    ['name_ar' => 'السيوف', 'name_en' => 'Siouf'],
                ]
            ],
            [
                'name_ar' => 'الدقهلية',
                'name_en' => 'Dakahlia',
                'code' => 'EG-DK',
                'cities' => [
                    ['name_ar' => 'المنصورة', 'name_en' => 'Mansoura'],
                    ['name_ar' => 'طلخا', 'name_en' => 'Talkha'],
                    ['name_ar' => 'ميت غمر', 'name_en' => 'Mit Ghamr'],
                    ['name_ar' => 'بلقاس', 'name_en' => 'Belqas'],
                    ['name_ar' => 'أجا', 'name_en' => 'Aga'],
                ]
            ],
            [
                'name_ar' => 'البحر الأحمر',
                'name_en' => 'Red Sea',
                'code' => 'EG-BA',
                'cities' => [
                    ['name_ar' => 'الغردقة', 'name_en' => 'Hurghada'],
                    ['name_ar' => 'رأس غارب', 'name_en' => 'Ras Ghareb'],
                    ['name_ar' => 'سفاجا', 'name_en' => 'Safaga'],
                    ['name_ar' => 'القصير', 'name_en' => 'El Qoseir'],
                ]
            ],
            [
                'name_ar' => 'البحيرة',
                'name_en' => 'Beheira',
                'code' => 'EG-BH',
                'cities' => [
                    ['name_ar' => 'دمنهور', 'name_en' => 'Damanhur'],
                    ['name_ar' => 'كفر الدوار', 'name_en' => 'Kafr El Dawwar'],
                    ['name_ar' => 'رشيد', 'name_en' => 'Rashid'],
                    ['name_ar' => 'إدكو', 'name_en' => 'Edku'],
                ]
            ],
            [
                'name_ar' => 'الفيوم',
                'name_en' => 'Faiyum',
                'code' => 'EG-FYM',
                'cities' => [
                    ['name_ar' => 'الفيوم', 'name_en' => 'Faiyum'],
                    ['name_ar' => 'طامية', 'name_en' => 'Tamiya'],
                    ['name_ar' => 'سنورس', 'name_en' => 'Sinnuris'],
                ]
            ],
            [
                'name_ar' => 'الغربية',
                'name_en' => 'Gharbia',
                'code' => 'EG-GH',
                'cities' => [
                    ['name_ar' => 'طنطا', 'name_en' => 'Tanta'],
                    ['name_ar' => 'المحلة الكبرى', 'name_en' => 'El Mahalla El Kubra'],
                    ['name_ar' => 'زفتى', 'name_en' => 'Zefta'],
                ]
            ],
            [
                'name_ar' => 'الإسماعيلية',
                'name_en' => 'Ismailia',
                'code' => 'EG-IS',
                'cities' => [
                    ['name_ar' => 'الإسماعيلية', 'name_en' => 'Ismailia'],
                    ['name_ar' => 'فايد', 'name_en' => 'Fayed'],
                    ['name_ar' => 'القنطرة شرق', 'name_en' => 'Qantara Sharq'],
                ]
            ],
            [
                'name_ar' => 'المنوفية',
                'name_en' => 'Monufia',
                'code' => 'EG-MNF',
                'cities' => [
                    ['name_ar' => 'شبين الكوم', 'name_en' => 'Shibin El Kom'],
                    ['name_ar' => 'السادات', 'name_en' => 'Sadat City'],
                    ['name_ar' => 'منوف', 'name_en' => 'Menouf'],
                ]
            ],
            [
                'name_ar' => 'المنيا',
                'name_en' => 'Minya',
                'code' => 'EG-MN',
                'cities' => [
                    ['name_ar' => 'المنيا', 'name_en' => 'Minya'],
                    ['name_ar' => 'ملوي', 'name_en' => 'Mallawi'],
                    ['name_ar' => 'دير مواس', 'name_en' => 'Deir Mawas'],
                ]
            ],
            [
                'name_ar' => 'القليوبية',
                'name_en' => 'Qalyubia',
                'code' => 'EG-KB',
                'cities' => [
                    ['name_ar' => 'بنها', 'name_en' => 'Banha'],
                    ['name_ar' => 'قليوب', 'name_en' => 'Qalyub'],
                    ['name_ar' => 'شبرا الخيمة', 'name_en' => 'Shubra El Kheima'],
                ]
            ],
            [
                'name_ar' => 'الوادي الجديد',
                'name_en' => 'New Valley',
                'code' => 'EG-WAD',
                'cities' => [
                    ['name_ar' => 'الخارجة', 'name_en' => 'Kharga'],
                    ['name_ar' => 'باريس', 'name_en' => 'Paris'],
                    ['name_ar' => 'الداخلة', 'name_en' => 'Dakhla'],
                ]
            ],
            [
                'name_ar' => 'السويس',
                'name_en' => 'Suez',
                'code' => 'EG-SUZ',
                'cities' => [
                    ['name_ar' => 'السويس', 'name_en' => 'Suez'],
                    ['name_ar' => 'الأربعين', 'name_en' => 'Arbaeen'],
                    ['name_ar' => 'عتاقة', 'name_en' => 'Ataka'],
                ]
            ],
            [
                'name_ar' => 'أسوان',
                'name_en' => 'Aswan',
                'code' => 'EG-ASN',
                'cities' => [
                    ['name_ar' => 'أسوان', 'name_en' => 'Aswan'],
                    ['name_ar' => 'كوم أمبو', 'name_en' => 'Kom Ombo'],
                    ['name_ar' => 'دراو', 'name_en' => 'Daraw'],
                ]
            ],
            [
                'name_ar' => 'أسيوط',
                'name_en' => 'Asyut',
                'code' => 'EG-AST',
                'cities' => [
                    ['name_ar' => 'أسيوط', 'name_en' => 'Asyut'],
                    ['name_ar' => 'ديروط', 'name_en' => 'Dayrout'],
                    ['name_ar' => 'صدفا', 'name_en' => 'Sidfa'],
                ]
            ],
            [
                'name_ar' => 'بني سويف',
                'name_en' => 'Beni Suef',
                'code' => 'EG-BNS',
                'cities' => [
                    ['name_ar' => 'بني سويف', 'name_en' => 'Beni Suef'],
                    ['name_ar' => 'ببا', 'name_en' => 'Biba'],
                    ['name_ar' => 'الواسطي', 'name_en' => 'El Wasta'],
                ]
            ],
            [
                'name_ar' => 'بورسعيد',
                'name_en' => 'Port Said',
                'code' => 'EG-PTS',
                'cities' => [
                    ['name_ar' => 'بورسعيد', 'name_en' => 'Port Said'],
                    ['name_ar' => 'حي الشرق', 'name_en' => 'Hayy El Sharq'],
                    ['name_ar' => 'حي الضواحي', 'name_en' => 'Hayy El Dawahi'],
                ]
            ],
            [
                'name_ar' => 'دمياط',
                'name_en' => 'Damietta',
                'code' => 'EG-DT',
                'cities' => [
                    ['name_ar' => 'دمياط', 'name_en' => 'Damietta'],
                    ['name_ar' => 'فارسكور', 'name_en' => 'Faraskour'],
                    ['name_ar' => 'الزرقا', 'name_en' => 'El Zarqa'],
                ]
            ],
            [
                'name_ar' => 'جنوب سيناء',
                'name_en' => 'South Sinai',
                'code' => 'EG-JS',
                'cities' => [
                    ['name_ar' => 'الطور', 'name_en' => 'El Tor'],
                    ['name_ar' => 'شرم الشيخ', 'name_en' => 'Sharm El Sheikh'],
                    ['name_ar' => 'دهب', 'name_en' => 'Dahab'],
                ]
            ],
            [
                'name_ar' => 'كفر الشيخ',
                'name_en' => 'Kafr El Sheikh',
                'code' => 'EG-KFS',
                'cities' => [
                    ['name_ar' => 'كفر الشيخ', 'name_en' => 'Kafr El Sheikh'],
                    ['name_ar' => 'دسوق', 'name_en' => 'Desouk'],
                    ['name_ar' => 'فوه', 'name_en' => 'Fuwa'],
                ]
            ],
            [
                'name_ar' => 'مطروح',
                'name_en' => 'Matrouh',
                'code' => 'EG-MT',
                'cities' => [
                    ['name_ar' => 'مرسى مطروح', 'name_en' => 'Marsa Matrouh'],
                    ['name_ar' => 'الحمام', 'name_en' => 'El Hamam'],
                    ['name_ar' => 'النجيلة', 'name_en' => 'El Negaila'],
                ]
            ],
            [
                'name_ar' => 'الأقصر',
                'name_en' => 'Luxor',
                'code' => 'EG-LX',
                'cities' => [
                    ['name_ar' => 'الأقصر', 'name_en' => 'Luxor'],
                    ['name_ar' => 'القرنة', 'name_en' => 'Al Qarna'],
                    ['name_ar' => 'إسنا', 'name_en' => 'Esna'],
                ]
            ],
            [
                'name_ar' => 'قنا',
                'name_en' => 'Qena',
                'code' => 'EG-KN',
                'cities' => [
                    ['name_ar' => 'قنا', 'name_en' => 'Qena'],
                    ['name_ar' => 'دشنا', 'name_en' => 'Deshna'],
                    ['name_ar' => 'نقادة', 'name_en' => 'Naqada'],
                ]
            ],
            [
                'name_ar' => 'شمال سيناء',
                'name_en' => 'North Sinai',
                'code' => 'EG-SIN',
                'cities' => [
                    ['name_ar' => 'العريش', 'name_en' => 'El Arish'],
                    ['name_ar' => 'الشيخ زويد', 'name_en' => 'Sheikh Zuweid'],
                    ['name_ar' => 'رفح', 'name_en' => 'Rafah'],
                ]
            ],
            [
                'name_ar' => 'سوهاج',
                'name_en' => 'Sohag',
                'code' => 'EG-SHG',
                'cities' => [
                    ['name_ar' => 'سوهاج', 'name_en' => 'Sohag'],
                    ['name_ar' => 'أخميم', 'name_en' => 'Akhmim'],
                    ['name_ar' => 'البلينا', 'name_en' => 'El Balyana'],
                ]
            ]
        ];

        foreach ($governorates as $govData) {
            // Use firstOrCreate to avoid duplicates
            $governorate = Governorate::firstOrCreate(
                ['code' => $govData['code']],  // Search by unique code
                [
                    'name_ar' => $govData['name_ar'],
                    'name_en' => $govData['name_en'],
                ]
            );

            foreach ($govData['cities'] as $cityData) {
                // Use firstOrCreate to avoid duplicate cities
                City::firstOrCreate(
                    [
                        'name_en' => $cityData['name_en'],
                        'governorate_id' => $governorate->id
                    ],
                    ['name_ar' => $cityData['name_ar']]
                );
            }
        }
    }
}
