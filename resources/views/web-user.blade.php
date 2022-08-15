@php
if (isset($user)):
    extract($user->toArray());
    $signatures = collect(Storage::allFiles('backup'))
        ->filter(fn($f) => Str::contains($f, '.sql'))
        ->map(
            fn($f) => Str::of($f)
                ->substr(0, -4)
                ->replace(['backup', '/-'], ''),
        )
        ->sortDesc()
        ->values()
        ->all();
    $secure_files = collect(Storage::allFiles('secure'))
        ->filter(fn($f) => Str::contains($f, '.zip'))
        ->sortDesc()
        ->values()
        ->all();
    $signatures = $action !== 'download' ? $signatures : $secure_files;
    $action = $action ?? null;
endif;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <title>{{ $action ?? 'Management Console' }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: arial;
        }

        .errors {
            text-align: center;
            color: #fc361c;
            margin: 5px;
        }

        .messages {
            text-align: center;
            color: #07a202;
            margin: 5px;
        }

        .code {
            color: #1ea912;
            margin: 5px;
        }

        .code-holder {
            height: 200px;
            overflow-y: scroll;
            margin: 2rem 0 2rem 0;
            padding: 1rem;
            background-color: #000;
        }

        .loginform {
            background: #ffffff;
            width: 450px;
            color: #4caf50;
            top: 50%;
            left: 50%;
            position: absolute;
            transform: translate(-50%, -50%);
            box-sizing: border-box;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0px 10px 29px 0px #e0e0e0;
        }

        .loginform p {
            margin: 0;
            padding: 0;
            font-weight: bold;
        }

        .loginform h2 {
            font-size: 30px;
            margin: 30px 0px;
            text-transform: uppercase;
            font-weight: normal;
            text-align: center;
        }

        .loginform input {
            width: 100%;
            margin-bottom: 30px;

        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto;
            position: absolute;
            top: -55px;
            left: 0px;
            right: 0px;
            border: 6px solid #e6e6e6;
        }

        .avatar img {
            width: 100%;
            height: auto;
        }

        .loginform input[type="text"],
        input[type="password"] {
            border: none;
            border-bottom: 1px solid #1e5220;
            background: transparent;
            outline: none;
            height: 40px;
            color: #333;
            font-size: 16px;
        }

        .loginform input[type="submit"],
        .btn {
            background: #4CAF50;
            color: #fff !important;
            font-size: 20px !important;
            padding: 7px 15px !important;
            border-radius: 20px;
            transition: 0.4s;
            border: none;
        }

        .loginform input[type="submit"]:hover,
        .btn:hover {
            cursor: pointer;
            background: #1f5822;
        }

        .btn {
            width: 100%;
            appearance: auto;
            user-select: none;
            white-space: pre;
            align-items: flex-start;
            text-align: center;
            cursor: default;
            box-sizing: border-box;
        }

        .loginform a {
            text-decoration: none;
            font-size: 15px;
            line-height: 20px;
            color: #1e5220;
        }

        .loginform .have-not {
            float: right;
        }

        .loginform a:hover {
            color: #4caf50;
        }

        .m-5 {
            margin: 15px;
        }

        select {
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #c7c7c7;
            margin: 10px;
            box-shadow: 1px 3px 2px 0px rgb(0 0 0 / 9%);
        }

        .button {
            background: #4CAF50;
            color: #fff !important;
            font-size: 20px !important;
            padding: 7px 15px !important;
            border-radius: 10px;
            transition: 0.4s;
            border: none;
        }

        .flex {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /*-- Responsive CSS --*/
        @@media(max-width: 576px) {
            .loginform {
                width: 90%;
            }

            .loginform a {
                display: block;
                text-align: center;
                float: inherit !important;
                margin: 10px 0px;
            }
        }

    </style>
</head>

<body x-data>
    <div class="loginform">
        <!-- Avatar Image -->
        <div class="avatar">
            <img src="{{ $image_url }}" alt="Avatar">
        </div>
        <h2>{{ $fullname }}</h2>

        @if ($errors)
            <div class="errors m-5">{{ $errors->first() }}</div>
        @endif
        @isset($messages)
            <div class="messages m-5">{{ $messages->first() }}</div>
        @endisset
        @isset($code)
            <div class="code-holder">
                <code class="code m-5">{!! $code->first() !!}</code>
            </div>
        @endisset
        @isset($choose)
            <h4 style="margin: 0 auto 0 auto; color: rgb(21, 157, 230);">Choose Backup Signature</h4>
        @else
            <h4 style="margin: 0 auto 0 auto; color: rgb(21, 157, 230);">Run Command</h4>
        @endisset
        <div class="flex">
            <select x-ref="artisan" @input="$refs.artisan_run.setAttribute('href', $el.value)" id="artisan">
                @if ($action === 'choose' || $action === 'download')
                    <option value="" readonly>Choose {{ $action ? 'Signature' : 'Action' }}</option>
                    @foreach ($signatures as $signature)
                        <option
                            value="{{ url(($action === 'choose' ? 'artisan/system:reset -r -s ' : 'downloads/') . $signature) }}">
                            {{ $signature }}
                        </option>
                    @endforeach
                    <option value="{{ url('artisan/list') }}">Go Back</option>
                @else
                    <option value="{{ url('artisan/list') }}">Help and Info</option>
                    <option value="{{ url('artisan/dispatch') }}">Manual Dispatch</option>
                    <option value="{{ url('artisan/storage:link') }}">Sym Link Storage</option>
                    <option value="{{ url('artisan/migrate') }}">Migrate Database</option>
                    <option value="{{ url('artisan/optimize:clear') }}">Clear Cache</option>
                    <option value="{{ url('artisan/transactions -h') }}">Transactions Help</option>
                    <option value="{{ url('artisan/transactions abandoned -a clear,-s paystack,-p 50,-r') }}">Clear
                        Abandoned/Failed Transactions
                    </option>
                    <option value="{{ url('artisan/system:reset backup') }}">System Backup</option>
                    <option value="{{ url('artisan/system:reset -h') }}">System Reset Help</option>
                    <option value="{{ url('artisan/system:reset -b') }}">System Reset (Backup)</option>
                    <option value="{{ url('artisan/system:reset') }}">System Reset (No Backup)</option>
                    <option value="{{ url('artisan/system:reset -r') }}">System Reset (Restore Latest Backup)
                    </option>
                    <option value="{{ url('artisan/system:reset restore') }}">System Restore (Latest Backup)</option>
                    <option value="{{ url('artisan/backup/action/choose') }}">System Restore (Choose Backup)</option>
                    <option value="{{ url('artisan/backup/action/download') }}">Download Backups</option>
                @endif
            </select>
            <div>
                <a x-ref="artisan_run" href="{{ url('artisan/list') }}"
                    class="button artisan">{{ $action === 'download' ? 'Select' : 'Run' }}</a>
            </div>
        </div>

        <form action="{{ url('logout') }}" method="POST">
            @csrf
            <input type="submit" name="login-btn" value="Logout">
        </form>

        <div style="text-align: center; margin-top: 1em;">This page does not give you access to
            {{ config('settings.site_name') }}, but <a
                href="{{ config('settings.frontend_link', env('FRONTEND_LINK', 'http://localhost:8080')) . '/login' }}">this</a>
            does!
        </div>
    </div>
    <script>
        let artisan = document.querySelector('select#artisan');
        document.addEventListener('alpine:initialized', () => {})
    </script>
</body>

</html>
