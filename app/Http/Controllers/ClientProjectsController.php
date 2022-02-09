<?php

namespace App\Http\Controllers;

use App\Models\LkProjectStatus;
use Illuminate\Http\Request;
use App\Models\Projects;

class ClientProjectsController extends Controller
{
    /**
     * Updates a project by ID.
     */
    public function patchProject(Request $request){

        $request->validate([
                'id'=>'required|integer|gt:0',
                'projectKey'=>'required|max:10',
                'saveToJira'=>'required|integer|between:0,1',
                'isActive'=>'required|integer|between:0,1'
            ]
        );

        if($request['saveToJira'] == 1){
            $request->validate([
                'jiraId'=>'required|integer|gt:0',
            ]);
        }

        try {

            $project = Projects::where('clientId','=', $request['clientId'])
                               ->where('id','=',$request['id'])->first();

            if(!is_null($project)){

                $activeStatus = LkProjectStatus::where('description','=','active')->first()->id;
                $inactiveStatus = LkProjectStatus::where('description','=','inactive')->first()->id;

                $project->update([
                    'projectKey' => $request['projectKey'],
                    'lkProjectStatusId' => $request['isActive'] == 1?
                                           $activeStatus : $inactiveStatus,
                    'jiraId' => $request['saveToJira'] == 0?
                                null : $request['jiraId'],
                ]);
            }

            $response = [
                'result'=>'success',
            ];

            return response()->
            json($response, 200);

        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }

    }

    /**
     * Gets project list based on the clientId and search query
     */
    public function getProject(Request $request){

        $request->validate([
            'query'=>'required|max:50',
            'includeInactive'=>'required|integer|between:0,1',
        ]);

        try {

            $projects = null;

            if($request['includeInactive'] == 1){

                $projects = Projects::where('clientId','=',$request['clientId'])
                                    ->whereRaw('LOWER(projectKey) LIKE LOWER(?)', ["%{$request['query']}%"])
                                    ->get();

            }elseif($request['includeInactive'] == 0){

                $activeStatus = LkProjectStatus::where('description','=','active')->first()->id;

                $projects = Projects::where('clientId','=',$request['clientId'])
                                    ->where('LkProjectStatusId','=',$activeStatus)
                                    ->whereRaw('LOWER(projectKey) LIKE LOWER(?)', ["%{$request['query']}%"])
                                    ->get();
            }

            $response = [
                'result' => $projects
            ];

            return response()->
            json($response, 200);

        } catch (Exception $e) {
            return response()->
            json($e, 500);
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
                'result' => $jiraProjects,
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

            $projects->save();

            return response()->
            json($projects, 200);

        } catch (Exception $e) {

            return response()->
            json($e, 500);

        }

    }
}
