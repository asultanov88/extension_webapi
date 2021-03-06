<?php

namespace App\Http\Controllers;

use App\Models\LkProjectStatus;
use Illuminate\Http\Request;
use App\Models\Projects;
use App\Models\Modules;
use App\Http\Custom\CustomValidators;
use Exception;

class ClientProjectsController extends Controller
{

    /**
     * Activates or inactivates projects status.
     */
    public function patchProjectStatus(Request $request){
        $request->validate([
            'id'=>'required|integer|gt:0|exists:projects,id',
            'lkProjectStatusId'=>'required|integer|between:1,2'
        ]);

        // Validates if the requested project ID belongs to the user.
        if(!CustomValidators::validateProjectId($request)){
            return response()->
            json(['error'=>CustomValidators::$invalidProjectIdError], 500); 
        }

        $activeStatus = LkProjectStatus::where('description','=','active')->first()->id;
        $inactiveStatus = LkProjectStatus::where('description','=','inactive')->first()->id;

        try {
            
            $project = Projects::where('clientId','=',$request['clientId'])
                                ->where('id','=',$request['id'])
                                ->first();

            $project->update([                
                'lkProjectStatusId' => $request['lkProjectStatusId'] == 1 
                                        ? $activeStatus 
                                        : $inactiveStatus,
            ]);

            return response()->
            json(['result' => $project], 200);

        } catch (Exception $e) {
            return response()->json(
                env('APP_ENV') == 'local' ? $e : ['result' => ['message' => 'Unable to update project status.']], 500
              );    
        }

    }

    /**
     * Deletes projects that have no reference in Modules table.
     */
    public function deleteProject(Request $request){
        $request->validate([
            'id'=>'required|integer|gt:0|exists:projects,id'
        ]);

        // Validates if the requested project ID belongs to the user.
        if(!CustomValidators::validateProjectId($request)){
            return response()->
            json(['error'=>CustomValidators::$invalidProjectIdError], 500); 
        }

            try {
                
                $project = Projects::with('modules')
                                    ->where('id','=',$request['id'])
                                    ->where('clientId','=',$request['clientId'])
                                    ->first();

            if(!is_null($project)){

                if(count($project['modules']) > 0){
      
                    return response()->
                    json(['error'=>'Unable to delete, project is in use.'], 500); 
    
                }else{               
    
                    $project->delete();
    
                    return response()->
                    json(['status'=>'success'], 200);
    
                }
                
            }
            
        } catch (Exception $e) {
            return response()->json(
                env('APP_ENV') == 'local' ? $e : ['result' => ['message' => 'Unable to delete project.']], 500
              );    
        }
    }

    /**
     * Updates a project by ID.
     */
    public function patchProject(Request $request){

        $request->validate([
                    'id'=>'required|integer|gt:0|exists:projects,id',
                    'projectKey'=>'required|max:10',
                    'saveToJira'=>'required|integer|between:0,1', 
                ]
        );

        // Validates if the requested project ID belongs to the user.
        if(!CustomValidators::validateProjectId($request)){
            return response()->
            json(['error'=>CustomValidators::$invalidProjectIdError], 500); 
        }

        if($request['saveToJira'] == 1){
            $request->validate([
                'jiraId'=>'required|integer|gt:0',
            ]);
        }

        // Check if duplicate project exists before update.
        $duplicateExists = Projects::where('clientId','=',$request['clientId'])
                                    ->where('id','!=',$request['id'])
                                    ->where(function($query) use ($request)
                                    {                                        
                                        // Check for jiraId match only if saveToJira is 1 (true).
                                        $request['saveToJira'] == 1
                                        ? $query->whereRaw('LOWER(projectKey) = LOWER(?)', ["{$request['projectKey']}"])
                                          ->orWhere('jiraId','=',$request['jiraId'])
                                        : $query->whereRaw('LOWER(projectKey) = LOWER(?)', ["{$request['projectKey']}"]);
                                    
                                    })->first();

        if(!is_null($duplicateExists)){
            $errResponse = [
                'error' => 'project already exists',
            ];

            return response()->
            json($errResponse, 500);
        }

        try {

            $project = Projects::where('clientId','=', $request['clientId'])
                               ->where('id','=',$request['id'])->first();

            if(!is_null($project)){

                $project->update([
                    'projectKey' => $request['projectKey'],                    
                    'jiraId' => $request['saveToJira'] == 0?
                                null : $request['jiraId'],
                ]);

                if(isset($request['description'])){
                    $project->update(['description' => $request['description']]);
                }

                return response()->
                json(['result' => $project], 200);

            }

        } catch (Exception $e) {
            return response()->json(
                env('APP_ENV') == 'local' ? $e : ['result' => ['message' => 'Unable to update the project.']], 500
              );
        }

    }

    /**
     * Gets project list based on the clientId and search query
     */
    public function getProject(Request $request){

        $request->validate([
            'query'=>'required|max:10',
            'includeInactive'=>'required|integer|between:0,1',
        ]);

        try {

            $projects = null;
            $query = $request['query'];

            if($request['includeInactive'] == 1){

                $projects = Projects::with('modules')
                                    ->where('clientId','=',$request['clientId'])
                                    ->where('projectKey','like', '%'.$query.'%')
                                    ->get();

                // Check if selected project is referenced by modules. Allow delete if not referenced.
                foreach($projects as $project){
                    $project['allowDelete'] = count($project['modules']) > 0 ? 0 : 1;
                }

            }elseif($request['includeInactive'] == 0){

                $activeStatus = LkProjectStatus::where('description','=','active')->first()->id;

                $projects = Projects::with('modules')
                                    ->where('clientId','=',$request['clientId'])
                                    ->where('LkProjectStatusId','=',$activeStatus)
                                    ->where('projectKey','like', '%'.$query.'%')
                                    ->get();

                // Check if selected project is referenced by modules. Allow delete if not referenced.
                foreach($projects as $project){
                    $project['allowDelete'] = count($project['modules']) > 0 ? 0 : 1;
                }
            }

            foreach($projects as $project){
                $project['saveToJira'] = $project['jiraId'] == null ? 0 : 1;
            }

            return response()->
            json(['result' => $projects], 200);

        } catch (Exception $e) {
            return response()->json(
                env('APP_ENV') == 'local' ? $e : ['result' => ['message' => 'Unable to search for project.']], 500
              );
        }

    }

    /**
     * Creates a new project for client.
     */
    public function postProject(Request $request){

        $request->validate([
            'projectKey'=>'required|max:10',
            'saveToJira'=>'required|integer|between:0,1',
        ]);

        $jiraProjects = null;

        if($request['saveToJira'] == 1){
            $request->validate([
                'jiraId'=>'required|integer|gt:0'
            ]);

            $jiraProjects = Projects::where('clientId','=',$request['clientId'])
                                    ->where('jiraId','=',$request['jiraId'])
                                    ->orWhereRaw('LOWER(projectKey) = LOWER(?)', ["{$request['projectKey']}"])
                                    ->first();

        }elseif($request['saveToJira'] == 0){

            $jiraProjects = Projects::where('clientId','=',$request['clientId'])
                                    ->whereRaw('LOWER(projectKey) = LOWER(?)', ["{$request['projectKey']}"])
                                    ->first();
        }

        if(!is_null($jiraProjects)){

            $errResponse = [
                'error' => 'project already exists',
            ];

            return response()->
            json($errResponse, 500);
        }

        try {

            $activeStatus = LkProjectStatus::where('description','=','active')->first()->id;

            $projects = new Projects();
            $projects['projectKey'] = $request['projectKey'];
            $projects['clientId'] = $request['clientId'];

            $projects['jiraId'] =
            $request['saveToJira'] === 1
            ? $request['jiraId']
            : null;
            $projects['LkProjectStatusId'] = $activeStatus;

            if(isset($request['description'])){
                $projects['description'] = $request['description'];
            }

            $projects->save();

            return response()->
            json(['result' => $projects], 200);

        } catch (Exception $e) {
            return response()->json(
                env('APP_ENV') == 'local' ? $e : ['result' => ['message' => 'Unable to create project.']], 500
              );
        }
    }
}
