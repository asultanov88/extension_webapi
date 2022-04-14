<?php

namespace App\Http\Custom;

use Carbon\Carbon;
use Illuminate\Support\Str;

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
    public function saveBlobAsFile($request, $category, $fileExtension, $bug){

        $uuidAsFileName = Str::uuid()->toString();;
        $directoryPath = 'media-repository/'.$request['uuid'].'/'.$category.'/'.$bug['bugId'];
        $filePath = $directoryPath.'/'.$uuidAsFileName.'.'.$fileExtension;
        $fullPath = getcwd().'/'.$filePath;
        $decodedImage = SaveFileHelper::decodeBlob($request['screenshot']);  
        
        SaveFileHelper::createMediaDirectory($directoryPath);
        file_put_contents($fullPath, $decodedImage);

        // Returns file's saved path.
        return $filePath;
    }

    public function saveAttachmentAsTempFile($request){

        $uuidxAsFileName = Str::uuid()->toString(); 
        $fileExtension = SaveFileHelper::getFileExtension();
        $directoryPath = 'media-repository/temp_attachments/'.$request['uuid'];
        $filePath = $directoryPath.'/'.$uuidxAsFileName.'.'.$fileExtension;
        SaveFileHelper::createMediaDirectory($directoryPath);
        // Read file from temporary location.
        $file = file_get_contents($_FILES['attachment']['tmp_name']);
        file_put_contents($filePath, $file);      

        return [
            'name' => SaveFileHelper::getFileName(),
            'tempPath' => $filePath,
            'uuid' => $uuidxAsFileName,
        ]; 
    }

    /**
     * Saves saves attached file.
     * @param $request - incoming request.
     * @param $category - either 'screenshots' or 'attachments'.
     */    
    public function saveTempFileAsPermanent($temp_attachment, $category, $clientUuid, $bugId){ 

        $directoryPath = 'media-repository/'.$clientUuid.'/'.$category.'/'.$bugId;
        $filePath = $directoryPath.'/'.$temp_attachment['fileName'];
        $tempFilePath = $temp_attachment['tempPath'];

        // Proceed is temporary file exists.
        if(SaveFileHelper::checkFileExists($tempFilePath)){

            SaveFileHelper::createMediaDirectory($directoryPath);

            // Add unix prefix to file name if file name already exists.
            if(SaveFileHelper::checkFileExists($filePath)){
                $filePath = $directoryPath.'/'.time().'_'.$temp_attachment['fileName'];
            }

            // Read file from temporary location.
            $file = file_get_contents($tempFilePath);
            // Write to new location.
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
     * Deletes file by path.
     */
    public function deleteFile($filePath){
        
        if(SaveFileHelper::checkFileExists($filePath)){
            unlink($filePath);
            return true;
        }else{
            return false;
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
     * Gets uploaded file's extension.
     */
    public function getFileExtension(){
        $fileName = $_FILES['attachment']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        return $fileExtension;
    }

    /**
     * Checks if file exists at given path.
     * @param $filePath - file path to check.
     */
    public function checkFileExists($filePath){
       return file_exists($filePath) ? true : false; 
    }

    /**
     * Generates public url for file path.
     * @param $filePath - path to a file.
     */
    public function getPublicPath($filePath){
        return  env('APP_URL').'/'.$filePath;
    }

}
