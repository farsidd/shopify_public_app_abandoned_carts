<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Traits\ShopifyApiCallsRequestTrait;
use App\Traits\ShopifyHelperFunctionsTrait;
use Exception;

class AbandonedCartController extends Controller
{
    use ShopifyApiCallsRequestTrait;

    public function index()
    {
        try {
            $storeDetails = Auth::user()->store;
            $endpoint = getShopifyURLForStore('checkouts.json', $storeDetails);
            $headers = getShopifyHeadersForStore($storeDetails);

            $response = $this->makeAnApiCallToShopify('GET', $endpoint, null, $headers, null);
            if ($response['statusCode'] == 200) {
                $body = $response['body'];
                if (!is_array($body)) {
                    $body = json_decode($body, true);
                    // return $body['checkouts'] ?? null;
                }
            } else {
                Log::info('Error while getting abandoned checkouts object from shopify via Rest API');
                Log::info($response);
                return null;
            }
        } catch (Exception $e) {
            Log::info('Error while getting abandoned checkouts object from shopify via Rest API');
            Log::info($e->getMessage() . " " . $e->getLine());
        }
        return Inertia::render('AbandonedCarts', [
            'abandoned_checkouts' => $body['checkouts']
        ]);
    }
}
