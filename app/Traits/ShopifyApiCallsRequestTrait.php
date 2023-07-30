<?php

namespace App\Traits;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

trait ShopifyApiCallsRequestTrait {
    //for simple get reqeust to shopify api we only need $url, $headers & $method
    //other parameters like $url_params & $reqeust_body that's only for POST request
    public function makeAnApiCallToShopify($method = 'GET', $endpoint, $url_params = null, $headers, $request_body = null) {
        //header will include
        //Content-Type: application/json
        //X-Shopify-Access-Token: value
        try {
        //here we will do things according to parameters which passed to function if its for GET request we will do
        //something accordingly
        //we will use switch methods to distinguish betweeen what we have to do
        $client = new Client();
        $response = null;
        switch($method) {
            case 'GET' : $response = $client->request($method, $endpoint, ['headers' => $headers]); break;
            case 'POST' : $response = $client->request($method, $endpoint,['headers' => $headers,'json' => $request_body]); break;
        }
        return [
            'statusCode' => $response->getStatusCode(),
            'body' => $response->getBody()
        ];

        }
        catch(Exception $e) {
            return [
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage(),
                'body' => null
            ];
        }
    }

    //This is is the post request that we use to get access token only.
    //we have sent client_id, client_secret & code in the request body as json array
    function makeAPOSTCallToShopify($requestBody, $endpoint, $headers) {
        // Initialize cURL
        $ch = curl_init($endpoint);
    
        // Convert array to JSON
        $jsonRequestBody = $requestBody;
    
    // Set options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequestBody);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute cURL and get the response
    $response = curl_exec($ch);

    // Get the HTTP status code
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check if the request has any errors
    if (curl_error($ch)) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }

    // Close the cURL session
    curl_close($ch);

    // Return the response and status code
    return [
        'statusCode' => $httpcode,
        'body' => $response
    ];
    }
}