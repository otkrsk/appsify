<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\AppInstall;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;

class ShopifyController extends Controller {

  public function install(Request $request) {
    $api_key = getenv('SHOPIFY_APIKEY');
    $scopes = getenv('SHOPIFY_SCOPES');
    $redirect_uri = urlencode(getenv('APP_URL').getenv('SHOPIFY_REDIRECT_URI'));
    $app_install = new AppInstall;
    $nonce = str_random(20);

    $app_install->store = $request->store;
    $app_install->nonce = $nonce;
    $url = "https://{$store}/admin/oauth/authorize?client_id={$api_key}&scope={$scopes}&redirect_uri={$redirect_uri}&state={$nonce}";
    header("Location: {$url}");
  }

  public function auth() {
    $api_key = getenv('SHOPIFY_APIKEY');
    $secret_key = getenv('SHOPIFY_SECRET');
    $request = $_GET;

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

    if($hmac == $calculated_hmac) {
      $client = new Client;

      $response = $client->request(
        'POST',
        "https://{$store}/admin/oauth/access_token",
        [
          'form_params' => [
            'client_id' => $api_key,
            'client_secret' => $secret_key,
            'code' => $query['code']
          ]
        ]
      );
    }

    $data = json_decode($reponse->getBody()->getContents(), true);
    $access_token = $data['access_token'];
    $nonce = $request['state'];

    $app_install = AppInstall::where('store', $store)->where('nonce', $nonce);

    if(count($app_install) > 0) {
      $app_install->update(['access_token' => $access_token]);
    }
  }
}
