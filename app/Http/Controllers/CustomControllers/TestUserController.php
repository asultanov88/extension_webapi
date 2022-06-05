<?php

namespace App\Http\Controllers\CustomControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestUserController extends Controller
{
    public function getJiraBody($projectId, $issueTypeId, $bugObject){

        $description = 
        "\n*Description:*\n"
        .$bugObject['description']
        ."\n*Steps to Reproduce:*\n"
        .$bugObject['stepsToReproduce']
        ."\n*Expected Result:*\n"
        .$bugObject['expectedResult']
        ."\n*Actual Result:*\n"
        .$bugObject['actualResult'];
               
        return ['fields'=>[
            'project' => ['id'=>$projectId],
            'summary' => $bugObject['title'],
            'description' => $description,
            'issuetype' => ['id'=>$issueTypeId]
        ]];

    }
}
