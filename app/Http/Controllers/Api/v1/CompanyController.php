<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\Business\CompanyCollection;
use App\Http\Resources\v1\Business\CompanyResource;
use App\Http\Resources\v1\User\EventCollection;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        if ($company->status !== 'verified' && $company->verification->status !== 'verified') {
            return $this->buildResponse([
                'message' => 'Company not found',
                'status' => 'error',
                'status_code' => HttpStatus::NOT_FOUND,
            ]);
        }

        return (new CompanyResource($company))->additional([
            'message' => 'OK',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the featured companies.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Category  $company
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function featured(Request $request)
    {
        $limit = $request->input('limit', 15);

        $companies = Company::where('featured_to', '>=', Carbon::now())->inRandomOrder()->limit($limit)->get();
        if ($companies->isEmpty()) {
            $companies = Company::inRandomOrder()->limit($limit)->get();
        }

        return (new CompanyCollection($companies))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the company events.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Category  $company
     * @return \Illuminate\Http\Response
     */
    public function events(Request $request, Company $company)
    {
        $query = $company->events();

        if (!$request->has('field') || !in_array($request->get('field'), ['start_date', 'end_date'])) {
            // Filter all events that are not owned by the user
            $query->where(function($query) use ($request) {
                $query->where('user_id', $request->user()->id);
                $query->orWhereHas('company', function($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                    $query->orWhere('id', $request->user()->company_id);
                });
            });
        }

        if ($request->has('meta') && is_array($request->get('meta'))) {
            $key = key($request->get('meta'));
            $query->where('meta->' . $key, $request->get('meta')[$key]);
        }

        $events = $query->whereMonth('start_date', $request->get('month', now()->month))
            ->whereYear('start_date', $request->get('year', now()->year))
            ->get();

        // If the start date and the end dates span more than one day then we need to create a new event for each day
        $events = $events->map(function ($event) use ($request) {
            $event->start_date = Carbon::parse($event->start_date);
            $event->end_date = Carbon::parse($event->end_date);
            $days = $event->start_date->diffInDays($event->end_date);

            $events = collect();
            for ($i = 0; $i <= $days; $i++) {
                $newEvent = $event->replicate();
                $newEvent->id = $event->id;
                $newEvent->created_at = $event->created_at;
                $newEvent->updated_at = $event->updated_at;
                $newEvent->start_date = $event->start_date->addDays($i);
                $newEvent->end_date = $event->start_date->addDays($i);
                $events->push($newEvent);
                // If event has no color then generate a random color #hex
                if (!$newEvent->color) {
                    $newEvent->color = '#' . substr(md5(rand()), 0, 6);
                }
                // If event duration is more than one day then set the duration to 1 day
                if ($days > 1) {
                    $newEvent->duration = 60 * 24;
                }
            }
            return $events;
        })->flatten();

        if ($request->has('field')) {
            // Return only the selected field
            $events = in_array($request->input('field'), ['start_date', 'end_date', 'user'])
                ? $events->map(function ($event) use ($request) {
                    if ($request->input('field') === 'user') {
                        $event->user->event_id = $event->id;
                        return (new UserResource($event->user));
                    }
                    return $event->only($request->input('field'));
                  })
                : collect([]);

                if ($request->input('field') === 'user') {
                    $events = $events->unique('id')->filter(fn($id)=>$id !== auth()->id());
                }

                $events = $events->flatten();
        } else {
            $events = collect(new EventCollection($events));

            if ($request->input('group-by') === 'date') {
                $format = $request->input('format', 'Y-m-d');
                $events = $events->groupBy(function ($event) use ($format) {
                    // dd($event);
                    return ($event->start_date ?? $event['start_date'])->format($format);
                });
            }
        }

        // dd($events);
        return $this->buildResponse([
            'data' => $events,
            'message' => 'Events retrieved successfully',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }
}