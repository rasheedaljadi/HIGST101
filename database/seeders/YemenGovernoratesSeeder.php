<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class YemenGovernoratesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $country = DB::table('countries')->where('code', 'YE')->first();

        if (! $country) {
            $countryId = DB::table('countries')->insertGetId([
                'code' => 'YE',
                'name' => 'اليمن',
            ]);
        } else {
            $countryId = $country->id;
        }

        $governorates = [
            ['code' => 'SAN', 'name' => 'أمانة العاصمة'],
            ['code' => 'SN',  'name' => 'محافظة صنعاء'],
            ['code' => 'AD',  'name' => 'عدن'],
            ['code' => 'TZ',  'name' => 'تعز'],
            ['code' => 'HU',  'name' => 'الحديدة'],
            ['code' => 'IB',  'name' => 'إب'],
            ['code' => 'AB',  'name' => 'أبين'],
            ['code' => 'BA',  'name' => 'البيضاء'],
            ['code' => 'SH',  'name' => 'شبوة'],
            ['code' => 'HD',  'name' => 'حضرموت'],
            ['code' => 'MR',  'name' => 'المهرة'],
            ['code' => 'LA',  'name' => 'لحج'],
            ['code' => 'MA',  'name' => 'مأرب'],
            ['code' => 'JA',  'name' => 'الجوف'],
            ['code' => 'HJ',  'name' => 'حجة'],
            ['code' => 'SD',  'name' => 'صعدة'],
            ['code' => 'MW',  'name' => 'المحويت'],
            ['code' => 'DH',  'name' => 'ذمار'],
            ['code' => 'AM',  'name' => 'عمران'],
            ['code' => 'DL',  'name' => 'الضالع'],
            ['code' => 'RY',  'name' => 'ريمة'],
            ['code' => 'SU',  'name' => 'أرخبيل سقطرى'],
        ];

        foreach ($governorates as $gov) {
            $existing = DB::table('country_states')
                ->where('country_code', 'YE')
                ->where('code', $gov['code'])
                ->first();

            if ($existing) {
                $stateId = $existing->id;
                DB::table('country_states')->where('id', $stateId)->update([
                    'default_name' => $gov['name'],
                ]);
            } else {
                $stateId = DB::table('country_states')->insertGetId([
                    'country_id' => $countryId,
                    'country_code' => 'YE',
                    'code' => $gov['code'],
                    'default_name' => $gov['name'],
                ]);
            }

            foreach (['ar', 'en'] as $locale) {
                $transExists = DB::table('country_state_translations')
                    ->where('country_state_id', $stateId)
                    ->where('locale', $locale)
                    ->first();

                if ($transExists) {
                    DB::table('country_state_translations')
                        ->where('id', $transExists->id)
                        ->update(['default_name' => $gov['name']]);
                } else {
                    DB::table('country_state_translations')->insert([
                        'country_state_id' => $stateId,
                        'locale' => $locale,
                        'default_name' => $gov['name'],
                    ]);
                }
            }
        }
    }
}
