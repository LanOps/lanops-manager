<?php

namespace App\Console\Commands;

use App\Models\EventParticipant;

use Illuminate\Console\Command;

class RegenerateTicketQrCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:regenerate-qr {--event= : Only regenerate QR codes for this event ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate the QR code image for every event participant';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $query = EventParticipant::query();
        if ($this->option('event')) {
            $query->where('event_id', $this->option('event'));
        }

        $failed = 0;
        $bar = $this->output->createProgressBar($query->count());
        $query->chunkById(100, function ($participants) use (&$failed, $bar) {
            foreach ($participants as $participant) {
                if (!$participant->generateQRCode()) {
                    $failed++;
                    $this->newLine();
                    $this->error('Failed to regenerate QR code for participant ' . $participant->id);
                }
                $bar->advance();
            }
        });
        $bar->finish();
        $this->newLine();

        if ($failed > 0) {
            $this->error($failed . ' QR code(s) failed to regenerate');
            return self::FAILURE;
        }
        $this->info('QR codes regenerated');
        return self::SUCCESS;
    }
}
