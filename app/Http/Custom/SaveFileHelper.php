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

    
    /**
     * Saves blob as png file.
     * @param $request - incoming request.
     * @param $category - either 'screenshots' or 'attachments'.
     * @param $fileExtension - specify subject file's extension.
     */
    public function saveBlobAsFile($request, $category, $fileExtension){
        // Files are saved in 'month-Year' folders.
        $monthYear = Carbon::now()->format('m-Y');
        $unixAsFileName = time();
        $directoryPath = 'media-repository/'.$request['uuid'].'/'.$category.'/'.$monthYear;
        $filePath = $directoryPath.'/'.$unixAsFileName.'.'.$fileExtension;
        $fullPath = getcwd().'/'.$filePath;
        $decodedImage = SaveFileHelper::decodeBlob($request['screenshot']);  
        
        SaveFileHelper::createMediaDirectory($directoryPath);
        file_put_contents($fullPath, $decodedImage);

        // Returns file's saved path.
        return $filePath;
    }

    /**
     * Saves saves attached file.
     * @param $request - incoming request.
     * @param $category - either 'screenshots' or 'attachments'.
     */    
    public function saveAttachmentAsFile($request, $category){
        // Files are saved in 'month-Year' folders.
        $monthYear = Carbon::now()->format('m-Y');
        $directoryPath = 'media-repository/'.$request['uuid'].'/'.$category.'/'.$monthYear;
        $filePath = $directoryPath.'/'.SaveFileHelper::getFileName();

        if(!SaveFileHelper::checkFileExists($filePath)){
            SaveFileHelper::createMediaDirectory($directoryPath);

            // Read file from temporary location.
            $file = file_get_contents($_FILES['attachment']['tmp_name']);
            file_put_contents($filePath, $file);      

            // Returns file's saved path.
            return $result = [
                'saved' => true,
                'filePath' => $filePath,
            ];
        }else{
            return $result = [
                'saved' => false,
            ];
        }
        
    }

    /**
     * Gets uploaded file's name with extension.
     */
    public function getFileName(){
        $fileName = $_FILES['attachment']['name'];
        return $fileName;
    }

    /**
     * Checks if file exists at given path.
     * @param $filePath - file path to check.
     */
    public function checkFileExists($filePath){
       return file_exists($filePath) ? true : false; 
    }

}
