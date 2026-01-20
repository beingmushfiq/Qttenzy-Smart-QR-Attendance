<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Qttenzy Backend</title>
        <style>
            body {
                font-family: 'Nunito', sans-serif;
                background-color: #1a202c;
                color: #fff;
                height: 100vh;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .content {
                text-align: center;
            }
            .title {
                font-size: 84px;
                font-weight: 200;
                margin-bottom: 30px;
                background: linear-gradient(to right, #6366f1, #a855f7, #ec4899);
                background-clip: text;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .links > a {
                color: #fff;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }
            .status {
                margin-top: 20px;
                padding: 10px 20px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 8px;
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <div class="content">
            <div class="title">
                Qttenzy
            </div>

            <div class="links">
                <a href="/api/documentation">Docs</a>
                <a href="https://laravel.com/docs">Laravel</a>
                <a href="https://github.com/laravel/laravel">GitHub</a>
            </div>

            <div class="status">
                Backend Service is Running 🚀
            </div>
        </div>
    </body>
</html>
