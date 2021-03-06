<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Custom\SaveFileHelper;
use Illuminate\Support\Str;
use Exception;

class GeneratePdf extends Controller
{
    /**
     * Generates a PDF document based on the bug object and return the public path.
     */
    public function generatePdf(Request $request){
        
        try {

            $htmlScreenshot = null;
            // Saving screenshot as temporary png file.
            if(isset($request['screenshot']) && strlen(trim($request['screenshot'])) > 1){
                $screenshotPath = SaveFileHelper::saveBlobForPdf($request['screenshot']);
                $htmlScreenshot = view('bugPdf', ['screenshot' => $screenshotPath])->render();
            }

            $pdfGeneralPath = 'media-repository/PDF/';
            $pdfFileName = Str::uuid()->toString().'.pdf';
            $filePath = $pdfGeneralPath.$pdfFileName;

            SaveFileHelper::createMediaDirectory($pdfGeneralPath);

            $mpdf = new \Mpdf\Mpdf([
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ]);
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;

            $html = view('bugPdf', ['project' => $request->project?$request->project:null,
                                    'module' => $request->module?$request->module:null,                         
                                    'environment' => $request->environment?$request->environment:null,                         
                                    'title' => $request->title?$request->title:null,                         
                                    'actualResult' => $request->actualResult?$request->actualResult:null,                         
                                    'description' => $request->description?$request->description:null,                         
                                    'stepsToReproduce' => $request->stepsToReproduce?$request->stepsToReproduce:null,                         
                                    'expectedResult' => $request->expectedResult?$request->expectedResult:null,                         
                                    'xpath' => $request->xpath?$request->xpath:null,
                                    'url' => $request->url?$request->url:null,
                                    ])->render();
            
            $mpdf->SetHeader('Bug Report||{PAGENO}');
            $mpdf->WriteHTML($html);

            // Add the second page only if screenshot is available.
            if(isset($htmlScreenshot)){
                $mpdf->AddPage('L');
                $mpdf->WriteHTML($htmlScreenshot);
            }

            $mpdf->Output($filePath,'F'); 
            $pdfPublicPath = SaveFileHelper::getPublicPath($filePath);

            return response()->
            json(['result' => $pdfPublicPath], 200);

        } catch (Exception $e) {
            return response()->json(
                env('APP_ENV') == 'local' ? $e : ['result' => ['message' => 'Unable to generate PDF.']], 500
              );
        }
    }
}
