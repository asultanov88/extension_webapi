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
        /**
         * NOTE: cron job command for hawkhost (change as needed):
         * /usr/local/bin/php /home/evendora/extension-service-api/artisan schedule:run
         */
        
        // Deletes all processed and more than 1 hour old temporary attachments.
        $schedule->call(function () {
            $publcPath = env('APP_ENV') == 'local' ? public_path().'/' : env('APP_PUBLIC_PATH');
            $tempAttachments = TempAttachment::all();
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

        })->daily()->timezone('America/New_York');	

        // Deletes all generated PDF and related screenshot files.
        $schedule->call(function () {
            // Pulls the public path from the .env file. Replace on the server as needed.
            $publcPath = env('APP_ENV') == 'local' ? public_path().'/' : env('APP_PUBLIC_PATH');
            $pdfDirectory = $publcPath.'media-repository/PDF/*';

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

        })->daily()->timezone('America/New_York');
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
