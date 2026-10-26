<!doctype html>
<html lang="{{ config('app.locale') }}">
    <head>
        <meta charset="utf-8">
        
        <meta name="author" content="SEXTAN">

        <link href="{{url('/')}}/favicon.png" rel="shortcut icon">

        <!--WEBAPP CONFIG-->
        <meta name=”robots” content=”noindex”>
        <link rel="manifest" href="{{url('/')}}/manifest.json">
        <link rel="icon" type="image/png" sizes="172x172" href="{{url('/')}}/apple-touch-icon.png">
        <link rel="apple-touch-icon" type="image/png" sizes="172x172" href="{{url('/')}}/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="144x144" href="{{url('/')}}/apple-touch-icon-144.png">
        <link rel="apple-touch-icon" type="image/png" sizes="144x144" href="{{url('/')}}/apple-touch-icon-144.png">
        <meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="nido ERP">
        <meta name="format-detection" content="telephone=yes">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no, viewport-fit=cover" />
        <style>html { -webkit-text-size-adjust: 100%; }</style>
        <meta name="application-name" content="nido ERP">
        <meta name="theme-color" content="#fff">
        <meta name="msapplication-navbutton-color" content="#fff">
        <meta name="msapplication-starturl" content="/">


        <meta name="description" content="SEXTAN FOR NON PROFIT">
        <title>{{ config('app.name') }}</title>
        <link href="{{ mix('/css/app.css') }}" rel="stylesheet">
        <link href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap.min.css" rel="stylesheet">
        
        <script>
            window.Laravel = {!! json_encode([
                'csrfToken' => csrf_token(),
                'siteName'  => config('app.name'),
                'apiDomain' => config('app.url').'/api',
                'appDomain' => config('app.url'),
                'pusherKey' => config('broadcasting.connections.pusher.key'),
                'pusherCluster' => config('broadcasting.connections.pusher.options.cluster')
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
        <style>
           .all-wrapper.with-pattern {
                background-image: url("/images/bg_pattern.png");
            }
           .onboarding-modal .onboarding-side-by-side .onboarding-content.with-gradient {                
                background-image: url("/images/onboarding-gradient-ver.png");
           }

            .el-select-dropdown{
                /* never wider than the screen: on phones the options ran past its edge */
                max-width: min(700px, calc(100vw - 16px))!important;
            }

            body {
                zoom: .9;
            }
        </style>
    </head>
    <audio style="display:none" id="soundNotification">
        <source src="{{url('/')}}/sounds/noty.mp3" type='audio/ogg' />
        <source src="{{url('/')}}/sounds/noty.mp3" type='audio/mp3' />
    </audio>
    <body class="menu-position-side menu-side-left full-screen">          
        <div id="app">
            <app></app>
        </div>
        <script src="{{ mix('/js/app.js') }}"></script>
    </body>
</html>
