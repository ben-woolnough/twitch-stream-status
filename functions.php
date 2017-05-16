<?php
class Channel
{
    public $id;
    public $name;
    public $status;
    public $logo;
    public $url;
    public $isLive = False;

    public $game = NULL;
    public $viewers = NULL;
    public $preview = NULL;
}

function getOutputFromUrl($clientID, $url)
{
    $ch = curl_init();

    // cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Accept: application/vnd.twitchtv.v5+json",
        "Client-ID: $clientID"
    ));

    $output = curl_exec($ch);

    if ($output === FALSE) {
        echo "cURL Error: " . curl_error($ch);
    }

    curl_close($ch);

    return $output;
}

function getIdFromUsername($clientID, $username)
{
    $url = "https://api.twitch.tv/kraken/users?login=$username";
    $output = getOutputFromUrl($clientID, $url);
    $json = json_decode($output, true);
    return $json["users"][0]["_id"];
}

function userFollows($clientID, $userID)
{
    // Takes a user ID and returns up to 25 followed channels
    $url = "https://api.twitch.tv/kraken/users/$userID/follows/channels";
    $output = getOutputFromUrl($clientID, $url);
    $json = json_decode($output, true);

    // Create an array of Channel objects with data from the json file
    $channelArray = array();
    for ($channel = 0; $channel < count($json["follows"]); $channel++) {
        array_push($channelArray, new Channel());
        $channelArray[$channel]->id = $json["follows"][$channel]["channel"]["_id"];
        $channelArray[$channel]->name = $json["follows"][$channel]["channel"]["display_name"];
        $channelArray[$channel]->status = $json["follows"][$channel]["channel"]["status"];
        $channelArray[$channel]->logo = $json["follows"][$channel]["channel"]["logo"];
        $channelArray[$channel]->url = $json["follows"][$channel]["channel"]["url"];
    }

    return $channelArray;
}

function streamStatus($clientID, $channelArray)
{
    // Create an array of channel IDs from the object array
    // and convert to a comma separated string to be passed to the URL
    $channelIDs = array();
    foreach ($channelArray as $channel) {
        array_push($channelIDs, $channel->id);
    }
    $IDString = join(",", $channelIDs);

    $url = "https://api.twitch.tv/kraken/streams/?channel=$IDString";
    $output = getOutputFromUrl($clientID, $url);
    $json = json_decode($output, true);

    // Get stream info for live channels and pass to objects in array
    for ($stream = 0; $stream < count($json["streams"]); $stream++) {
        foreach ($channelArray as $channel) {
            if ($channel->id == $json["streams"][$stream]["channel"]["_id"]) {
                $channel->isLive = True;
                $channel->game = $json["streams"][$stream]["game"];
                $channel->viewers = $json["streams"][$stream]["viewers"];
                $channel->preview = $json["streams"][$stream]["preview"]["medium"];
            }
        }
    }

    return $channelArray;
}

function activeHTML($name, $status, $logo, $url, $game, $viewers, $preview) {
    // HTML template for active channels (currently streaming)
    return '
<div class="container active">
    <div class="logo">
        <a href="'.$url.'">
            <img height="90" width="90" src="'.$logo.'">
        </a>
    </div>
    <div class="preview">
        <a href="'.$url.'"><img height="90" src="'.$preview.'"></a>
    </div>
    <div class="text">
        <h1><a href="'.$url.'">'.$name.'</a></h1>
        <p>Now playing: '.$game.'</p>
        <p>'.$status.'</p>
        <p>'.$viewers.' viewers</p>
    </div>
</div>';
}

function inactiveHTML($name, $status, $logo, $url) {
    // HTML template for inactive channels
    return '
<div class="container inactive">
    <div class="logo">
        <a href="'.$url.'">
            <img height="90" width="90" src="'.$logo.'">
        </a>
    </div>
    <div class="text">
        <h1><a href="'.$url.'">'.$name.'</a></h1>
        <p>'.$status.'</p>
    </div>
</div>';
}

?>