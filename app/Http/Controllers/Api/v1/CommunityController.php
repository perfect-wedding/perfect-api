<?php

namespace App\Http\Controllers\Api\v1;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\ContactFormResource;
use App\Http\Resources\v1\MailingListResource;
use App\Models\v1\ContactForm;
use App\Models\v1\MailingList;
use App\Traits\Meta;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    use Meta;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function join(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zip' => 'nullable|string',
            'country' => 'nullable|string',
            'ip' => 'nullable|string',
            'user_agent' => 'nullable|string',
            'referrer' => 'nullable|string',
        ]);

        if (MailingList::where('email', $request->email)->exists()) {
            return $this->buildResponse([
                'status_code' => HttpStatus::CONFLICT,
                'status' => 'error',
                'message' => __('You are already subscribed to our mailing list.'),
            ]);
        }

        $ipInfo = $this->ipInfo();

        $request->merge([
            'name' => str($request->name ?? str($request->email)->before('@'))->replace(['.', '_', '-'], ' ')->title(),
            'city' => $this->ipInfo()['ip'],
            'user_agent' => $request->userAgent(),
            'referrer' => $request->server('HTTP_REFERER'),
            'ip' => $ipInfo['ip'],
            'country' => $ipInfo['country'],
            'state' => $ipInfo['region'],
            'city' => $ipInfo['city'],
        ]);

        $mailingList = MailingList::create($request->all());

        return (new MailingListResource($mailingList))->additional([
            'success' => true,
            'message' => __('Thanks for signing up to our mailing list, you will be notified when we have new updates.'),
            'status_code' => HttpStatus::CREATED,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function feedback(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string',
            'email' => 'required|email',
            'message' => 'required|string|min:10|max:500',
        ]);

        if (ContactForm::where('email', $request->email)->where('message', 'like', $request->message)->exists()) {
            return $this->buildResponse([
                'status_code' => HttpStatus::CONFLICT,
                'status' => 'error',
                'message' => __('You have already submitted this feedback.'),
            ]);
        }

        $ipInfo = $this->ipInfo();

        $request->merge([
            'name' => str($request->name ?? str($request->email)->before('@'))->replace(['.', '_', '-'], ' ')->title(),
            'city' => $this->ipInfo()['ip'],
            'user_agent' => $request->userAgent(),
            'referrer' => $request->server('HTTP_REFERER'),
            'ip' => $ipInfo['ip'],
            'country' => $ipInfo['country'],
            'state' => $ipInfo['region'],
            'city' => $ipInfo['city'],
            'message' => $request->message,
        ]);

        $mailingList = ContactForm::create($request->all());

        return (new ContactFormResource($mailingList))->additional([
            'success' => true,
            'message' => __('Thanks for your feedback, we will get back to you shortly.'),
            'status_code' => HttpStatus::CREATED,
        ]);
    }
}
