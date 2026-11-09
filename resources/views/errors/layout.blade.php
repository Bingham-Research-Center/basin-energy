<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('code') - @yield('title') | BasinEnergy</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: Arial, Helvetica, sans-serif;
            color: #ffffff;
            background-color: #0f2439;
            background-image:
                linear-gradient(
                    rgba(15, 36, 57, 0.82),
                    rgba(15, 36, 57, 0.88)
                ),
                url("/frontend/images/bg/slider-1.jpg");
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            text-align: center;
        }

        main {
            width: 100%;
            max-width: 750px;
            padding: 40px 20px;
        }

        .brand {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 45px;
        }

        .error-code {
            font-size: clamp(90px, 18vw, 160px);
            font-weight: 800;
            line-height: 1;
            margin-bottom: 20px;
        }

        h1 {
            font-size: clamp(28px, 5vw, 42px);
            font-weight: 700;
            margin: 0 0 20px;
        }

        p {
            font-size: 18px;
            line-height: 1.8;
            color: #e5e9ed;
            max-width: 600px;
            margin: 0 auto 35px;
        }

        .home-button {
            display: inline-block;
            padding: 16px 32px;
            background: #0f2439;
            color: #ffffff;
            border: 2px solid #ffffff;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: background 0.2s ease;
        }

        .home-button:hover {
            background: #111111;
        }

        .home-button:focus-visible {
            outline: 3px solid #ffffff;
            outline-offset: 4px;
        }

        @media (max-width: 480px) {
            .brand {
                font-size: 23px;
                margin-bottom: 35px;
            }

            main {
                padding: 20px 10px;
            }

            p {
                font-size: 16px;
            }

            .home-button {
                padding: 14px 24px;
            }
        }
    </style>
</head>

<body>
    <main>
        <div class="brand">BasinEnergy</div>

        <div class="error-code">@yield('code')</div>

        <h1>@yield('title')</h1>

        <p>@yield('message')</p>

        <a class="home-button" href="/">
            Return to Homepage
        </a>
    </main>
</body>
</html>
