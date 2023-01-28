<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Services\AdminStatistics;
use App\Services\ChartsPlus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function testService(Request $request, $service = 'mail')
    {
        $this->authorize('can-do', ['configuration']);

        $message = 'Service not found.';
        $status = HttpStatus::NOT_FOUND;

        if ($service === 'mail') {
            $this->validate($request, [
                'email' => 'required|email',
            ]);
            Mail::raw('It Works!', function ($message) use ($request) {
                $message
                  ->to($request->input('email'))
                  ->subject('Test Mail');
              });
            $message = 'Test mail sent successfully.';
            $status = HttpStatus::OK;
        } elseif ($service === 'sms') {
            $phone_val = stripos($request->phone, '+') !== false ? 'phone:AUTO,NG' : 'phone:'.$this->ipInfo('country');
            $this->validate($request, [
                'phone' => 'required|'.$phone_val,
            ]);
            (new \Twilio\Rest\Client(
                config('twilio-notification-channel.account_sid'),
                config('twilio-notification-channel.auth_token')
            ))->messages->create( $request->input('phone'), [
                  'from' => config('twilio-notification-channel.from'),
                  'body' => 'It Works!'
                ]
              );
            $message = 'Test sms sent successfully.';
            $status = HttpStatus::OK;
        } elseif ($service === 'push') {
            broadcast(new \App\Events\SendingNotification([
                'id' => null,
                'data' => [
                    'type' => 'system_notification',
                    'title' => 'Test Push',
                    'message' => 'It Works!',
                    'image' => 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y',
                ],
                'message' => 'It Works!',
                'image' => 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y',
                'created_at' => now(),
                'read_at' => null,
                'type' => 'system_notification',
            ], auth()->user()));
            $message = 'Test push sent successfully.';
            $status = HttpStatus::OK;
        }

        return $this->buildResponse([
            'data' => [],
            'message' => $message,
            'status' => $status === HttpStatus::OK ? 'success' : 'error',
            'status_code' => $status,
        ]);
    }

    public function loadChartPlus(Request $request)
    {
        $this->authorize('can-do', ['dashboard']);

        $type = str($request->input('type', 'month'))->ucfirst()->camel()->toString();
        $data = (new ChartsPlus)->adminTransactionAndOrderCharts($request, $type);

        return $this->buildResponse([
            'data' => $data,
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ], [
            'type' => $type,
            'duration' => $request->input('duration', 12),
        ]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadStats(Request $request)
    {
        $this->authorize('can-do', ['dashboard']);

        $interval = str($request->input('type', 'month'))->ucfirst()->camel()->toString();
        $data = (new AdminStatistics)->build($request, $interval);

        return $this->buildResponse([
            'data' => $data,
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ], [
            'type' => $interval,
            'duration' => $request->input('duration', 12),
        ]);
    }
}
