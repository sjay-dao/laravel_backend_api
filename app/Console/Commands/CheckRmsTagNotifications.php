<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\RmsTagNotificationMail;

class CheckRmsTagNotifications extends Command
{
    protected $signature = 'rms:check-tag-notifications';

    protected $description = 'Check RMS tags and send notifications';

    public function handle()
    {
        //fetching newly record (Request Service, etc) with OPEN tag and category RMS
       $records = DB::connection('ers')
        ->table('tblformitrequestv3 as a')
        ->leftJoin('tblstatus as t', function ($join) {
            $join->on('a.CurrentStatusId', '=', 't.id')
                ->where('t.formId', 130);
        })
        ->leftJoin('tblcategory as e', 'a.CategoryId', '=', 'e.id')
        ->select('a.id', 'a.Title', 't.status', 't.Tag')
        ->where('a.deletedflag', 0)
        ->where('t.Tag', 'OPEN')
        ->where('e.categoryname', 'RMS')
        ->whereNotIn('t.status', ['RESOLVED'])
        ->get();

        foreach ($records as $record) {

           $exists = DB::table('rms_notification_events')
            ->where('rms_id', $record->id)
            ->where('tag', 'OPEN')
            ->exists();

            if (!$exists) {

                // TODO: Send Email Here

                DB::table('rms_notification_events')->insert([
                    'rms_id' => $record->id,
                    'tag' => $record->Tag,
                    'status' => $record->status,
                    'notified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->info(
                    "Notification saved. RMS ID={$record->id}, Tag={$record->Tag}"
                );
            }
        }

        //sending email notification
        $unsentEvents = DB::table('rms_notification_events')
        ->where('email_sent', 0)
        ->get();

        if ($unsentEvents->isNotEmpty()) {
            try {
                $recordsForEmail = $unsentEvents->map(function ($event) {
                    return [
                        'id' => $event->rms_id,
                        'status' => $event->status ?? '',
                        'tag' => $event->tag ?? '',
                        'created_date' => $event->created_date ?? '',
                    ];
                })->toArray();
                
                Mail::to(config('rms.notifications.to_email'))
                ->send(new RmsTagNotificationMail($recordsForEmail));

                DB::table('rms_notification_events')
                    ->whereIn('id', $unsentEvents->pluck('id'))
                    ->update([
                        'email_sent' => 1,
                        'email_sent_at' => now(),
                        'email_error' => null,
                    ]);

            } catch (\Throwable $e) {
                DB::table('rms_notification_events')
                    ->whereIn('id', $unsentEvents->pluck('id'))
                    ->update([
                        'email_error' => $e->getMessage(),
                    ]);
            }
        }

        return Command::SUCCESS;
    }
}