<?php
namespace App\Traits;

use App\Models\Store;

trait ShopifyHelperFunctionsTrait {
    public function getStoreByDomainNameFromDB($shop) {
        return Store::where('myshopify_domain', $shop)->first();
    }
}
?>