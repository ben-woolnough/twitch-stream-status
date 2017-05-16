<!DOCTYPE html>
<html>
<head>
    <title>Twitch Stream Status</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<h1 class="heading">Twitch Stream Status</h1>
<form id="form" action="index.php" method="get">
    <input type="text" name="username" placeholder="Enter your Twitch username...">
    <input type="submit" value="Go">
</form>

<?php

if (isset($_GET["username"])) {

    $clientID = "y996r5q7jlafff9mo8e93beazm9dy7";

    require 'functions.php';

    // Get user ID from username
    $userID = getIdFromUsername($clientID, $_GET["username"]);

    // Get array containing objects of channels followed by user
    $channelArray = userFollows($clientID, $userID);

    // Add live stream data to channel objects, return updated object array
    $channelArray = streamStatus($clientID, $channelArray);

    // Sort the array by live channels first
    for ($i = 0; $i < count($channelArray); $i++) {
        if ($channelArray[$i]->isLive) {
            $channelArray = array($i => $channelArray[$i]) + $channelArray;
        }
    }

    // Generate HTML based on object properties
    foreach ($channelArray as $c) {
        if ($c->isLive) {
            echo activeHTML($c->name, $c->status, $c->logo, $c->url, $c->game, $c->viewers, $c->preview);
        } else {
            echo inactiveHTML($c->name, $c->status, $c->logo, $c->url);
        }
    }

}

?>

</body>
</html>