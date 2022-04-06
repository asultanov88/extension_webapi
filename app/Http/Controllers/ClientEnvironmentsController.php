<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Environment;
use App\Models\ModuleBug;

class ClientEnvironmentsController extends Controller
{
    /**
     * Updates environment by ID.
     */
    public function patchEnvironment(Request $request){
        $request->validate([
            'environmentId'=>'required|integer|exists:environments,environmentId',
            'name'=>'required|string|max:50',
        ]);

        try {
            
            $environment = Environment::where('environmentId','=',$request['environmentId'])->first();

            $environment->update([
                'name'=>$request['name'],
            ]);
    
            return response()->
            json(['result' => $environment], 200); 

        } catch (Exception $e) {
            return response()->
            json($e, 500);        
        }
    }

    /**
     * Deletes environment by environment ID.
     */
    public function deleteEnvironment(Request $request){
        $request->validate([
            'environmentId'=>'required|integer|exists:environments,environmentId',
        ]);

        try {
            
            $bugs = ModuleBug::with('bugEnvironment')
                         ->join('modules','modules.moduleId','=','module_bugs.moduleId')
                         ->join('projects','projects.id','=','modules.projectId')
                         ->where('projects.clientId','=',$request['clientId'])
                         ->get();

            $environmentUsed = false;

            foreach($bugs as $bug){
                $bugEnvironmentId = $bug->bugEnvironment->environment->environmentId;
                if($bugEnvironmentId == $request['environmentId']){
                    $environmentUsed = true;
                }
            }
        
            if($environmentUsed){

                return response()->
                json(['status' => 'unable to delete'], 500);

            }else{

                $environment = Environment::where('environmentId','=',$request['environmentId'])
                                            ->first();                                     
                $environment->delete();
                
                return response()->
                json(['status' => 'success'], 200); 
            
            }

        } catch (Exception $e) {
            return response()->
            json($e, 500);        
        }
    }

    /**
     * Gets a list of environments by client ID.
     */
    public function getEnvironment(Request $request){
        $request->validate([
            'query'=>'required|string|min:2'
        ]);

        try {
            
            $environments = Environment::where('clientId','=',$request['clientId'])
                                       ->where('name', 'like', '%'.$request['query'].'%')
                                       ->get()->toArray();

            // Used to identify if environment is referenced in any bug.
            $bugs = ModuleBug::with('bugEnvironment')
                                       ->join('modules','modules.moduleId','=','module_bugs.moduleId')
                                       ->join('projects','projects.id','=','modules.projectId')
                                       ->where('projects.clientId','=',$request['clientId'])
                                       ->get()->toArray();

            // array of referenced environment IDs.
            $referencedEnvIds = array_map(function ($bugs){
                return $bugs['bug_environment']['environmentId'];                
            }, $bugs);

            $queryResult = [];

            foreach($environments as $env){
                $result = [
                    'environmentId' => $env['environmentId'],
                    'name' => $env['name'],
                    'allowDelete' => in_array($env['environmentId'], $referencedEnvIds) ? 0 : 1,
                ];

                array_push($queryResult, $result);
            }
            
            return response()->
            json(['result' => $queryResult], 200);

        } catch (Exception $e) {
            return response()->
            json($e, 500);        
        }
    }

    /**
     * Creates a new environment. 
     */
    public function postEnvironment(Request $request){
        $request->validate([
            'name'=>'required|string|max:50',
        ]);

        try {
            
            $matchingEnvironment = Environment::where('clientId','=',$request['clientId'])
                                          ->whereRaw('LOWER(name) = LOWER(?)', ["{$request['name']}"])
                                          ->first();


            if(is_null($matchingEnvironment)){
                $environment = new Environment();
                $environment['name'] = $request['name'];
                $environment['clientId'] = $request['clientId'];
                $environment->save();

                return response()->
                json(['result' => $environment], 200);

            }else{
                
                return response()->
                json(['error' => 'environment already exists'], 500);

            }            
            
        } catch (Exception $e) {
            return response()->
            json($e, 500);        
        }
    }
}
