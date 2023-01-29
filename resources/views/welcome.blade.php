<!DOCTYPE html>
<html>
<head>
    <title>Swftea - Welcome</title>
</head>
<style>
    body, html {
        height: 100%;
        margin: 0;
    }

    .bgimg {
        background-image: url('https://cleanfax.com/wp-content/uploads/2018/04/trends-in-cleaning-and-restoration-technology-banner-image.jpg');
        height: 100%;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        color: white;
        font-family: "Courier New", Courier, monospace;
        font-size: 25px;
    }

    .topleft {
        position: absolute;
        top: 0;
        left: 16px;
    }

    .bottomleft {
        position: absolute;
        bottom: 0;
        left: 16px;
    }

    .middle {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }

    hr {
        margin: auto;
        width: 40%;
    }
</style>
<body>
<?php

//date_default_timezone_set('Asia/Kathmandu');
$date = new DateTime();
$timeZone = $date->getTimezone();
$zone = $timeZone->getName();
$now = time(); // or your date as well
$your_date = strtotime("2020-07-02");
$datediff = $your_date - $now;

?>

<div class="bgimg">
    <div class="topleft">
{{--        <p>Logo</p>--}}
    </div>
    <div class="middle">
        <h1 style="color: #fff">We are upgrading...</h1>
        <hr>
        <p style="color: #fff; background-color: #0a1520; min-height: 50">September 1, 2020</p>
    </div>
    <div class="bottomleft">
        <p>Sharing worldwide fun, treasure and entertainment</p>
    </div>
</div>

</body>
</html>
