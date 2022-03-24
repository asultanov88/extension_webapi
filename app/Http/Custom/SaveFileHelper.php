<?php

namespace App\Http\Custom;

use Carbon\Carbon;

class SaveFileHelper
{    
    /**
     * Creates a media directory for each user based on user's UUID.
     */
    public function createMediaDirectory($directoryPath){        
        // The location of the dir: *public_folder/media-repository/*user_uuid.
        if(!is_dir($directoryPath)){
            mkdir($directoryPath, 0755, true);            
        }          
    }

    /**
     * Decodes image_blob.
     */    
    public function decodeBlob($blob){
        // $data[ 0 ] == "data:image/png;base64"
        // $data[ 1 ] == "actual base64 string"
        $data = explode(',', $blob);
        // removing double quotes form the beggining and end.
        $data_base64 = trim($data[1],'"');

        return base64_decode($data_base64);
    }

    
    // Saves blob as png file.
    public function saveBlobAsFile($request){
        // Files are saved in 'month-Year' folders.
        $monthYear = Carbon::now()->format('m-Y');
        $unixAsFileName = time();
        $directoryPath = 'media-repository/'.$request['uuid'].'/'.$monthYear;
        $filePath = $directoryPath.'/'.$unixAsFileName.'.png';
        $fullPath = getcwd().'/'.$filePath;
        $decodedImage = SaveFileHelper::decodeBlob($request['screenshot']);  
        
        SaveFileHelper::createMediaDirectory($directoryPath);
        file_put_contents($fullPath, $decodedImage);

        return $filePath;
    }

}
