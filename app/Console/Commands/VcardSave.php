<?php

namespace App\Console\Commands;

use App\Models\v1\Vcard;
use App\Services\Media;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VcardSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vcard:save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save all available vcards to storage and delete old expired ones';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // $knownDate = Carbon::now()->addDays(11);
        // Carbon::setTestNow($knownDate);
        $do = Vcard::get()->map(function ($vcard) {
            if ($vcard->created_at->addDays(config('settings.vcard_lifespan'))->isPast()) {
                // Delete expired vcards
                $vcard->count_saves = 0;
                $vcard->count_saved = 0;
                $file_name = 'vcards/'.$vcard->title.'.vcf';
                Storage::delete($file_name);
            // $vcard->delete();
            } else {
                $vcard->count_saves = ($vcard->count_saves ? $vcard->count_saves : 0) + 1;
                $vcard->count_saved = (new Media)->buildVcard($vcard);
            }

            return $vcard;
        });
        $count = $do->sum('count_saves');
        $saves = $do->sum('count_saved');
        $this->info("$count Vcards loaded with $saves contacts!");

        return 0;
    }
}
