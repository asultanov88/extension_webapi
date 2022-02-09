<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LkProjectStatus;
use Carbon\Carbon;

class LkProjectStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedLkProjectStatus();
    }

    private function seedLkProjectStatus(){

        LkProjectStatus::truncate();

        $projectStatus = [
            [
                'id'=>1,
                'description'=>'active',
                'created_at'=>Carbon::now(),
                'updated_at'=>Carbon::now(),
            ],
            [
                'id'=>2,
                'description'=>'inactive',
                'created_at'=>Carbon::now(),
                'updated_at'=>Carbon::now(),
            ]
        ];

        LkProjectStatus::insert($projectStatus);

    }
}
