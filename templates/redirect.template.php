<!DOCTYPE html>
<html>
<head>
    <title><?php echo t('Redirecting to Twitter...'); ?></title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%; 
            text-align: center;
            font-family: 'Arial', 'sans-serif';
            font-size: 16px;
            color: #666;
            background-color: #eee;
        }

        img {
            background-color: #fff;
            padding: 10px;
            border: 1px solid #ccc;
            max-width: 200px;
        }

        #authorizing h1 {
            font-size: 26px;
            margin-bottom: 25px;
        }

        .outer {
            width: 100%;
            height: 95%;
            display: table;
            margin: 0;
            padding: 0;
        }

        #message {
            display: table-cell;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class='outer'>
        <div id="message">
            <img src="{{ logo_url }}" alt="Twitter Feed">
            <h1><?php echo t('Standby, sending you to twitter...'); ?></h1>
            <p><?php echo t('If you are not automatically redirected to twitter, <a href="{{ url }}">click here</a>.'); ?></p>
        </div>
    </div>
    <script>
        window.TwitterFeedOAuthToken = "{{ token }}";

        window.onload = function () {
            setTimeout(function () { window.location = '{{ url }}'; }, 2000);
        };
    </script>
</body>
</html>