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
use App\Http\Custom\SaveFileHelper;
use App\Http\Controllers\BugAttachmentsController;


use Illuminate\Http\Request;

class ModuleBugs extends Controller
{
    public function postBug(Request $request){
        
        $request->validate([
            'moduleId'=>'required|integer|exists:modules,moduleId',
            'title'=>'required|string|max:100|min:1',
            'actualResult'=>'required|string|max:1000|min:1',
            'description'=>'required|string|max:1000|min:1',
            'stepsToReproduce'=>'required|string|max:1000|min:1',
            'expectedResult'=>'required|string|max:1000|min:1',
            'xpath'=>'required|string|max:500|min:1',
            'screenshot'=>'required',
        ]);

        try {

            $activeBugstatus = LkBugStatus::where('description','=','active')->first()->id;
            
            $bug = new ModuleBug();
            $bug['moduleId'] = $request['moduleId'];
            $bug['lkBugStatusId'] = $activeBugstatus;
            $bug->save();

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

            // Load all relationships before return.
            $bug->title;
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
}
