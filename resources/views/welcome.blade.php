<?php
session_start();
// require_once base_path()  . '/src/Facebook/autoload.php'; // download official fb sdk for php @ https://github.com/facebook/php-graph-sdk

$fb = new Facebook\Facebook([
  'app_id' => '589377805260652',
  'app_secret' => '72c3d522f2bba35e63933b9aef7ff59d',
  'default_graph_version' => 'v2.12',
]);

$helper = $fb->getCanvasHelper();

$permissions = ['email']; // optionnal

try {
    if (isset($_SESSION['facebook_access_token'])) {
    $accessToken = $_SESSION['facebook_access_token'];
    } else {
        $accessToken = $helper->getAccessToken();
    }
} catch(Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
 }

if (isset($accessToken)) {

    if (isset($_SESSION['facebook_access_token'])) {
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    } else {
        $_SESSION['facebook_access_token'] = (string) $accessToken;

        // OAuth 2.0 client handler
        $oAuth2Client = $fb->getOAuth2Client();

        // Exchanges a short-lived access token for a long-lived one
        $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['facebook_access_token']);

        $_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;

        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    }

    // validating the access token
    try {
        $request = $fb->get('/me');
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        if ($e->getCode() == 190) {
            unset($_SESSION['facebook_access_token']);
            $helper = $fb->getRedirectLoginHelper();
            $loginUrl = $helper->getLoginUrl('https://apps.facebook.com/APP_NAMESPACE/', $permissions);
            echo "<script>window.top.location.href='".$loginUrl."'</script>";
            exit;
        }
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    // getting basic info about user
    try {
        $profile_request = $fb->get('/me?fields=name,first_name,last_name,email');
        $profile = $profile_request->getGraphNode()->asArray();
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
        unset($_SESSION['facebook_access_token']);
        echo "<script>window.top.location.href='https://apps.facebook.com/APP_NAMESPACE/'</script>";
        exit;
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    // priting basic info about user on the screen
    print_r($profile);

    // Now you can redirect to another page and use the access token from $_SESSION['facebook_access_token']
} else {
    $helper = $fb->getRedirectLoginHelper();
    $loginUrl = $helper->getLoginUrl('https://apps.facebook.com/APP_NAMESPACE/', $permissions);
    echo "<script>window.top.location.href='".$loginUrl."'</script>";
}