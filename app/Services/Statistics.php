<?php

namespace App\Services;

use App\Models\v1\Company;
use App\Models\v1\Order;
use App\Models\v1\Transaction;
use Flowframe\Trend\Trend;
use Illuminate\Http\Request;

class Statistics
{
    protected $type;

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company  $company
     * @return \Illuminate\Support\Collection
     */
    public function build(Request $request, Company $company, $type = null)
    {
        // Join the orders() and transactions() results into one collection
        return collect([
            'orders' => $this->orders($request, $company, $type),
            'transactions' => $this->transactions($request, $company, $type),
        ]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company  $company
     * @return array
     */
    protected function orders(Request $request, Company $company, $type = null)
    {
        $queryAll = Order::byCompany($company->id);
        $queryPending = Order::byCompany($company->id)->pending();
        $queryCompleted = Order::byCompany($company->id)->completed();

        $XqueryAll = Order::byCompany($company->id);
        $XqueryPending = Order::byCompany($company->id)->pending();
        $XqueryCompleted = Order::byCompany($company->id)->completed();

        $YqueryAll = Order::byCompany($company->id);
        $YqueryPending = Order::byCompany($company->id)->pending();
        $YqueryCompleted = Order::byCompany($company->id)->completed();

        $type = $type ?? str($request->input('type', 'month'))->ucfirst()->camel()->toString();

        return $this->builder(
            $request,
            [$queryAll, $XqueryAll, $YqueryAll],
            [$queryPending, $XqueryPending, $YqueryPending],
            [$queryCompleted, $XqueryCompleted, $YqueryCompleted],
            $type
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company  $company
     * @return array
     */
    protected function transactions(Request $request, Company $company, $type = null)
    {
        $queryAll = Transaction::byCompany($company->id);
        $queryPending = Transaction::byCompany($company->id)->status('pending');
        $queryCompleted = Transaction::byCompany($company->id)->status('completed');

        $XqueryAll = Transaction::byCompany($company->id);
        $XqueryPending = Transaction::byCompany($company->id)->status('pending');
        $XqueryCompleted = Transaction::byCompany($company->id)->status('completed');

        $YqueryAll = Transaction::byCompany($company->id);
        $YqueryPending = Transaction::byCompany($company->id)->status('pending');
        $YqueryCompleted = Transaction::byCompany($company->id)->status('completed');

        $type = $type ?? str($request->input('type', 'month'))->ucfirst()->camel()->toString();

        return $this->builder(
            $request,
            [$queryAll, $XqueryAll, $YqueryAll],
            [$queryPending, $XqueryPending, $YqueryPending],
            [$queryCompleted, $XqueryCompleted, $YqueryCompleted],
            $type
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $queryAll
     * @param  array  $queryPending
     * @param  array  $queryCompleted
     * @param $type
     * @return array
     */
    protected function builder(Request $request, $queryAll, $queryPending, $queryCompleted, $type)
    {
        $order_trend = Trend::query($queryAll[0])->between(
            start: now()->{'startOf'.$type}()->subMonth($request->input('duration', 12) - 1),
            end: now()->{'endOf'.$type}()
        )->{'per'.$type}()->sum('amount');

        $order_trend_count = Trend::query($queryAll[2])->between(
            start: now()->{'startOf'.$type}()->subMonth($request->input('duration', 12) - 1),
            end: now()->{'endOf'.$type}()
        )->{'per'.$type}()->count('id');

        $order_trend_pending = Trend::query($queryPending[0])->between(
            start: now()->{'startOf'.$type}()->subMonth($request->input('duration', 12) - 1),
            end: now()->{'endOf'.$type}(),
        )->{'per'.$type}()->sum('amount');

        $order_trend_pending_count = Trend::query($queryPending[2])->between(
            start: now()->{'startOf'.$type}()->subMonth($request->input('duration', 12) - 1),
            end: now()->{'endOf'.$type}(),
        )->{'per'.$type}()->count('id');

        $order_trend_completed = Trend::query($queryCompleted[0])->between(
            start: now()->{'startOf'.$type}()->subMonth($request->input('duration', 12) - 1),
            end: now()->{'endOf'.$type}(),
        )->{'per'.$type}()->sum('amount');

        $order_trend_completed_count = Trend::query($queryCompleted[2])->between(
            start: now()->{'startOf'.$type}()->subMonth($request->input('duration', 12) - 1),
            end: now()->{'endOf'.$type}(),
        )->{'per'.$type}()->count('id');

        return [
            'total' => $queryAll[1]->sum('amount'),
            'total_pending' => $queryPending[1]->sum('amount'),
            'total_completed' => $queryCompleted[1]->sum('amount'),
            'count' => $queryAll[1]->count(),
            'count_pending' => $queryPending[1]->count(),
            'count_completed' => $queryCompleted[1]->count(),
            'count_monthly' => collect($order_trend_count->last())->get('aggregate'),
            'count_monthly_pending' => collect($order_trend_pending_count->last())->get('aggregate'),
            'count_monthly_completed' => collect($order_trend_completed_count->last())->get('aggregate'),
            'monthly' => collect($order_trend->last())->get('aggregate'),
            'monthly_pending' => collect($order_trend_pending->last())->get('aggregate'),
            'monthly_completed' => collect($order_trend_completed->last())->get('aggregate'),
            'trend' => $order_trend,
            'trend_pending' => $order_trend_pending,
            'trend_completed' => $order_trend_completed,
        ];
    }

    protected function all()
    {
        $this->type = 'all';
    }

    protected function pending()
    {
        $this->type = 'pending';
    }

    protected function completed()
    {
        $this->type = 'completed';
    }
}
