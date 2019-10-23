<?php
require __DIR__ . '/vendor/autoload.php';
use Google\Auth\Credentials\UserRefreshCredentials;
use Google\Photos\Library\V1\PhotosLibraryClient;


try {
    function connectWithGooglePhotos(array $scopes, $redirectURI)
    {
        $clientSecretJson = json_decode(
            file_get_contents(__DIR__  . '/client_secret_1022843461226-judpl0tob9r5v4c98f13rqe1mtlf003f.apps.googleusercontent.com.json'),
            true
        );
        $clientId = $clientSecretJson['client_id'];
        $clientSecret = $clientSecretJson['client_secret'];
        $oauth2 = new \Google\Auth\OAuth2([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'authorizationUri' => 'https://accounts.google.com/o/oauth2/v2/auth',
            // Where to return the user to if they accept your request to access their account.
            // You must authorize this URI in the Google API Console.
            'redirectUri' => 'http://localhost',
            'tokenCredentialUri' => 'https://www.googleapis.com/oauth2/v4/token',
            'scope' => $scopes,
        ]);

        // With the code returned by the OAuth flow, we can retrieve the refresh token.
        if (file_exists(__DIR__ . '/refresh_token') === false) {
            $authenticationUrl = $oauth2->buildFullAuthorizationUri(['access_type' => 'offline']);
            print($authenticationUrl . PHP_EOL);
            $code = readline('Enter auth code:');

            $oauth2->setCode($code);
            $authToken = $oauth2->fetchAuthToken();
            file_put_contents(__DIR__ . '/refresh_token', $authToken['access_token']);
        }
        $refreshToken = file_get_contents(__DIR__ . '/refresh_token');

        // The UserRefreshCredentials will use the refresh token to 'refresh' the credentials when
        // they expire.
        return new UserRefreshCredentials(
            $scopes,
            [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken
            ]
        );
    }

    // Use the OAuth flow provided by the Google API Client Auth library
    // to authenticate users. See the file /src/common/common.php in the samples for a complete
    // authentication example.

    $authCredentials = connectWithGooglePhotos(['https://www.googleapis.com/auth/photoslibrary'], require __DIR__ . '/credentials.php');

    // Set up the Photos Library Client that interacts with the API
    $photosLibraryClient = new PhotosLibraryClient(['credentials' => $authCredentials]);

    $albums = $photosLibraryClient->listAlbums();
    /*
     * @var $album Album
     **/
    foreach ($albums as $album) {
        var_dump($album);
    }
exit;
    $items = $photosLibraryClient->listMediaItems();
    echo PHP_EOL . 'Counting...';
    $count = 0;
    foreach ($items->iterateAllElements() as $item) {
        $count++;
    }
    echo $count . ' items';

} catch (\Google\ApiCore\ApiException $exception) {
    // Error during album creation
    echo $exception->getTraceAsString();
} catch (\Google\ApiCore\ValidationException $exception) {
    // Error during client creation
    echo $exception->getTraceAsString();
}
