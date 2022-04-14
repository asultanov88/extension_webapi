<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Custom\SaveFileHelper;
use App\Models\BugAttachment;
use App\Models\TempAttachment;


class BugAttachmentsController extends Controller
{
  /**
   * Deletes temporary attachment by uuid.
   */
  public function deleteTempAttachment(Request $request){

    $request->validate([
      'attachment_uuid'=>'required|exists:temp_attachments,uuid',
    ]);

    try {      
      $temp_attachment = TempAttachment::where('clientId', '=', $request['clientId'])
                                       ->where('uuid', '=', $request['attachment_uuid'])
                                       ->where('isPermanent','=', 0)
                                       ->first();

      $result = null;

      if(!is_null($temp_attachment)){

        $result = SaveFileHelper::deleteFile($temp_attachment['tempPath']);

        if($result){
          $temp_attachment->delete();
        }  

      }

      return $result ? response()->json(['result' => 'success'], 200)
                       : response()->json(['result' => 'fail'], 500); 
                       
    } catch (Exception $e) {
      return response()->
      json($e, 500);
    }

  }


  /**
   * Saves attachment as temporary file.
   */
  public function postAttachment(Request $request){

    try {       

      if(!empty($_FILES['attachment'])){

        $temp_file = SaveFileHelper::saveAttachmentAsTempFile($request);

        $tempAttachment = new TempAttachment();
        $tempAttachment['clientId'] = $request['clientId'];
        $tempAttachment['uuid'] = $temp_file['uuid'];
        $tempAttachment['fileName'] = $temp_file['name'];
        $tempAttachment['tempPath'] = $temp_file['tempPath'];
        $tempAttachment->save();
  
        return response()->
        json(['result' => $tempAttachment['uuid']], 200);

      }else{
        return response()->
        json(['result' => 'no attachment found.'], 500);
      }  

    } catch (Exception $e) {
      return response()->
      json($e, 500);
    }
                
  }

  /**
   * Moves temporary file as permanent attachments.
   */
  public function makeAttachmentPermanent($attachmentUuid, $clientUuid, $clientId, $bug){

    try {

        $temp_attachment = TempAttachment::where('clientId', '=', $clientId)
                                          ->where('uuid', '=', $attachmentUuid)
                                          ->where('isPermanent','=', 0)
                                          ->first();

        if(!is_null($temp_attachment)){            
          $saveStatus = SaveFileHelper::saveTempFileAsPermanent($temp_attachment, 'attachments', $clientUuid, $bug['bugId']);

          if($saveStatus['saved']){
            $attachmentPath = $saveStatus['filePath'];
            $attachment = new BugAttachment();
            $attachment['attachmentPath'] = $attachmentPath;
            $bug->attachment()->save($attachment);

            // Mark temp file as permanent in 'temp_attachments' table.
            $temp_attachment->update([
              'isPermanent' => 1,
            ]); 
          }            
        }  
      
    } catch (Exception $e) {
      return $e;
    }
  }
}