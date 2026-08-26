<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\FbrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SubmitInvoiceToFbr implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(public string $invoiceId)
    {
    }

    public function handle(): void
    {
        $invoice = Invoice::find($this->invoiceId);
        if (!$invoice) return;

        FbrService::submitInvoice($invoice);
    }
}
