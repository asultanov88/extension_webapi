<?php

namespace App\Http\Controllers;
use App\Models\ModuleBug;
use App\Models\BugActualResults;
use App\Models\BugDescription;
use App\Models\BugExpectedResults;
use App\Models\BugStepsToReproduce;
use App\Models\BugXpath;
use App\Models\BugScreenshot;
use App\Models\BugTitle;
use App\Models\LkBugStatus;
use App\Models\BugEnvironment;
use App\Models\Modules;
use App\Models\Projects;
use App\Models\BugGlobalSearch;
use App\Http\Custom\SaveFileHelper;
use App\Http\Controllers\BugAttachmentsController;
use Carbon\Carbon;
use App\Http\Custom\CustomValidators;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Http\Controllers\CustomControllers\ClientControllerDefinition;
use App\Http\Controllers\CustomControllers\TestUserController;
use Illuminate\Support\Facades\Crypt;

class ModuleBugs extends Controller
{
    /**
     * Creates Jira issue.
     */
    public function createJiraIssue(Request $request, $returnJson = true){
        $request->validate([
            'jiraSettings' => 'required',
            'jiraSettings.ClientJiraControllerId' => 'required|integer|min:1',
            'jiraSettings.JiraDomain' => 'required|string',
            'jiraSettings.JiraUserName' => 'required|string',
            'jiraSettings.JiraApiKey' => 'required|string',
            'jiraSettings.JiraIssueType' => 'required|integer|min:1',
            'bugId'=>'required|integer|exists:module_bugs,bugId',
        ]);        

        try {
            
            $jiraDomain = Crypt::decryptString($request->jiraSettings['JiraDomain']);
            $jiraUsername = Crypt::decryptString($request->jiraSettings['JiraUserName']);
            $jiraApiToken = Crypt::decryptString($request->jiraSettings['JiraApiKey']);
            $issueTypeId = $request->jiraSettings['JiraIssueType'];
            $bugId = $request['bugId'];
            $bugObject = $this->getBugdetails($request, false, false);
            $projectId = $bugObject['projectJiraId'];
            $unableToCreateJira = false;
    
            $authHeader = 'Basic '.base64_encode("$jiraUsername:$jiraApiToken");
            $client = new Client([
                'headers' => [
                    'Authorization'=>$authHeader, 
                    'content-type'=>'application/json'
                ],
                ]);
    
            $body = null;
    
            // Depending on client's Jira controller id, body is geberate from client specific class.
            switch(intval($request->jiraSettings['ClientJiraControllerId'])){
                case ClientControllerDefinition::TestClient:
                    $body = TestUserController::getJiraBody($projectId, $issueTypeId, $bugObject);
                    break;
                default: 
                    $body = null;
            }
    
            if($body != null){
    
                $request = $client->request('POST', "https://$jiraDomain/rest/api/2/issue/", ['json'=>$body]);
    
                $response = json_decode($request->getBody());    

                if(isset($response->id)){
                    // return true if the function is called internally.
                    $this->updateBugJiraLinks($bugId, $response, $jiraDomain);
                    return $returnJson ? response()->json(['result' => 'success'], 200) : true;
                }else{
                    $unableToCreateJira = true;
                }
            }else{
                $unableToCreateJira = true;
            }

            if($unableToCreateJira){
                return $returnJson ? response()->json(['result'=>'Unable to create Jira body.'], 500) : false;
            }

        } catch (\Exception $e) {
            return $returnJson ? response()->json(['result'=>'Unable to create Jira ticket.'], 500) : false;
        }        
    }

    /**
     * Generate Jira URL link to create Jira bug via GET call.
     */
    public function getJiraLink(Request $request){

    }

    /**
     * Gets bug screenshot as blob
     */
    public function getScreenshotAsBlob(Request $request){
        $request->validate([
            'bugId'=>'required|integer|exists:module_bugs,bugId',
        ]);

        try {

            $bug = ModuleBug::where('bugId','=',$request['bugId'])->first();  
            $screenshotPath = $bug->screenshot[0]->screenshotPath;
            $screenshotBlob = null;
    
            if(SaveFileHelper::checkFileExists($screenshotPath)){
                // Bug has only 1 screenshot, others are attachments.
                $screenshotBlob = base64_encode(file_get_contents($screenshotPath));
            }
            
            return $screenshotBlob != null 
            ? response()->json(['result' => $screenshotBlob], 200)
            : response()->json(['result' => 'no screenshot found'], 500);

        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }
    }

    /**
     * Patch bug screenshot
     */
    public function patchBugScreenshot(Request $request){
        $request->validate([
            'bugId'=>'required|integer|exists:module_bugs,bugId',
            'xpath'=>'required|string|max:500|min:1',
            'screenshot'=>'required',
        ]);
        
        // Validates if the requested bug ID belongs to the user.
        if(!CustomValidators::validateBugId($request)){
            return response()->
            json(['error'=>CustomValidators::$invalidBugIdError], 500); 
        }

        try {

            $bug = ModuleBug::where('bugId','=',$request['bugId'])->first();

            // updating bug xpath.
            $bug->xpath()->update(['xpath'=>$request['xpath']]);

            // updating bug screenshot.
            $saveFileHelper = new SaveFileHelper();
            $imagePath = $saveFileHelper->saveBlobAsFile($request, 'screenshots', 'png', $bug);
            $oldScreenshotPath = $bug->screenshot[0]->screenshotPath;
            $bug->screenshot()->update(['screenshotPath'=>$imagePath]);
            $saveFileHelper->deleteFile($oldScreenshotPath);

            return response()->
            json(['result' => 'success'], 200);

        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }


    }

    /**
     * Patch bug by Id 
     */
    public function patchBug(Request $request){
        $request->validate([
            'bugId'=>'required|integer|exists:module_bugs,bugId',
            'moduleId'=>'required|integer|exists:modules,moduleId',
            'title'=>'required|string|max:100|min:1',
            'actualResult'=>'required|string|max:1000|min:1',
            'description'=>'required|string|max:1000|min:1',
            'stepsToReproduce'=>'required|string|max:1000|min:1',
            'expectedResult'=>'required|string|max:1000|min:1',
            'environmentId'=>'required|integer|exists:environments,environmentId',            
        ]);

        // Validates if the requested bug ID belongs to the user.
        if(!CustomValidators::validateBugId($request)){
            return response()->
            json(['error'=>CustomValidators::$invalidBugIdError], 500); 
        }
        
        try {
            
            $bug = ModuleBug::join('modules','modules.moduleId','=','module_bugs.moduleId')
                            ->join('projects','projects.id','=','modules.projectId')  
                            ->where('module_bugs.bugId','=',$request['bugId'])
                            ->where('projects.clientId','=',$request['clientId'])
                            ->first();

            $bug->update(['moduleId' => $request['moduleId']]);       
            $bug->bugEnvironment()->update(['environmentId'=>$request['environmentId']]);
            $bug->title()->update(['title'=>$request['title']]);
            $bug->actualResult()->update(['actualResults'=>$request['actualResult']]);        
            $bug->description()->update(['description'=>$request['description']]);
            $bug->stepsToReproduce()->update(['stepsToReproduce'=>$request['stepsToReproduce']]);
            $bug->expectedResult()->update(['expectedResult'=>$request['expectedResult']]);
            // Updates the timestamps.
            $bug->touch();
            $bug->fresh();
                                
            if(!is_null($request['attachments']) && is_array($request['attachments'])){
                // Make attachments permanent if available.
                foreach ($request['attachments'] as $attachmentUuid){
                    BugAttachmentsController::makeAttachmentPermanent($attachmentUuid, $request['uuid'], $request['clientId'], $bug);
                }
            }

            // saving the gloabal search keyword.
            $project = Modules::where('moduleId','=',$bug['moduleId'])
            ->join('projects','projects.id','=','modules.projectId')
            ->first(
                array(                                      
                    'projects.id',
                    'projects.projectKey',                                      
                )
                );
            
            $searchKeyword = strtolower($project['projectKey']).'-'.$bug['bugId'].' '.strtolower($bug->title['title']);
            BugGlobalSearch::where('bugId','=',$bug['bugId'])->update(['searchKeyword' => $searchKeyword]);
            
            return response()->
            json(['result' => 'success'], 200);

        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }
    }  
    
    /**
     * Update bug status.
     */
    public function patchBugStatus(Request $request){
        try {
            $request->validate([
                'bugId'=>'required|integer|exists:module_bugs,bugId',
                'lkBugStatusId'=>'required|integer|exists:lk_bug_statuses,lkBugStatusId',
            ]);

            // Validates if the requested bug ID belongs to the user.
            if(!CustomValidators::validateBugId($request)){
                return response()->
                json(['error'=>CustomValidators::$invalidBugIdError], 500); 
            }
    
            $bug=ModuleBug::join('modules','modules.moduleId','=','module_bugs.moduleId')
                            ->join('projects','projects.id','=','modules.projectId')  
                            ->where('module_bugs.bugId','=',$request['bugId'])
                            ->where('projects.clientId','=',$request['clientId'])
                            ->first();
    
            $bug->update([
                'lkBugStatusId'=>$request['lkBugStatusId']
            ]);
    
            return response()->
            json(['result' => 'success'], 200);

        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }
    }

    /**
     * Get the list of bug statuses.
     */
    public function getBugStatusList(){
        try {

            $bugStatuses = LkBugStatus::all(array('lkBugStatusId','description'));
            
            return response()->
            json(['result' => $bugStatuses], 200); 

        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }
    }

    /**
     * Search for a bug by query keyword.
     */
    public function getGlobalSearch(Request $request){

        $request->validate([
            'query'=>'required|string|min:2',
            'includeCanceled'=>'required|integer|min:0|max:1',
            'includeCompleted'=>'required|integer|min:0|max:1',
        ]);

        try {

            $activeBugstatus = LkBugStatus::where('description','=','active')->first()->lkBugStatusId;
            $inProgressBugStatus = LkBugStatus::where('description','=','in-progress')->first()->lkBugStatusId;
            $cancelledBugStatus = LkBugStatus::where('description','=','cancelled')->first()->lkBugStatusId;
            $completedBugStatus = LkBugStatus::where('description','=','completed')->first()->lkBugStatusId;
            
            $bugs = BugGlobalSearch::where('searchKeyword','like', '%'.$request['query'].'%')
                                    ->join('module_bugs','module_bugs.bugId','=','bug_global_searches.bugId')
                                    ->join('bug_titles','bug_titles.bugId','=','module_bugs.bugId')
                                    ->join('bug_xpath','bug_xpath.bugId','=','module_bugs.bugId')
                                    ->join('bug_screenshots','bug_screenshots.bugId','=','module_bugs.bugId')                                  
                                    ->join('modules','modules.moduleId','=','module_bugs.moduleId')
                                    ->join('projects','projects.id','=','modules.projectId')  
                                    ->where('projects.clientId','=',$request['clientId'])
                                    ->where(function($query) use ($activeBugstatus, $inProgressBugStatus)
                                    {     
                                       $query->where('module_bugs.lkBugStatusId','=',$activeBugstatus)
                                             ->orWhere('module_bugs.lkBugStatusId','=',$inProgressBugStatus); 
                                    })                           
                                    ->when($request['includeCanceled'] == 1, function($query) use ($request, $cancelledBugStatus)
                                        {
                                            return $query->orWhere('module_bugs.lkBugStatusId','=',$cancelledBugStatus);
                                        })
                                    ->when($request['includeCompleted'] == 1, function($query) use ($request, $completedBugStatus)
                                        {
                                            return $query->orWhere('module_bugs.lkBugStatusId','=',$completedBugStatus);
                                        })
                                    ->get(
                                        array(
                                            'projects.projectKey',
                                            'module_bugs.bugId',
                                            'bug_titles.title',
                                            'bug_xpath.xpath',
                                            'bug_screenshots.screenshotPath'
                                        )
                                    )->toArray();

            $result = [];

            foreach($bugs as $bug){
                // Setting bug index.
                $bug['bugIndex'] = $bug['projectKey'].'-'.$bug['bugId'];
                // Upper casing the first letter of the bug title.
                $bug['title'] = ucfirst($bug['title']);
                // Removing unsused projectKey.
                unset($bug['projectKey']);
                // Modifying the screenshot path for public access.
                $bug['screenshotPath'] = SaveFileHelper::getPublicPath($bug['screenshotPath']);
                array_push($result, $bug);
            }

            return response()->
            json(['result' => $result], 200); 

        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }
    }

    /**
     * Get bug details by bugId.
     */
    public function getBugdetails(Request $request, $includePublicPath = true, $returnJson = true){
        $request->validate([
            'bugId'=>'required|integer|exists:module_bugs,bugId',
        ]);
        
        try {

            $bug = ModuleBug::where('bugId','=',$request['bugId'])
                            ->join('modules','modules.moduleId','=','module_bugs.moduleId')
                            ->join('projects','projects.id','=','modules.projectId')    
                            ->where('projects.clientId','=',$request['clientId'])
                            ->first(
                                array(
                                    'module_bugs.bugId',
                                    'module_bugs.moduleId',
                                    'module_bugs.lkBugStatusId',
                                    'module_bugs.jiraTicketUrl',
                                    'projects.id AS projectId',
                                    'projects.jiraId AS jiraId',
                                    'projects.projectKey',
                                    'modules.name AS moduleName',
                                    'module_bugs.created_at',
                                    'module_bugs.updated_at',
                                )
                            );
            
            // Construct new object to represent bug.
            $result = null;

            if($bug){
                $result = [
                    'bugId' => $bug['bugId'], 
                    'bugIndex' => strtoupper($bug['projectKey']).'-'.$bug['bugId'],
                    'projectId' => $bug['projectId'],
                    'projectName' => $bug['projectKey'],  
                    'projectJiraId' => $bug['jiraId'],             
                    'moduleId' => $bug['moduleId'],
                    'moduleName' => $bug['moduleName'],
                    'lkBugStatusId' => $bug['lkBugStatusId'],
                    'lkBugStatus' => LkBugStatus::where('lkBugStatusId','=',$bug['lkBugStatusId'])->first()->description,
                    'bugEnvironment' => $bug['bugEnvironment']['environment']['name'],
                    'bugEnvironmentId' => $bug['bugEnvironment']['environmentId'],
                    'title' => $bug['title']['title'],
                    'description' => $bug['description']['description'],
                    'stepsToReproduce' => $bug['stepsToReproduce']['stepsToReproduce'],
                    'expectedResult' => $bug['expectedResult']['expectedResult'],
                    'actualResult' => $bug['actualResult']['actualResults'],
                    'xpath' => $bug['xpath']['xpath'],
                    'jiraTicketUrl' => $bug['jiraTicketUrl'],
                    'screenshots' => $includePublicPath ? $this->getPath($bug->screenshot, 'screenshotPath') : $bug->screenshot,
                    'attachments' => $includePublicPath ? $this->getPath($bug->attachment, 'attachmentPath') : $bug->attachment,
                    'createdAt' => $bug['created_at'],
                    'updatedAt' => $bug['updated_at'],
                ];
            }
            
            if($returnJson){
                return $result ? response()->json(['result' => $result], 200)
                               : response()->json(['result' => 'unable to get bug details'], 500);
            }else{
                return $result;
            }
            
        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }
    }

    /**
     * Get bug list by parameters.
     */
    public function getBugList(Request $request){
        $request->validate([
            'moduleId'=>'required|integer|exists:modules,moduleId',
            'environmentId'=>'required|integer|exists:environments,environmentId',
            'fromDate'=>'required|string',
            'toDate'=>'required|string',
            'includeCanceled'=>'required|integer|min:0|max:1',
            'includeCompleted'=>'required|integer|min:0|max:1',
        ]);

        // Validates if the requested module ID belongs to the user.
        if(!CustomValidators::validateModuleId($request)){
            return response()->
            json(['error'=>CustomValidators::$invalidModuleIdError], 500); 
        }

        try {

            /**
             * Reformat dates to match SQL timestamp format.
             * Substracting 1 day from $fromDate and adding 1 day to $toDate 
             * in order to cover timezone differences.
             */ 
            $fromDate = Carbon::parse($request['fromDate'])->subDay();
            $toDate = Carbon::parse($request['toDate'])->addDay();
          
            $activeBugstatus = LkBugStatus::where('description','=','active')->first()->lkBugStatusId;
            $inProgressBugStatus = LkBugStatus::where('description','=','in-progress')->first()->lkBugStatusId;
            $cancelledBugStatus = LkBugStatus::where('description','=','cancelled')->first()->lkBugStatusId;
            $completedBugStatus = LkBugStatus::where('description','=','completed')->first()->lkBugStatusId;

            $bugs = ModuleBug::join('bug_titles','bug_titles.bugId','=','module_bugs.bugId')
                            ->join('bug_xpath','bug_xpath.bugId','=','module_bugs.bugId')
                            ->join('bug_screenshots','bug_screenshots.bugId','=','module_bugs.bugId')
                            ->join('bug_environments','bug_environments.bugId','=','module_bugs.bugId')
                            ->join('modules','modules.moduleId','=','module_bugs.moduleId')
                            ->join('projects','projects.id','=','modules.projectId')       
                            ->where('bug_environments.environmentId','=',$request['environmentId'])
                            ->where('modules.moduleId','=',$request['moduleId'])                           
                            ->where(function($query) use ($activeBugstatus, $inProgressBugStatus)
                                    {     
                                       $query->where('module_bugs.lkBugStatusId','=',$activeBugstatus)
                                             ->orWhere('module_bugs.lkBugStatusId','=',$inProgressBugStatus); 
                                    })                           
                            ->when($request['includeCanceled'] == 1, function($query) use ($request, $cancelledBugStatus)
                                {
                                    return $query->orWhere('module_bugs.lkBugStatusId','=',$cancelledBugStatus);
                                })
                            ->when($request['includeCompleted'] == 1, function($query) use ($request, $completedBugStatus)
                                {
                                    return $query->orWhere('module_bugs.lkBugStatusId','=',$completedBugStatus);
                                })
                            ->where('projects.clientId','=',$request['clientId'])
                            ->whereDate('module_bugs.created_at','>=',$fromDate)
                            ->whereDate('module_bugs.created_at','<=',$toDate)
                            ->orderBy('module_bugs.bugId','DESC')
                            ->get(
                                array(
                                    'projects.projectKey',
                                    'module_bugs.bugId',
                                    'bug_titles.title',
                                    'bug_xpath.xpath',
                                    'bug_screenshots.screenshotPath'
                                )
                            )
                            ->toArray();

            $result = [];

            foreach($bugs as $bug){
                // Setting bug index.
                $bug['bugIndex'] = $bug['projectKey'].'-'.$bug['bugId'];
                // Upper casing the first letter of the bug title.
                $bug['title'] = ucfirst($bug['title']);
                // Removing unsused projectKey.
                unset($bug['projectKey']);
                // Modifying the screenshot path for public access.
                $bug['screenshotPath'] = SaveFileHelper::getPublicPath($bug['screenshotPath']);
                array_push($result, $bug);
            }

            return response()->
            json(['result' => $result], 200); 

        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }

    }

    /**
     * Create new bug.
     */
    public function postBug(Request $request){
        
        $request->validate([
            'moduleId'=>'required|integer|exists:modules,moduleId',
            'title'=>'required|string|max:100|min:1',
            'actualResult'=>'required|string|max:1000|min:1',
            'description'=>'required|string|max:1000|min:1',
            'stepsToReproduce'=>'required|string|max:1000|min:1',
            'expectedResult'=>'required|string|max:1000|min:1',
            'xpath'=>'required|string|max:500|min:1',
            'environmentId'=>'required|integer|exists:environments,environmentId',
            'screenshot'=>'required',
            'saveToJira'=>'required|integer|max:1|min:0',
        ]);

        // Validates if the requested module ID belongs to the user.
        if(!CustomValidators::validateModuleId($request)){
            return response()->
            json(['error'=>CustomValidators::$invalidModuleIdError], 500); 
        }

        try {

            $activeBugstatus = LkBugStatus::where('description','=','active')->first()->lkBugStatusId;
            
            $bug = new ModuleBug();
            $bug['moduleId'] = $request['moduleId'];
            $bug['lkBugStatusId'] = $activeBugstatus;
            $bug->save();

            $environment = new BugEnvironment();
            $environment['environmentId'] = $request['environmentId'];
            $bug->bugEnvironment()->save($environment);

            $title = new BugTitle();
            $title['title'] = $request['title'];
            $bug->title()->save($title);
    
            $actualResult = new BugActualResults();
            $actualResult['actualResults'] = $request['actualResult'];
            $bug->actualResult()->save($actualResult);
            
            $description = new BugDescription();
            $description['description'] = $request['description'];
            $bug->description()->save($description);
    
            $stepsToReproduce = new BugStepsToReproduce();
            $stepsToReproduce['stepsToReproduce'] = $request['stepsToReproduce'];
            $bug->stepsToReproduce()->save($stepsToReproduce);
    
            $expectedResult = new BugExpectedResults();
            $expectedResult['expectedResult'] = $request['expectedResult'];
            $bug->expectedResult()->save($expectedResult);
    
            $xpath = new BugXpath();
            $xpath['xpath'] = $request['xpath'];
            $bug->xpath()->save($xpath);

            // saving bug screenshot.
            $saveFileHelper = new SaveFileHelper();
            $imagePath = $saveFileHelper->saveBlobAsFile($request, 'screenshots', 'png', $bug);
            $screenshot = new BugScreenshot();
            $screenshot['screenshotPath'] = $imagePath;
            $bug->screenshot()->save($screenshot);

            if(!is_null($request['attachments']) && is_array($request['attachments'])){
                // Make attachments permanent if available.
                foreach ($request['attachments'] as $attachmentUuid){
                    BugAttachmentsController::makeAttachmentPermanent($attachmentUuid, $request['uuid'], $request['clientId'], $bug);
                }
            }

            // saving the gloabal search keyword.
            $project = Modules::where('moduleId','=',$request['moduleId'])
                              ->join('projects','projects.id','=','modules.projectId')
                              ->first(
                                  array(                                      
                                    'projects.id',
                                    'projects.projectKey',                                      
                                  )
                                );

            $globalSearch = new BugGlobalSearch();
            $globalSearch['bugId'] = $bug['bugId'];
            $globalSearch['searchKeyword'] = strtolower($project['projectKey']).'-'.$bug['bugId'].' '.strtolower($request['title']);
            $globalSearch->save();

            // Saving to Jira if saveToJira = 1.
            $jiraResponse = false;

            if($request['saveToJira'] == 1){    
                $request['bugId'] = $bug->bugId;
                $request->validate([
                    'jiraSettings' => 'required',
                    'jiraSettings.ClientJiraControllerId' => 'required|integer|min:1',
                    'jiraSettings.JiraDomain' => 'required|string',
                    'jiraSettings.JiraUserName' => 'required|string',
                    'jiraSettings.JiraApiKey' => 'required|string',
                    'jiraSettings.JiraIssueType' => 'required|integer|min:1',
                    'bugId'=>'required|integer|exists:module_bugs,bugId',
                ]);  
                $jiraResponse = $this->createJiraIssue($request, false);
            }

            // Add message to the response if Jira issue creation was unsuccessful.
            $result = ['result' => ['bugId' => $bug->bugId]];
            if(!$jiraResponse){
                $result['result']['message'] = 'Unable to create Jira ticket.';
            }
  
            return response()->
            json($result, 200);    

        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }
    }
    
    /**
     * Extracts screenshot or attachment path and returns array of public path.
     */
    private function getPath($pathArr, $key){

        $publicPathArr = [];

        foreach ($pathArr as $path) {
            $publicPath = SaveFileHelper::getPublicPath($path[$key]);
            if(str_contains($key, 'attachment')){
                // uuid of an attachment is needed only for attachments to be able to delete.
                array_push($publicPathArr, ['path'=>$publicPath, 
                                            'uuid'=>$path->uuid, 
                                            'fileName'=>$path->fileName]);
            }elseif(str_contains($key, 'screenshot')){
                array_push($publicPathArr, $publicPath);
            }            
        }
        return $publicPathArr;
    }  
    
    /**
     * Updates existing bug's Jira links.
     */
    private function updateBugJiraLinks($bugId, $response, $jiraDomain){

        $bug = ModuleBug::where('bugId','=',$bugId)->first();
        $bug->update([
            'jiraObjectUrl' => $response->self,
            'jiraTicket' => $response->key,
            'jiraId' => $response->id,
            'jiraTicketUrl' => "https://$jiraDomain/browse/$response->key",
        ]);        
    }
}
