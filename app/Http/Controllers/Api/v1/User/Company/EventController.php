<?php

namespace App\Http\Controllers\Api\v1\User\Company;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\EventCollection;
use App\Models\v1\Company;
use App\Traits\Meta;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use Meta;

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Company $company)
    {
        // Get one event per day for the current month or if request has a month param then get events for that month
        $events = $company->events()
            ->whereMonth('start_date', $request->get('month', now()->month))
            ->whereYear('start_date', $request->get('year', now()->year))
            ->get();

        $grouped = collect(new EventCollection($events))
        ->groupBy(function ($event) {
            // dd($event);
            return ($event->start_date ?? $event['start_date'])->format('Y-m-d');
        });
        return $this->buildResponse([
            'data' => $grouped,
            'message' => 'Events retrieved successfully',
            'status' => 'success', 'Events retrieved successfully',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
