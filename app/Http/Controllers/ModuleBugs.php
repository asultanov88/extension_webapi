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


use Illuminate\Http\Request;

class ModuleBugs extends Controller
{
    /**
     * Path bug by Id 
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
            'xpath'=>'required|string|max:500|min:1',
            'environmentId'=>'required|integer|exists:environments,environmentId',
            'screenshot'=>'required',
        ]);

        $bug = ModuleBug::join('modules','modules.moduleId','=','module_bugs.moduleId')
                        ->join('projects','projects.id','=','modules.projectId')  
                        ->where('module_bugs.bugId','=',$request['bugId'])
                        ->where('projects.clientId','=',$request['clientId'])
                        ->first();
                        
        return $bug;

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
            'query'=>'required|string|min:2'
        ]);

        try {
            
            $bugs = BugGlobalSearch::where('searchKeyword','like', '%'.$request['query'].'%')
                                    ->join('module_bugs','module_bugs.bugId','=','bug_global_searches.bugId')
                                    ->join('bug_titles','bug_titles.bugId','=','module_bugs.bugId')
                                    ->join('bug_xpath','bug_xpath.bugId','=','module_bugs.bugId')
                                    ->join('bug_screenshots','bug_screenshots.bugId','=','module_bugs.bugId')                                  
                                    ->join('modules','modules.moduleId','=','module_bugs.moduleId')
                                    ->join('projects','projects.id','=','modules.projectId')  
                                    ->where('projects.clientId','=',$request['clientId'])
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
    public function getBugdetails(Request $request){
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
                                'projects.id AS projectId',
                                'projects.projectKey',
                                'modules.name AS moduleName',
                                'module_bugs.created_at',
                                'module_bugs.updated_at',
                                )
                            );
            
            // Construct new object to represent bug.
            $result = [
                'bugId' => $bug['bugId'], 
                'bugIndex' => strtoupper($bug['projectKey']).'-'.$bug['bugId'],
                'projectId' => $bug['projectId'],
                'projectName' => $bug['projectKey'],               
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
                'screenshots' => $this->getPath($bug->screenshot, 'screenshotPath'),
                'attachments' => $this->getPath($bug->attachment, 'attachmentPath'),
                'createdAt' => $bug['created_at'],
                'updatedAt' => $bug['updated_at'],
            ];
            
            return response()->
            json(['result' => $result], 200);
            
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
        ]);

        try {

            /**
             * Reformat dates to match SQL timestamp format.
             * Substracting 1 day from $fromDate and adding 1 day to $toDate 
             * in order to cover timezone differences.
             */ 
            $fromDate = Carbon::parse($request['fromDate'])->subDay();
            $toDate = Carbon::parse($request['toDate'])->addDay();
          
            $activeBugstatus = LkBugStatus::where('description','=','active')->first()->lkBugStatusId;

            $bugs = ModuleBug::join('bug_titles','bug_titles.bugId','=','module_bugs.bugId')
                            ->join('bug_xpath','bug_xpath.bugId','=','module_bugs.bugId')
                            ->join('bug_screenshots','bug_screenshots.bugId','=','module_bugs.bugId')
                            ->join('bug_environments','bug_environments.bugId','=','module_bugs.bugId')
                            ->join('modules','modules.moduleId','=','module_bugs.moduleId')
                            ->join('projects','projects.id','=','modules.projectId')       
                            ->where('bug_environments.environmentId','=',$request['environmentId'])
                            ->where('modules.moduleId','=',$request['moduleId'])
                            ->where('module_bugs.lkBugStatusId','=',$activeBugstatus)
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
        ]);

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

            // saving the gloab search keyword.
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

            // Load all relationships before return.
            $bug->title;
            $bug->bugEnvironment->environment;
            $bug->actualResult;
            $bug->description;
            $bug->stepsToReproduce;
            $bug->expectedResult;
            $bug->xpath;
            
            // No need to load screenshot and attachment relationships for now.
            //$bug->screenshot;
            //$bug->attachment;
    
            return response()->
            json(['result' => $bug], 200);    

        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }
    }
    
    /**
     * Extracts screenshot path and returns array of public path.
     */
    private function getPath($pathArr, $key){

        $publicPathArr = [];

        foreach ($pathArr as $path) {
            $publicPath = SaveFileHelper::getPublicPath($path[$key]);
            array_push($publicPathArr, $publicPath);
        }

        return $publicPathArr;
    }
}
