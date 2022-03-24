<?php

namespace App\Http\Controllers;
use App\Models\ModuleBug;
use App\Models\BugActualResults;
use App\Models\BugDescription;
use App\Models\BugExpectedResults;
use App\Models\BugStepsToReproduce;
use App\Models\BugXpath;
use App\Models\BugScreenshot;
use App\Http\Custom\SaveFileHelper;



use Illuminate\Http\Request;

class ModuleBugs extends Controller
{
    public function postBug(Request $request){
        
        $request->validate([
            'moduleId'=>'required|integer|exists:modules,moduleId',
            'actualResult'=>'required|string|max:1000|min:1',
            'description'=>'required|string|max:1000|min:1',
            'stepsToReproduce'=>'required|string|max:1000|min:1',
            'expectedResult'=>'required|string|max:1000|min:1',
            'xpath'=>'required|string|max:500|min:1',
            'screenshot'=>'required',
        ]);

        try {
            
            $bug = new ModuleBug();
            $bug['moduleId'] = $request['moduleId'];
            $bug->save();
    
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
            $imagePath = $saveFileHelper->saveBlobAsFile($request, 'screenshots', 'png');
            $screenshot = new BugScreenshot();
            $screenshot['screenshotPath'] = $imagePath;
            $bug->screenshot()->save($screenshot);

            // Load all relationships before return.
            $bug->actualResult;
            $bug->description;
            $bug->stepsToReproduce;
            $bug->expectedResult;
            $bug->xpath;
            $bug->screenshot;
    
            return response()->
            json(['result' => $bug], 200);    

        } catch (Exception $e) {
            return response()->
            json($e, 500);
        }
    }
}
