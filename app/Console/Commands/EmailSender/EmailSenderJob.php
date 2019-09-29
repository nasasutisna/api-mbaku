<?php

namespace App\Console\Commands\EmailSender;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailSenderJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:sender';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a email queueing to all user.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    private $isFirstTime = true;

    public function handle()
    {
        $this->doSchedule();
        /* $index = 0;
        $isLooping = true;
        while ($isLooping) {

            // biar ngeloop setiap satu detik
            sleep(0.1);

            // schedulernya akan ngeloop sebanyak 600 kali (60 detik)
            if ($index > 600) {
                $isLooping = false;
            } else {
                $index++;
            }
        } */
    }

    private function doSchedule()
    {
        print($this->isFirstTime . "\n\r");
        $bookingId = Carbon::now()->timestamp;
        $emailSenderFcd = new EmailSenderFacade($bookingId);
        if ($this->isFirstTime) {
            $emailSenderFcd->doRollback();
            $this->isFirstTime = false;
        }

        // update all data to booking;
        $emailSenderFcd->doBookingEmailQueue();

        // get all data booking
        $list = $emailSenderFcd->getAllEmailQueue();
        foreach ($list as $emailObject) {
            if ($emailObject) {
                $emailObject->emailSentDt = Carbon::now()->toDateTimeString();
                try {
                    print("ID : " . $emailObject->emailId . " sent email to " . $emailObject->emailDest . " on " . $emailObject->emailSentDt . " in progress..." . "\r\n");
                    Mail::send([], [], function ($message) use ($emailObject) {
                        // prepare attachment file
                        $files = [];
                        if (!empty($emailObject->attachment1)) array_push($files, storage_path($emailObject->attachment1));
                        if (!empty($emailObject->attachment2)) array_push($files, storage_path($emailObject->attachment2));

                        // prepare sent email message object
                        $message->subject($emailObject->emailTitle);
                        $message->from(env('MAIL_USERNAME', 'noreply@mbaku.online'), 'MBAKU Administrator (noreply)');
                        $message->to($emailObject->emailDest);
                        $message->setBody($emailObject->emailContent, 'text/html');
                        foreach ($files as $file) {
                            $message->attach($file);
                        }
                    });
                    $emailObject->emailSentDt = Carbon::now()->toDateTimeString();
                    print("ID : " . $emailObject->emailId . " sent email to " . $emailObject->emailDest . " is success on " . $emailObject->emailSentDt . "\r\n");
                    $emailSenderFcd->doMoveToSuccess($emailObject);
                } catch (Throwable $e) {
                    $emailObject->emailSentDt = Carbon::now()->toDateTimeString();
                    print("ID : " . $emailObject->emailId . " sent email to " . $emailObject->emailDest . " failed with error : " . $e->getMessage() . "\r\n");
                    $emailObject->response = $e->getMessage();
                    $emailSenderFcd->doRollBackById($emailObject);
                }
            }
        }
    }
}
