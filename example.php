<?php 
require './src/SimpleGoogleOAuth.php';

// Client Id & Client Secret from Google's developer Console
$client_id     = 'xxxxx';
$client_secret = 'xxxxxxxx';
$redirect_uri  = "http://example.com/example.php";


$client = new SimpleGoogleOAuth($client_id,$client_secret);

// We set additional required attributes available at Google developer docs here
// To provide multiple attributes to a single 'name', seperate them using space, like this
// $client->setAttr('name', 'value1 value2')

$client->setAttr('redirect_uri', $redirect_uri); // as registered at dev console
$client->setAttr('scope', "https://www.googleapis.com/auth/drive.readonly https://www.googleapis.com/auth/drive.apps.readonly");
$client->setAttr('access_type', 'offline'); // and so on


if (!isset($_GET['code']))
{
    // Create auth url
    $auth_url = $client->createAuthUrl();
    
    // For web apps, we redirect user here
    header('Location: ' . $auth_url);
    die("<a href='$auth_url'>Click here.</a>");
} else {

    // Exchange authorization code for an access token.
    $access_token = $client->createAccessToken($_GET['code']);

    // To save this as JSON, call  getAccessToken()
    $json_access_token = $client->getAccessToken();
    // and save to file or database
    file_put_contents("user_access_token.secret", $json_access_token);


    // Output
    echo $json_access_token;

    // That's it!

    // If you want to refresh this saved access token after expiration
    // You could either pass JSON string or Array to setAccessToken() method, like this
    // Note: Token Can be refreshed after expiry only if you set access_type to offline.
    
    $tok = file_get_contents("user_access_token.secret");
    $client->setAccessToken($tok);

    //Checkk if token expired
    if ( $client->isAccessTokenExpired() ) {
        //  generate new token
        $access_token = $client->refreshToken();// throws an Exception, if refresh_token is not available
    }

}


?>