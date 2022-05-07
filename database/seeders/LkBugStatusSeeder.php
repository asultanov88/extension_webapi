<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LkBugStatus;
use Carbon\Carbon;

class LkBugStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedLkBugStatus();
    }

    private function seedLkBugStatus(){

        LkBugStatus::truncate();

        $bugStatus = [
            [
                'lkBugStatusId'=>1,
                'description'=>'active',
                'created_at'=>Carbon::now(),
                'updated_at'=>Carbon::now(),
            ],
            [
                'lkBugStatusId'=>2,
                'description'=>'in-progress',
                'created_at'=>Carbon::now(),
                'updated_at'=>Carbon::now(),
            ],
            [
                'lkBugStatusId'=>3,
                'description'=>'completed',
                'created_at'=>Carbon::now(),
                'updated_at'=>Carbon::now(),
            ],
            [
                'lkBugStatusId'=>4,
                'description'=>'cancelled',
                'created_at'=>Carbon::now(),
                'updated_at'=>Carbon::now(),
            ]
        ];

        LkBugStatus::insert($bugStatus);

    }
}
