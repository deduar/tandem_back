<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Twitt;
use Illuminate\Http\Request;


class TwittController extends Controller
{
    /* Get number of twitts by hashtag.
    *
    * @return \Illuminate\Http\Response
    */
    public function getTwitts()
    {
        // $twitts = Twitt::all();
        $twitts = queryTwitter('farina');
        return response()->json([
            // "success" => true,
            // "message" => "Twitts List Here !!!",
            "data" => $twitts
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $twitts = Twitt::all();
        return response()->json([
            "success" => true,
            "message" => "Product List",
            "data" => $twitts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        // $validator = Validator::make($input, [
        //     'twittID' => 'required',
        //     'text' => 'required'
        // ]);
        // if ($validator->fails()) {
        //     return $this->sendError('Validation Error.', $validator->errors());
        // }
        $twitts = Twitt::create($input);
        return response()->json([
            "success" => true,
            "message" => "Twit created successfully.",
            "data" => $twitts
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Twitt $twitts)
    {
        $twitts->delete();
        return response()->json([
            "success" => true,
            "message" => "Twitt deleted successfully.",
            "data" => $twitts
        ]);
    }
}


function queryTwitter($search)
{
    $url = "https://api.twitter.com/1.1/search/tweets.json";
    if ($search != "")
        $search = "#" . $search;
    $query = array('count' => env('NUM_TWITTS'), 'q' => urlencode($search), "result_type" => "recent");
    $oauth_access_token = env('ACCESS_TOKEN');
    $oauth_access_token_secret = env('ACCESS_TOKEN_SECRET');
    $consumer_key = env('CUSTOMER_KEY');
    $consumer_secret = env('CUSTOMER_SECRET');

    $oauth = array(
        'oauth_consumer_key' => $consumer_key,
        'oauth_nonce' => time(),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_token' => $oauth_access_token,
        'oauth_timestamp' => time(),
        'oauth_version' => '1.0'
    );

    $base_params = empty($query) ? $oauth : array_merge($query, $oauth);
    $base_info = buildBaseString($url, 'GET', $base_params);
    $url = empty($query) ? $url : $url . "?" . http_build_query($query);

    $composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
    $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
    $oauth['oauth_signature'] = $oauth_signature;

    $header = array(buildAuthorizationHeader($oauth), 'Expect:');
    $options = array(
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_HEADER => false,
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    );

    $feed = curl_init();
    curl_setopt_array($feed, $options);
    $json = curl_exec($feed);
    curl_close($feed);
    return  json_decode($json);
}

function buildBaseString($baseURI, $method, $params)
{
    $r = array();
    ksort($params);
    foreach ($params as $key => $value) {
        $r[] = "$key=" . rawurlencode($value);
    }
    return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
}

function buildAuthorizationHeader($oauth)
{
    $r = 'Authorization: OAuth ';
    $values = array();
    foreach ($oauth as $key => $value)
        $values[] = "$key=\"" . rawurlencode($value) . "\"";
    $r .= implode(', ', $values);
    return $r;
}
