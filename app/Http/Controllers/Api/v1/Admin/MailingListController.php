<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\MailingListCollection;
use App\Http\Resources\v1\MailingListResource;
use App\Http\Resources\v1\NewsletterResource;
use App\Jobs\ProcessNewsletterSending;
use App\Models\v1\MailingList;
use App\Models\v1\NewsLetter;
use Illuminate\Http\Request;

class MailingListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('can-do', ['anything']);
        $limit = $request->get('limit', 30);

        $query = MailingList::query();

        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->get('search')}%")
                ->orWhere('email', 'like', "%{$request->get('search')}%")
                ->orWhere('ip', 'like', "%{$request->get('search')}%")
                ->orWhere('city', 'like', "%{$request->get('search')}%");
        }

        // Reorder Columns
        if ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir == 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        $list = $query->paginate($limit);

        return (new MailingListCollection($list))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
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
    public function show(MailingList $member)
    {
        return (new MailingListResource($member))->additional([
            'success' => true,
            'message' => HttpStatus::message(HttpStatus::OK),
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Send a newsletter to the specified resources.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function sendNewsletter(Request $request)
    {
        $this->validate($request, [
            'ids' => 'required|array',
            'subject' => 'nullable|string',
            'message' => 'required|string',
        ]);

        $feedbacks = MailingList::whereIn('id', $request->ids)->get();

        $newsletter = NewsLetter::create([
            'sender_id' => auth()->id(),
            'subject' => $request->subject,
            'message' => $request->message,
            'type' => 'mailing_list',
            'status' => 'pending',
            'recipients' => $feedbacks->pluck('email'),
        ]);

        ProcessNewsletterSending::dispatch($feedbacks, $newsletter);

        return (new NewsletterResource($newsletter))->additional([
            'message' => __('Newsletter has been queued for sending to :0 recipient(s).', [$feedbacks->count()]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        $this->authorize('can-do', ['anything']);
        if ($request->items) {
            $items = collect($request->items)->map(function ($item) use ($request) {
                $item = MailingList::whereId($item)->first();
                if ($item) {
                    $delete = $item->delete();

                    return count($request->items) === 1 ? $item->id : $delete;
                }

                return false;
            })->filter(fn ($i) => $i !== false);

            return $this->buildResponse([
                'message' => $items->count() === 1
                    ? __('Mailing list entry #:0 has been deleted', [$items->first()])
                    : __(':0 mailing list entry have been deleted.', [$items->count()]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = MailingList::findOrFail($id);
            $item->delete();

            return $this->buildResponse([
                'message' => __('Mailing list entry #:0 has been deleted.', [$item->id]),
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        }
    }
}
