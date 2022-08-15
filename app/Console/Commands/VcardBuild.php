<?php

namespace App\Console\Commands;

use App\Models\v1\Plan;
use App\Models\v1\User;
use App\Models\v1\Vcard;
use App\Traits\Meta;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VcardBuild extends Command
{
    use Meta;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vcard:build
                           {price=0.00 : Will this vcard be for free?}
                           {subscription? : Which subscribers can download, Leave empty for free access?}
                           {channel_id? : Channels help group vcards according to user interests?}
                           {rating=5 : This determines the face value of this card, Maximum should be five?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Vcards';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $subscription = $this->argument('subscription');
        $price = (float) $this->argument('price');
        $rating = $this->argument('rating');
        $rating = $rating > 5 ? 5 : ($rating < 1 ? 1 : $rating);

        $vcard = [
            'subscription' => $subscription,
            'price' => $price,
            'title' => config('settings.vcf_prefix').Carbon::now()->format('d-m-Y-s').rand(10, 99),
            'slug' => $this->generate_string(35),
            'rating' => $rating,
            'group' => Carbon::now()->format('dmY'),
        ];

        $plan = Plan::where('slug', $subscription)->first('max_contacts');
        $limit = $plan->max_contacts ?? 0;
        /**
         * Strict mode prevent the Vcard Engine from generating Vcards with repeated content
         */
        $filter = [];
        if (($strict_mode = config('settings.strict_mode', false)) !== false && $strict_mode !== 0) {
            $filterParams = Vcard::get('content');
            if (in_array($strict_mode, [true, 1])) {
                $filterParams->where('subscription', $subscription);
            }
            if (in_array($strict_mode, [true, 2])) {
                $filterParams->where('rating', $rating);
            }
            if (in_array($strict_mode, [true, 3])) {
                $filterParams->where('price', $price);
            }
            $filter = $filterParams->mapWithKeys(function ($value, $key) {
                return [$key => collect($value->content)->flatten()];
                dd(collect($value->content)->flatten());
            })->unique()->flatten();
        }

        // Oly get users whose info is not empty
        $getUsers = User::whereNotIn('id', $filter)->has('userInfo');
        if ($limit > 0) {
            $getUsers->limit($limit);
        }
        $vcard['content'] = $getUsers->get('id')->mapWithKeys(function ($user, $key) {
            return [$key => ['user_id' => $user->id]];
        });

        Vcard::create($vcard);

        $this->info('Vcard built!');

        return 0;
    }
}
