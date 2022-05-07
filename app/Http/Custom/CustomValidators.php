<?php

namespace App\Http\Custom;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Projects;

class CustomValidators
{  

    /** Validates project ID */
    public function validateProjectId($request){
        $project = Projects::where('id','=',$request['id'])
                            ->where('clientId','=',$request['clientId'])
                            ->first();

        return $project ? true : false;
    }

}