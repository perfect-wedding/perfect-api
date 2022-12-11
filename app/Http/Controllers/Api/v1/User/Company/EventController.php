<?php

namespace App\Http\Controllers\Api\v1\User\Company;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\EventCollection;
use App\Http\Resources\v1\User\EventResource;
use App\Http\Resources\v1\User\UserResource;
use App\Models\v1\Company;
use App\Models\v1\User;
use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    use Meta;

    /**
     * Display a listing of the resource.
     *
     * @param  int $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $id)
    {
        $type = $request->type ?? 'company';
        if ($type === 'origin') {
            $query = User::findOrFail($id)->events();
            $type = 'user';
        } elseif ($type === 'concierge' || $type === 'user') {
            $query = User::findOrFail($id)->userEvents();
            $type = 'user';
        } else {
            $query = Company::findOrFail($id)->events();
        }

        if ($type !== 'user' && (!$request->has('field') || !in_array($request->get('field'), ['start_date', 'end_date']))) {
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
        $events = $events->map(function ($event) {
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
                $newEvent->end_date = $event->end_date->addDays($i);
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

        if ($request->has('unique')) {
            $events = $events->unique($request->input('unique'));
        }

        // dd($events);
        return $this->buildResponse([
            'data' => $events,
            'message' => 'Events retrieved successfully',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Models\v1\Company $company
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id)
    {
        if ($request->type === 'concierge' || $request->type === 'user') {
            $query = User::findOrFail($id)->userEvents();
        } else {
            $query = Company::findOrFail($id)->events();
        }

        $request->validate([
            'title' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'all_day' => 'nullable|boolean',
            'color' => 'nullable|string',
        ]);

        $event = $query->create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'details' => $request->details,
            'start_date' => $request->input('start_date', now()),
            'end_date' => $request->input('end_date', now()->addDays(1)),
            'duration' => Carbon::parse($request->input('start_date', now()))
                ->diffInMinutes(Carbon::parse($request->input('end_date', now()->addDays(1)))),
            'color' => $request->input('color', '#480d19'),
            'bgcolor' => $request->input('bgcolor', '#e8e7e7'),
            'border_color' => $request->input('border_color', '#480d19'),
            'location' => $request->location,
            'meta' => ['editable' => true],
            'notify' => boolval($request->input('notify', false)),
        ]);

        return (new EventResource($event))->additional([
            'message' => 'Event Created successfully',
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\v1\Company $company
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $company, $id)
    {
        if ($request->type === 'concierge' || $request->type === 'user') {
            $query = User::findOrFail($company)->userEvents();
        } else {
            $query = Company::findOrFail($company)->events();
        }

        $event = $query->findOrFail($id);
        $this->authorize('be-owner', $event);

        return (new EventResource($event))->additional([
            'message' => 'Event retrieved successfully',
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company $company
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $company, $id)
    {
        if ($request->type === 'concierge' || $request->type === 'user') {
            $query = User::findOrFail($company)->userEvents();
        } else {
            $query = Company::findOrFail($company)->events();
        }

        $event = $query->findOrFail($id);
        $this->authorize('be-owner', [$event]);

        $request->validate([
            'title' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'all_day' => 'nullable|boolean',
            'color' => 'nullable|string',
        ]);

        $data = [
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'details' => $request->details,
            'start_date' => $request->input('start_date', now()),
            'end_date' => $request->input('end_date', now()->addDays(1)),
            'duration' => Carbon::parse($request->input('start_date', now()))
                ->diffInMinutes(Carbon::parse($request->input('end_date', now()->addDays(1)))),
            'bgcolor' => $request->input('bgcolor', '#3a87ad'),
            'location' => $request->location,
            'notify' => boolval($request->input('notify', false)),
        ];

        $event->update($data);

        return (new EventResource($event))->additional([
            'message' => 'Event updated successfully',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
    /**
     * Delete the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company $company
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Company $company, $id)
    {
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) use ($company) {
                $item = $company->events()->whereNull('eventable_id')->find($item);
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} events have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = $company->events()->whereNull('eventable_id')->findOrFail($id);
        }

        $item->delete();

        return $this->buildResponse([
            'message' => "Event \"{$item->title}\" has been deleted.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}