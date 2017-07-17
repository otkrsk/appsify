<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\AppInstall;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;

class ShopifyController extends Controller {

  /**
   * Function to display the Home page.
   *
   * @param string $string
   * @return array $pieces
   * @author Nicholas Nuing <nicholas@poetfarmer.com>
   */
  public function home() {
    return 'You made it!';
  }

  /**
   * Function to allow user to install the app.
   *
   * @param string $string
   * @return array $pieces
   * @author Nicholas Nuing <nicholas@poetfarmer.com>
   */
  public function install(Request $request) {
    $api_key = getenv('SHOPIFY_APIKEY');
    $scopes = getenv('SHOPIFY_SCOPES');
    $store = $request->shop;
    $nonce = str_random(20);
    // $redirect_uri = urlencode(getenv('APP_URL').getenv('SHOPIFY_REDIRECT_URI'));
    $redirect_uri = 'http://6e214adb.ngrok.io/auth';
    $url = "https://{$store}/admin/oauth/authorize?client_id={$api_key}&scope={$scopes}&redirect_uri={$redirect_uri}&state={$nonce}";

    // check if user has already installed the app
    $app_install = AppInstall::where('store', $store)->first();

    if(count($app_install) > 0) {
      // update the nonce for the store
      $app_install->update(['nonce' => $nonce]);
    } else {
      // add the shop to the db
      $app_install = new AppInstall;
      $app_install->store = $store;
      $app_install->nonce = $nonce;
      $app_install->save();
    }

    // redirect the user
    header("Location: " . $url);
    exit;

  }

  /**
   * Function to authenticate the user.
   *
   * @param string $string
   * @return array $pieces
   * @author Nicholas Nuing <nicholas@poetfarmer.com>
   */
  public function auth() {
    $api_key = getenv('SHOPIFY_APIKEY');
    $secret_key = getenv('SHOPIFY_SECRET');
    $request = $_GET;
    // dd($request);

    $hmac = $request['hmac'];
    unset($request['hmac']);

    $params = [];

    foreach ($request as $key => $value) {
      $params[] = "$key=$value";
    }

    asort($params);
    $params = implode('&', $params);
    $calculated_hmac = hash_hmac('sha256', $params, $secret_key);

    $store = $request['shop'];
    $nonce = $request['state'];

    if($hmac == $calculated_hmac) {
      $client = new GuzzleHttpClient;

      $response = $client->request(
        'POST',
        "https://{$store}/admin/oauth/access_token",
        [
          'form_params' => [
            'client_id' => $api_key,
            'client_secret' => $secret_key,
            'code' => $request['code']
          ]
        ]
      );
    }

    $data = json_decode($response->getBody()->getContents(), true);
    $access_token = $data['access_token'];

    $app_install = AppInstall::where('store', $store)->where('nonce', $nonce)->first();

    if(count($app_install) > 0) {
      // if exists, updated the access token
      $app_install->update(['access_token' => $access_token]);
      // log the user in
      // redirect the user to the home page
      return redirect()->action('ShopifyController@home');
    }
  }

}

/* request that is sent when trying to view app
+request: ParameterBag {#48 ▼
  #parameters: array:3 [▼
    "hmac" => "765cb3c96a744f8ae7154388315b0a1345eed5746965d44eaf847d7860204d7e"
    "shop" => "hugos-mystical-magical-store.myshopify.com"
    "timestamp" => "1500155084"
  ]
}
*/

/*
array:5 [▼
  "code" => "dfd828f93e2a834c6ebde8482ab1adea"
  "hmac" => "4b982079daca941c3936db1940662df1074b06d2215ce5483507c2cb90c8f766"
  "shop" => "hugos-mystical-magical-store.myshopify.com"
  "state" => "RdRlfJoBz1pLywapExMO"
  "timestamp" => "1500155000"
]
*/
