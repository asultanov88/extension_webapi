<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\ModuleBug;
use App\Models\BugActualResults;
use App\Models\BugDescription;
use App\Models\BugExpectedResults;
use App\Models\BugStepsToReproduce;
use App\Models\BugXpath;
use App\Models\BugScreenshot;



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
            $imagePath = $this->saveBlobAsFile($request);
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

    // Saves blob as png file.
    private function saveBlobAsFile(Request $request){
        // Files are saved in 'month-Year' folders.
        $monthYear = Carbon::now()->format('m-Y');
        $unixAsFileName = time();
        $directoryPath = 'media-repository/'.$request['uuid'].'/'.$monthYear;
        $filePath = $directoryPath.'/'.$unixAsFileName.'.png';
        $fullPath = getcwd().'/'.$filePath;
        $decodedImage = $this->decodeBlob($request['screenshot']);  
        
        $this->createMediaDirectory($directoryPath);
        file_put_contents($fullPath, $decodedImage);

        return $filePath;
    }

    /**
     * Creates a media directory for each user based on user's UUID.
     */
    private function createMediaDirectory($directoryPath){        
        // The location of the dir: *public_folder/media-repository/*user_uuid.
        if(!is_dir($directoryPath)){
            mkdir($directoryPath, 0755, true);            
        }          
    }

    /**
     * Decodes image_blob.
     */    
    private function decodeBlob($blob){
        // $data[ 0 ] == "data:image/png;base64"
        // $data[ 1 ] == "actual base64 string"
        $data = explode(',', $blob);
        // removing double quotes form the beggining and end.
        $data_base64 = trim($data[1],'"');

        return base64_decode($data_base64);
    }
}
