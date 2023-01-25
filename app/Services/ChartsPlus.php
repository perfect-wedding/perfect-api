<?php

namespace App\Services;

use App\Models\v1\Company;
use App\Models\v1\Order;
use App\Models\v1\Transaction;
use Flowframe\Trend\Trend;
use Illuminate\Http\Request;

class ChartsPlus
{
    protected $type;

    /**
     * Generate the transaction and order charts dataset for echarts
     * Flowframe\Trend\Trend is used to generate the data
     *
     * @link https://echarts.apache.org/en/option.html#dataset
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company  $company
     * @return \Illuminate\Support\Collection
     */
    public function transactionAndOrderCharts(Request $request, Company $company, $type = null)
    {
        // Add the last 6 months to the dimensions array
        $orders = Trend::query(Order::completed()->byCompany($company->id))
            ->between(now()->startOfMonth()->subMonths(6), now())
            ->perMonth()->sum('amount')->mapWithKeys(function ($item) {
                return [$item->date => $item->aggregate];
            });

        // $transactions = Trend::query(Transaction::status('completed')->belongsToCompany($company->id))
        //     ->between(now()->startOfMonth()->subMonths(6), now())
        //     ->perMonth()->sum('amount')->mapWithKeys(function ($item) {
        //         return [$item->date => $item->aggregate];
        //     });

        $dataset = collect([
            'legend' => $orders->keys(),
            'dimensions' => ['Type', ...$orders->keys()],
            'source' => [
                ['Type' => 'Sales', ...$orders],
                // ['Type' => 'Transactions', ...$transactions],
            ],
        ]);



        return [
            'chart' => $dataset,
        ];
    }

    /**
     * Generate the admin transaction and order charts dataset for echarts
     * Flowframe\Trend\Trend is used to generate the data
     *
     * @link https://echarts.apache.org/en/option.html#dataset
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public function adminTransactionAndOrderCharts(Request $request, $type = null)
    {
        // Add the last 6 months to the dimensions array
        $orders = Trend::query(Order::completed())
            ->between(now()->startOfMonth()->subMonths(6), now())
            ->perMonth()->sum('amount')->mapWithKeys(function ($item) {
                return [$item->date => $item->aggregate];
            });

        $transactions = Trend::query(Transaction::status('completed'))
            ->between(now()->startOfMonth()->subMonths(6), now())
            ->perMonth()->sum('amount')->mapWithKeys(function ($item) {
                return [$item->date => $item->aggregate];
            });

        // dd($orders);

        $dataset = collect([
            'legend' => $transactions->keys(),
            'dimensions' => ['Type', ...$transactions->keys()],
            'source' => [
                ['Type' => 'Sales', ...$orders],
                ['Type' => 'Transactions', ...$transactions],
            ],
        ]);

        return [
            'chart' => $dataset,
        ];
    }
}
