<?php

namespace App\Http\Controllers\Api\v1\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\User\AlbumCollection;
use App\Http\Resources\v1\User\AlbumResource;
use App\Models\v1\Album;
use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;

class AlbumController extends Controller
{
    use Meta;

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $albums = $user->albums()->paginate();

        return (new AlbumCollection($albums))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:100'],
            'info' => ['nullable', 'string', 'max:150'],
            'privacy' => ['nullable', 'string', 'max:500'],
            'disclaimer' => ['nullable', 'string', 'max:500'],
        ], [
        ])->validate();

        $user = Auth::user();
        $album = [
            'title' => $request->title,
            'info' => $request->info,
            'privacy' => $request->privacy,
            'disclaimer' => $request->disclaimer,
        ];

        $album = $user->albums()->save(new Album($album));

        return (new AlbumResource($album))->additional([
            'message' => 'You have succesfully created a new album',
            'status' => 'success',
            'status_code' => HttpStatus::CREATED,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();
        $album = $user->albums()->whereId($id)->orWhere('slug', $id)->firstOrFail();

        return (new AlbumResource($album))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
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
        Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:100'],
            'info' => ['nullable', 'string', 'max:150'],
            'privacy' => ['nullable', 'string', 'max:500'],
            'disclaimer' => ['nullable', 'string', 'max:500'],
        ], [
        ])->validate();

        $user = Auth::user();
        $album = $user->albums()->findOrFail($id);
        $album->title = $request->title;
        $album->info = $request->info;
        $album->privacy = $request->privacy;
        $album->disclaimer = $request->disclaimer;

        $album->save();

        return (new AlbumResource($album))->additional([
            'message' => 'Your album was updated succesfully.',
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }


    /**
     * Request for a new album share link
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function requestLink(Request $request, $id, $action = 'create')
    {
        $user = Auth::user();
        $album = $user->albums()->findOrFail($id);
        $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
        $transactions = $album->transactions();

        if ($action === 'create') {
            $reference = config('settings.trx_prefix', 'TRX-') . $this->generate_string(20, 3);
            $due = conf('album_link_price');
            $real_due = round($due * 100, 2);
            $transaction = $transactions->create([
                'user_id' => $user->id,
                'reference' => $reference,
                'amount' => $due,
                'type' => 'album_link',
                'status' => 'pending',
                'description' => 'Album link request',
                'method' => 'Paystack',
                'restricted' => true,
            ]);
            // Dont initialize paystack for inline transaction
            if ($request->inline) {
                $tranx = [
                    'data' => ['reference' => $reference],
                ];
                $real_due = $due;
            } else {
                try {
                    $tranx = $paystack->transaction->initialize([
                        'amount' => $real_due,       // in kobo
                        'email' => $user->email,     // unique to customers
                        'reference' => $reference,   // unique to transactions
                        'callback_url' => $request->get('redirect'),
                    ]);
                } catch (ApiException $e) {
                    return $this->buildResponse([
                        'message' => $e->getMessage(),
                        'status' => 'error',
                        'status_code' => HttpStatus::BAD_REQUEST,
                        'payload' => $e->getResponseObject(),
                    ]);
                }
            }
        } elseif ($action === 'verify') {
            $tranx = $paystack->transaction->verify([
                'reference' => $request->reference,
            ]);
            if ('success' === $tranx->data->status) {
                $transaction = $transactions->where('reference', $request->reference)->where('status', 'pending')->firstOrFail();
                $album = $transaction->transactable;
                $album->share_token = base64url_encode(now()->timestamp . '-' . $album->slug);
                $album->expires_at = now()->addDays(conf('album_link_duration'));
                $album->save();
                $msg = __('A new album sharing link has been created specially for you.');
                $transaction->status = 'completed';
                $transaction->save();
            }
        }

        return (new AlbumResource($album))->additional([
            'message' => $msg ?? HttpStatus::message($action === 'create' ? HttpStatus::ACCEPTED : HttpStatus::OK),
            'status' => 'success',
            'payload' => $tranx ?? new \stdClass(),
            'status_code' => $action === 'create' ? HttpStatus::ACCEPTED : HttpStatus::OK,
            'transaction' => $transaction ?? new \stdClass(),
            'amount' => $real_due ?? 0,
        ])->response()->setStatusCode($action === 'create' ? HttpStatus::ACCEPTED : HttpStatus::OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $item = Album::whereId($item)->first();
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} albums have been deleted.",
                'status' => 'success',
                'status_code' => HttpStatus::ACCEPTED,
            ]);
        } else {
            $item = Album::findOrFail($id);
        }

        $item->delete();

        return $this->buildResponse([
            'message' => "Album \"{$item->title}\" has been deleted.",
            'status' => 'success',
            'status_code' => HttpStatus::ACCEPTED,
        ]);
    }
}