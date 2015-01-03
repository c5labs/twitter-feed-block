<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo t('Authorization Error'); ?></title>
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
            padding: 30px;
        }

        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class='outer'>
        <div id="message">
            <img src="{{ logo_url }}" alt="Twitter Feed">
            <h1><?php echo t('Oh No! We had a problem.'); ?></h1>
            <p><?php echo t('It looks like something went wrong, the error returned was:'); ?></p>
            <p class="error">{{ error }}</p>
            <p><?php echo t('Waiting a little while and trying again <i>may</i> fix the problem, we\'ve logged more information to the system error log.'); ?></p>
        </div>
    </div>
</body>
</html>