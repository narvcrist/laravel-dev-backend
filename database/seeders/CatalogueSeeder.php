<?php

namespace Database\Seeders;

use App\Models\Attendance\Attendance;
use App\Models\Attendance\Workday;
use App\Models\Authentication\Role;
use App\Models\Authentication\Route;
use App\Models\Authentication\System;
use App\Models\Authentication\User;
use App\Models\Ignug\Catalogue;
use App\Models\Ignug\Institution;
use Illuminate\Database\Seeder;

class CatalogueSeeder extends Seeder
{
    public function run()
    {
        $data = file_get_contents(storage_path() . "/catalogues.json");
        $catalogues = json_decode($data, true);

        Catalogue::factory()->create([
            'code' => $catalogues['workday']['type']['work'],
            'name' => 'JORNADA',
            'type' => $catalogues['workday']['type']['type'],
        ]);

    }
}
