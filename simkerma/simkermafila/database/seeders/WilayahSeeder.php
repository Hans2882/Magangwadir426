<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\Http;
use App\Models\MasterProvinsi;
use App\Models\MasterKota;

class WilayahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Fetching provinces...');
        $provincesResponse = Http::get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');

        if ($provincesResponse->successful()) {
            $provinces = $provincesResponse->json();

            foreach ($provinces as $province) {
                MasterProvinsi::firstOrCreate(
                    ['id' => $province['id']],
                    ['nama_provinsi' => $province['name']]
                );

                $this->command->info("Fetching cities for {$province['name']}...");
                $citiesResponse = Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/regencies/{$province['id']}.json");

                if ($citiesResponse->successful()) {
                    $cities = $citiesResponse->json();
                    foreach ($cities as $city) {
                        MasterKota::firstOrCreate(
                            ['id' => $city['id']],
                            [
                                'provinsi_id' => $province['id'],
                                'nama_kota' => $city['name']
                            ]
                        );
                    }
                }
            }
            $this->command->info('Wilayah seeding completed successfully!');
        } else {
            $this->command->error('Failed to fetch data from API.');
        }
    }
}
