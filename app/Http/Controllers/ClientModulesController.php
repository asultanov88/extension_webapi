<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Modules;
use App\Http\Custom\CustomValidators;

class ClientModulesController extends Controller
{
    /**
     * Deletes module not referenced by bugs.
     */
    public function deleteModule(Request $request){

        $request->validate([
            'moduleId'=>'required|integer|exists:modules,moduleId',
        ]);

        // Validates if the requested project ID belongs to the user.
        if(!CustomValidators::validateModuleId($request)){
            return response()->
            json(['error'=>'invalid module id'], 500); 
        }

        try {
                
            $module = Modules::with('bugs')
            ->where('moduleId','=',$request['moduleId'])  
            ->join('projects','projects.id','=','modules.projectId')
            ->where('projects.clientId','=',$request['clientId'])
            ->first();
            
            if(!is_null($module)){

                if(count($module['bugs']) > 0){

                    return response()->
                    json(['status' => 'unable to delete'], 500);  
    
                }else{
    
                    $module->delete();
                    return response()->
                    json(['status' => 'success'], 200);
    
                }   

            }                   

        } catch (Exception $e) {
            return response()->
            json($e, 500);        
        } 
    }

    /**
     * Updates module.
     */
    public function patchModule(Request $request){
        $request->validate([
            'moduleId'=>'required|integer|exists:modules,moduleId',
            'name'=>'required|max:50',
            'description'=>'required|max:255',
        ]);
        
        // Validates if the requested project ID belongs to the user.
        if(!CustomValidators::validateModuleId($request)){
            return response()->
            json(['error'=>'invalid module id'], 500); 
        }

        try {

            $matchingModules = Modules::whereRaw('LOWER(name) = LOWER(?)', ["{$request['name']}"])
                                ->join('projects','projects.id','=','modules.projectId')
                                ->where('projects.clientId','=',$request['clientId'])
                                ->first();

            $requestModule = Modules::where('moduleId','=',$request['moduleId'])
                                    ->join('projects','projects.id','=','modules.projectId')
                                    ->where('projects.clientId','=',$request['clientId'])
                                    ->first();

            if(!is_null($matchingModules)){

                $duplicateExists = $matchingModules['projectId'] == $requestModule['projectId']
                                ? true
                                : false;
                                
                if($duplicateExists){
                    $errResponse = [
                        'error' => 'module already exists',
                        'result' => $matchingModules,
                    ];
        
                    return response()->
                    json($errResponse, 500);
                }
                
            }


            $module = Modules::where('moduleId','=',$request['moduleId'])
                        ->join('projects','projects.id','=','modules.projectId')
                        ->where('projects.clientId','=',$request['clientId'])
                        ->first();

            if(!is_null($module)){

                $module->update([
                    'name'=>$request['name'],
                    'description'=>$request['description'],
                ]);

                return response()->
                json(['result' => $module], 200);

            }              

        } catch (Exception $e) {
            return response()->
            json($e, 500);        
        }
    }

    /**
     * Gets module list.
     */
    public function getModule(Request $request){
        $request->validate([
            'projectId'=>'required|integer|exists:projects,id',
            'query'=> 'required|max:50',
        ]);

        // Validates if the requested project ID belongs to the user.
        if(!CustomValidators::validateProjectId($request)){
            return response()->
            json(['error'=>'invalid project id'], 500); 
        }

        try {

            $modules = Modules::with('bugs')
                ->where('name', 'like', '%'.$request['query'].'%')
                ->join('projects','modules.projectId','=','projects.id')                
                ->where('projects.id','=',$request['projectId'])
                ->where('projects.clientId','=',$request['clientId'])
                ->get()->toArray();
           
            // Used to capture only moduleId, name and descriptin from the query result.
            $modulesResult = [];
            
            foreach($modules as $module){

                $result = [
                    'moduleId' => $module['moduleId'],
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'allowDelete' => count($module['bugs']) > 0 ? 0 : 1,
                ];

                array_push($modulesResult, $result);
            }

            return response()->
            json(['result' => $modulesResult], 200);

        } catch (Exception $e) {
            return response()->
            json($e, 500);        
        }
    }

    /**
     * Creates new module.
     */
    public function postModule(Request $request){
        $request->validate([
            'projectId'=>'required|integer|exists:projects,id',
            'name'=>'required|max:50',
            'description'=>'required|max:255',
        ]);

        // Validates if the requested project ID belongs to the user.
        if(!CustomValidators::validateProjectId($request)){
            return response()->
            json(['error'=>'invalid project id'], 500); 
        }

        try {
            
            $existingModule = Modules::where('projectId','=',$request['projectId'])
                ->whereRaw('LOWER(name) = LOWER(?)', ["{$request['name']}"])
                ->first();

            // Return error if module name already exists for given projectId.
            if(!is_null($existingModule)){

                $errResponse = [
                    'error' => 'Module already exists for this project.',
                    'result' => $existingModule,
                ];
    
                return response()->
                json($errResponse, 500);

            }

            $modules = new Modules();
            $modules['projectId'] = $request['projectId'];
            $modules['name'] = $request['name'];
            $modules['description'] = $request['description'];
            $modules->save();

            return response()->
            json(['result' => $modules], 200);

        } catch (Exception $e) {
            return response()->
            json($e, 500);        
        }
    }
}
