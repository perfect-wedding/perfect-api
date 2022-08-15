<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
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

        .loginform input[type="submit"] {
            background: #4CAF50;
            color: #fff;
            font-size: 20px;
            padding: 7px 15px;
            border-radius: 20px;
            transition: 0.4s;
            border: none;
        }

        .loginform input[type="submit"]:hover {
            cursor: pointer;
            background: #1f5822;
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

        /*-- Responsive CSS --*/
        @media(max-width: 576px) {
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

<body>
    <div class="loginform">
        <!-- Avatar Image -->
        <div class="avatar">
            <img src="{{ asset('media/default_avatar.png') }}" alt="Avatar">
        </div>
        <h2>Login</h2>
        <!-- Start Form -->
        <form action="{{ url('login') }}" method="POST">
            @csrf
            @if ($errors)
                <div class="errors">{{ $errors->first() }}</div>
            @endif
            <p>Username</p>
            <input type="text" name="email" placeholder="Email Address" value="{{ old('email') }}">
            <p>Password</p>
            <input type="password" name="password" placeholder="Enter Password">
            <input type="submit" name="login-btn" value="Login">
        </form>
        <div style="text-align: center; margin-top: 1em;">This page does not give you access to
            {{ config('settings.site_name') }}, but <a
                href="{{ env('FRONTEND_LINK', 'http://localhost:8080') . '/login' }}">this</a> does!
        </div>
    </div>
</body>

</html>
