<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Custom\SaveFileHelper;
use App\Models\TempAttachment;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Deletes all processed and more than 1 hour old temporary attachments.
        $schedule->call(function () {
            $publcPath = public_path().'/';
            $tempAttachments = TempAttachment::truncate();
            // All records now - 1 hour are deleted.
            $timeStampNow = Carbon::now()->addHours(-1);

            foreach ($tempAttachments as $attachment) {
                $created_at = Carbon::parse($attachment['created_at']);
                if($attachment['isPermanent'] == 1 || $created_at->lt($timeStampNow)){
                    if(SaveFileHelper::checkFileExists($publcPath.$attachment['tempPath'])){
                        if(SaveFileHelper::deleteFile($publcPath.$attachment['tempPath'])){
                            $attachment->delete();
                        }
                    }
                }
            }

        })->everyMinute()->timezone('America/New_York');	

        // Deletes all generated PDF and related screenshot files.
        $schedule->call(function () {
            $publcPath = public_path().'/';
            $pdfDirectory = $publcPath.'media-repository/PDF/*';
            error_log($pdfDirectory);

            $pdfFiles = glob($pdfDirectory);
            foreach($pdfFiles as $file){
                if(is_file($file)) {
                    unlink($file);
                }
            }

            $pdfDirectory = $publcPath.'media-repository/PDF/screenshots/*';
            $screenshotFiles = glob($pdfDirectory);
            foreach($screenshotFiles as $file){
                if(is_file($file)) {
                    unlink($file);
                }
            }

        })->everyMinute()->timezone('America/New_York');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
