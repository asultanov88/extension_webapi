<?php

namespace App\Http\Controllers\CustomControllers;

use App\Http\Controllers\Controller;
use Exception;

class TestUserController extends Controller
{
    public function getJiraBody($projectId, $issueTypeId, $bugObject){

        $screenshotPath = $bugObject['screenshots'][0];
        // Array of attachment paths.
        $attachments = $bugObject['attachments'];

        $description = 
        "\n*Description:*\n"
        .$bugObject['description']
        ."\n*Steps to Reproduce:*\n"
        .$bugObject['stepsToReproduce']
        ."\n*Expected Result:*\n"
        .$bugObject['expectedResult']
        ."\n*Actual Result:*\n"
        .$bugObject['actualResult']
        ."\n*URL:*\n"
        .$bugObject['url']
        ."\n\n*EZBug Attachments:*\n"
        ."*[Screenshot|$screenshotPath]*\n";

        foreach($attachments as $attachment){
            $attachmentFileName = $attachment['fileName'];
            $attachmentPath = $attachment['path'];
            $description .= "*[$attachmentFileName|$attachmentPath]*\n";
        }         
               
        return ['fields'=>[
            'project' => ['id'=>$projectId],
            'summary' => $bugObject['title'],
            'description' => $description,
            'issuetype' => ['id'=>$issueTypeId]
        ]];

    }
}
