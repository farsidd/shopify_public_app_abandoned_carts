<?php

return [
    'shopify_client_key' => env('SHOPIFY_CLIENT_ID', '95233af81e15fc6ca04b6f6eb383bc69'),
    'shopify_client_secret' => env('SHOPIFY_CLIENT_SECRET', '2992f2ccb6b5e1d7cd7b7aaa1f683a5e'),
    'shopify_api_version' => '2023-07',
    'api_scopes' => 'write_orders,write_checkouts,write_customers,write_fulfillments,read_locations,write_products,unauthenticated_read_checkouts'
];