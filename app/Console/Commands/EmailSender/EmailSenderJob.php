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
        print("start scheduler kirim email. \r\n");
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
                    $emailContent = str_replace('{{imgBase64}}', base64_encode(file_get_contents(public_path('image/mbaku_header.png'))), $emailObject->emailContent);
                    print("ID : " . $emailObject->emailId . " sent email to " . $emailObject->emailDest . " on " . $emailObject->emailSentDt . " in progress..." . "\r\n");
                    Mail::send([], [], function ($message) use ($emailObject, $emailContent) {
                        $message->subject($emailObject->emailTitle);
                        $message->from(env('MAIL_USERNAME', 'noreply@mbaku.online'), 'MBAKU Administrator (noreply)');
                        $message->to($emailObject->emailDest);
                        $message->setBody($emailContent, 'text/html');
                    });
                    $emailObject->emailSentDt = Carbon::now()->toDateTimeString();
                    print("ID : " . $emailObject->emailId . " sent email to " . $emailObject->emailDest . " is success on " . $emailObject->emailSentDt . "\r\n");
                    //$emailSenderFcd->doMoveToSuccess($emailObject);
                } catch (Throwable $e) {
                    $emailObject->emailSentDt = Carbon::now()->toDateTimeString();
                    print("ID : " . $emailObject->emailId . " sent email to " . $emailObject->emailDest . " failed with error : " . $e->getMessage() . "\r\n");
                    $emailObject->response = $e->getMessage();
                    $emailSenderFcd->doRollBackById($emailObject);
                }
            }
        }
        print("end scheduler kirim email. \r\n");
    }
}
