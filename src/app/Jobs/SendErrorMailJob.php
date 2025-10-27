<?php

namespace App\Jobs;

use App\Enums\AppEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendErrorMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $message;
    protected array $context;

    public function __construct(string $message, array $context = [])
    {
        $this->message = $message;
        $this->context = $context;
    }

    public function handle(): void
    {
        $facilityName = auth()->user()->facility->name ?? 'N/A';
        $errorTime = now()->format(AppEnum::DATETIME_FORMAT);

        $emailContent = "Facility Name: {$facilityName}\n";
        $emailContent .= "Error Time: {$errorTime}\n\n";
        $emailContent .= "Error Details:\n{$this->message}\n\n";
        $emailContent .= "Context:\n" . print_r($this->context, true);

        Mail::raw($emailContent, function ($message) {
            $message->to(config('const.email_send_error_log'))
                ->subject(config('const.title_mail_error_log'));
        });
    }
}
