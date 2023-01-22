<?php

namespace App\Services;

use App\Models\v1\Album;
use App\Models\v1\Company;
use App\Models\v1\Order;
use App\Models\v1\Transaction;
use App\Models\v1\User;
use App\Models\v1\Wallet;
use Flowframe\Trend\Trend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminStatistics
{
    protected $type;

    protected $company_id;

    protected $user_id;

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $interval
     * @param  \App\Models\v1\Company | \App\Models\v1\User  $owner (Company or User)
     *
     * @return \Illuminate\Support\Collection
     */
    public function build(Request $request, $interval = null, User | Company $owner = null)
    {
        if ($owner instanceof Company) {
            $this->company_id = $owner->id;
        } elseif ($owner instanceof User) {
            $this->user_id = $owner->id;
        }

        // Join the orders() and transactions() results into one collection
        return collect([
            'users' => $this->users($request, $interval),
            'orders' => $this->orders($request, $interval),
            'payouts' => $this->payOuts($request, $interval),
            'commissions' => $this->commissions($request, $interval),
            'transactions' => $this->transactions($request, $interval),
            'verifications' => $this->verifications($request, $interval),
            'albums' => $this->albums($request, $interval),
        ]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function users(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            [
                null => 'role',
                'admin' => 'role',
                'concierge' => 'role',
                'user' =>  function (Builder $query) {
                    $query->where('role', 'user');
                    $query->whereDoesntHave('company');
                },
                'provider' => function (Builder $query) {
                    $query->where('role', 'provider');
                    $query->orWhereHas('company', function (Builder $query) {
                        $query->where('type', 'provider');
                    });
                },
                'vendor' => function (Builder $query) {
                    $query->where('role', 'vendor');
                    $query->orWhereHas('company', function (Builder $query) {
                        $query->where('type', 'vendor');
                    });
                }
            ],
            User::class, null,
            $request->input('duration', 12)
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function orders(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            [null => 'status', 'pending' => 'status', 'completed' => 'status'],
            Order::class,
            null,
            $request->input('duration', 12)
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function transactions(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            [null => 'status', 'pending' => 'status', 'completed' => 'status'],
            Transaction::class,
            null,
            $request->input('duration', 12)
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function verifications(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            [null => 'status', 'pending' => 'status', 'completed' => 'status'],
            Transaction::class,
            Company::class
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function albums(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            ['completed' => 'status'],
            Transaction::class,
            Album::class
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function payOuts(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            ['completed' => function (Builder $query) {
                $query->where('status', 'complete');
                $query->where('type', 'credit');
                $query->where(function (Builder $query) {
                    $query->where('source_type', 'escrow');
                    $query->orWhere('source_type', 'task');
                    $query->orWhere('source', 'Task');
                });
            }],
            Wallet::class,
            null,
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function commissions(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            ['completed' => function (Builder $query) {
                $query->where('status', 'complete');
                $query->where('type', 'debit');
                $query->where('source_type', 'commission');
            }],
            Wallet::class,
            null,
        );
    }

    /**
     * @param  string  $interval (day, week, month, year)
     * @param  array  $scops (null, pending, completed) - null = all
     * @param  string  $model (Order::class, Transaction::class)
     * @param  string  $intermidiate (Company::class)
     * @param  int  $dur (duration in $interval)
     * @return \Illuminate\Support\Collection
     */
    protected function builder(
        string $interval = null,
        array $scopes = [null => 'status', 'pending' => 'status', 'completed' => 'status'],
        string $model = Transaction::class,
        string $intermidiate = null,
        int $dur = null
    ): \Illuminate\Support\Collection {

        // Set the scopes values and key to limit the query

        return collect($scopes)->mapWithKeys(function ($scope, $scopeValue) use ($interval, $intermidiate, $model, $dur) {

            // Set the query based on the intermidiate type
            if ($intermidiate) {
                $query = $scopeValue
                    ? $model::whereTransactableType($intermidiate)->where($scope, is_callable( $scope ) ? null : $scopeValue)
                    : $model::whereTransactableType($intermidiate);
            } else {
                $query = $scopeValue
                    ? $model::where($scope, is_callable( $scope ) ? null : $scopeValue)
                    : $model::query();
            }

            if (isset($this->company_id)) {
                // Filter by company
                $query->byCompany($this->company_id);
            }

            if (isset($this->user_id)) {
                // Filter by user
                $query->whereUserId($this->user_id);
            }

            // Build the data array
            $useMetrics = in_array($model, [Order::class, Transaction::class, Wallet::class]);

            if ($useMetrics) {
                $data = [
                    'total'.($scopeValue ? '_' : '').$scopeValue => $query->sum('amount'),
                    'count'.($scopeValue ? '_' : '').$scopeValue => $query->count(),
                    'count_'.$interval.($scopeValue ? '_' : '').$scopeValue => $query->{'where'.ucfirst($interval)}('created_at', now()->{$interval})->count(),
                    $interval.($scopeValue ? '_' : '').$scopeValue => $query->{'where'.ucfirst($interval)}('created_at', now()->{$interval})->sum('amount'),
                ];
            } else {
                $data = [
                    'count'.($scopeValue ? '_' : '').$scopeValue => $query->count(),
                ];
            }

            // Add the trend data if duration is set
            if ($dur && $useMetrics) {
                if ($intermidiate) {
                    $query2 = $scopeValue
                        ? $model::whereTransactableType($intermidiate)->where($scope, is_callable( $scope ) ? null : $scopeValue)
                        : $model::whereTransactableType($intermidiate);
                } else {
                    $query2 = $scopeValue
                        ? $model::where($scope, is_callable( $scope ) ? null : $scopeValue)
                        : $model::query();
                }

                // Merge the trend data into the data array
                $data = array_merge($data, [
                    'trend'.($scopeValue ? '_' : '').$scopeValue => Trend::query($query2)->between(
                        start: now()->{'startOf'.$interval}()->subMonth($dur - 1),
                        end: now()->{'endOf'.$interval}()
                    )->{'per'.$interval}()->sum('amount')->mapWithKeys((fn ($v) => [$v->date => $v->aggregate])),
                ]);
            }

            // Return the data
            return $data;
        })->sortKeys();
    }
}