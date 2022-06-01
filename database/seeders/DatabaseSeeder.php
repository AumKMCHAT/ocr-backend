<?php

namespace Database\Seeders;

use App\Models\MasterIso;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $response = Http::get('https://datahub.io/core/iso-container-codes/r/iso-container-codes.json');
        $master_iso = json_decode($response);
        foreach (json_decode($response) as $master_iso) {
            if (!is_numeric($master_iso->code)) {
                $iso = new MasterIso();
                $iso->code = $master_iso->code;
                $iso->description = $master_iso->description;
                $iso->group = $master_iso->group;
                $iso->height = $master_iso->height;
                $iso->length = $master_iso->length;
                $iso->save();
            }

            // print_r("iso code: " . $master_iso->code . "\n"); //master_iso
        } //loop put api to database
    }
}
