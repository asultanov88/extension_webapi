<?php

namespace App\Http\Custom;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Projects;
use App\Models\Modules;
use App\Models\ModuleBug;

class CustomValidators
{  

    /** Validates if project ID belongs to the user. */
    public function validateProjectId($request){
        $project = Projects::where('id','=',$request['id'] ? $request['id'] : $request['projectId'])
                            ->where('clientId','=',$request['clientId'])
                            ->first();

        return $project ? true : false;
    }

    /** Validates if module ID belongs to the user. */
    public function validateModuleId($request){
        $module = Modules::where('moduleId','=',$request['moduleId'])
                          ->join('projects','projects.id','=','modules.projectId')
                          ->where('projects.clientId','=',$request['clientId'])
                          ->first();

        return $module ? true : false;
    }

    /** Validates if bug ID belongs to the user. */
    public function validateBugId($request){
        $bug = ModuleBug::where('bugId','=',$request['bugId'])
                         ->join('modules','modules.moduleId','=','module_bugs.moduleId')
                         ->join('projects','projects.id','=','modules.projectId')  
                         ->where('projects.clientId','=',$request['clientId'])
                         ->first();

        return $bug ? true : false;
    }
}