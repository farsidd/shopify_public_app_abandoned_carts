<?php

namespace App\Http\Controllers;

use App\Mail\NewUserUponAppInstall;
use App\Models\Store;
use App\Models\User;
use App\Traits\ShopifyApiCallsRequestTrait;
use App\Traits\ShopifyHelperFunctionsTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class InstallationController extends Controller
{
    use ShopifyHelperFunctionsTrait;
    use ShopifyApiCallsRequestTrait;

    // There will be 3 scenerios that can happen

    //1: new fresh installation of the app
    //2: app already isntalled and access token is valid till now so we will open the app by sending dummy https request to fetch shop
    //3: app already installed but access token expired we will re-install the app again

    public function startInstallation(Request $request)
    {
        try {
            $validRequest = $this->validateRequestFromShopify($request->all());
            if ($validRequest) {
                $shop = $request->has('shop');
                if ($shop) {
                    $storeDetails = $this->getStoreByDomainNameFromDB($request->shop);
                    if ($storeDetails !== NULL && $storeDetails !== false) {
                        //store record already exisit in the database now we will whether the access token is valid for
                        //for that store or not. if not then we move user to re-installation process
                        //if access token is valid then we will redirect user to login page
                        $validAccessToken = $this->checkAcessTokenIsValid($storeDetails);
                        if ($validAccessToken) {
                            //token is valid for shopify api calls we will re-direct user to login apge
                            print_r('app already installed & token is also valid for api calls');
                            exit();
                        } else {
                            //here the case can be either user has unintalled the app or access token is invalid
                            //token is not valid we will move the user to re-installation work flow
                            //we will update the record of the store like access_token only which already in the database
                            Log::info('Re-Installation for shop: ' . " " . $request->shop);
                            $endpoint = 'https://' . $request->shop . '/admin/oauth/authorize?client_id=' . config('custom.shopify_client_key') . '&scope=' . config('custom.api_scopes') . '&redirect_uri=' . config('app.ngrok_url') . 'shopify/auth/redirect';
                            return Redirect::to($endpoint);
                        }
                    } else {
                        //here is the fresh installation of the app work flow
                        // https://{shop}.myshopify.com/admin/oauth/authorize?client_id={client_id}&scope={scopes}&redirect_uri={redirect_uri}&state={nonce}&grant_options[]={access_mode}
                        Log::info('New Installation for shop: ' . " " . $request->shop);
                        $endpoint = 'https://' . $request->shop . '/admin/oauth/authorize?client_id=' . config('custom.shopify_client_key') . '&scope=' . config('custom.api_scopes') . '&redirect_uri=' . config('app.ngrok_url') . 'shopify/auth/redirect';
                        return Redirect::to($endpoint);
                        // return route($endpoint);
                    }
                } else {
                    throw new Exception('Shop patameter is not present in the request');
                }
            } else {
                throw new Exception('Request is not valid');
            }
        } catch (Exception $e) {
            Log::info($e->getMessage() . ' ' . $e->getLine());
            dd($e->getMessage());
        }
    }
    private function validateRequestFromShopify($request)
    {
        try {
            $arr = [];
            $hmac = $request['hmac'];
            unset($request['hmac']);

            foreach ($request as $key => $value) {

                $key = str_replace("%", "%25", $key);
                $key = str_replace("&", "%26", $key);
                $key = str_replace("=", "%3D", $key);
                $value = str_replace("%", "%25", $value);
                $value = str_replace("&", "%26", $value);

                $arr[] = $key . "=" . $value;
            }

            $str = implode('&', $arr);
            // dd(config('custom.shopify_client_secret'));
            // $ver_hmac =  hash_hmac('sha256', $str, "custom.shopify_client_secret", false);
            $ver_hmac =  hash_hmac('sha256', $str, config('custom.shopify_client_secret'), false);
            //it will return true if hmac verification succeed otherwise return false
            if ($ver_hmac === $hmac) {
                return true;
            } else {
                Log::info("hmac verification failed hmac=" . " " . $hmac . 'provided hmac is: ' . " " . $ver_hmac);
            }
            // return $ver_hmac === $hmac;
        } catch (Exception $e) {
            Log::info('Problem with the hmac verification');
            Log::info($e->getMessage() . " " . $e->getLine());
            return false;
        }
    }

    //Local Helper Functions

    //we will use guzzle http request here to fetch the shop object if status code is 200 and shop object
    //fetched successfully it means access token is valid and we will open the app

    private function checkAcessTokenIsValid($storeDetails)
    {
        try {
            if ($storeDetails !== NULL && isset($storeDetails->access_token) && strlen($storeDetails->access_token) > 0) {
                $token = $storeDetails->access_token;
                //write guzzle request to shopify with access token to fetch the shop object or anything
                //to test if the access token is valid or not
                //if request came with status 200 it means accesss_token is still valid
                //if request came with 422 or any error it means access token is invalid
                $endpoint = getShopifyURLForStore('shop.json', $storeDetails);
                $headers = getShopifyHeadersForStore($storeDetails);
                $response = $this->makeAnApiCallToShopify('GET', $endpoint, null, $headers, null);
                Log::info('Log for checking the validity of token');
                Log::info($response);
                //we will know if status code equal to 200 it means our access_token is valid & we return
                //true or false based on this line
                return $response['statusCode'] === 200;
            }
            return false;
        } catch (Exception $e) {
            Log::info($e->getMessage() . " " . $e->getLine());
        }
    }
    public function handleRedirect(Request $request)
    {
        //when user click in install app button then shopify will redirect to our server on that redirect url
        //with some get parameters like shop,hmac,code,timestamps
        //to see what kind of data shopify will pass to our server redirect url see below url
        //https://example.org/some/redirect/uri?code={authorization_code}&hmac=da9d83c171400a41f8db91a950508985&host={base64_encoded_hostname}&shop={shop_origin}&state={nonce}&timestamp=1409617544

        //at very first we will again ensure that the data is sent by shopify server we will verify hmac
        //as we have already made a function to validate hmac

        try {
            $validRequest = $this->validateRequestFromShopify($request->all());
            if ($validRequest) {
                Log::info($request->all());
                if ($request->has('shop') && $request->has('code')) {
                    $shop = $request->shop;
                    $code = $request->code;
                    $accessToken = $this->requestAccessTokenFromShopifyForThisStore($shop, $code);
                    if ($accessToken !== false && $accessToken !== null) {
                        $shopDetails = $this->getShopDetailsFromShopify($accessToken, $shop);
                        $savePayloadToDB = $this->saveStoreDetailsToDatabase($shopDetails, $accessToken);
                        if ($savePayloadToDB['success']) {
                            $request->session()->flash('flash.banner', 'You login credentials sent to your store email:' ." ". $savePayloadToDB['email']);
                            return redirect()->route('login')->banner('App Installed Sucessfully! You login credentials sent to your store email:' ." ". '<b>'.$savePayloadToDB['email'].'</b>');
                        } else {
                            Log::info('Error while saving the shop object record in database');
                            Log::info($savePayloadToDB);
                        }
                    } else throw new Exception('Access Token Error:' . $accessToken);
                } else throw new Exception('Request has no code or shop name');
            } else throw new Exception('Request is not valid hmac verification fails');
        } catch (Exception $e) {
            Log::info($e->getMessage() . " " . $e->getLine());
            dd($e->getMessage() . " " . $e->getLine());
        }
    }
    private function getShopDetailsFromShopify($accessToken, $shop)
    {
        try {
            $endpoint = getShopifyURLForStore('shop.json', ['myshopify_domain' => $shop]);
            $headers = getShopifyHeadersForStore(['access_token' => $accessToken]);
            $response = $this->makeAnApiCallToShopify('GET', $endpoint, null, $headers, null);
            if ($response['statusCode'] == 200) {
                $body = $response['body'];
                if (!is_array($body)) {
                    $body = json_decode($body, true);
                    return $body['shop'] ?? null;
                }
            } else {
                Log::info('Error while getting shop object from shopify via Rest API');
                Log::info($response);
                return null;
            }
        } catch (Exception $e) {
            Log::info('Error while getting shop object from shopify via Rest API');
            Log::info($e->getMessage() . " " . $e->getLine());
        }
    }
    private function requestAccessTokenFromShopifyForThisStore($shop, $code)
    {
        try {
            //here i make the first endpoing which will fetch the shop object to check the api
            $endpoint = 'https://' . $shop . '/admin/oauth/access_token';
            //but first we have to send POST request to this url
            //url: https://{shop}.myshopify.com/admin/oauth/access_token?client_id={client_id}&client_secret={client_secret}&code={authorization_code}
            //where we have to pass client_id,client_secret & code in request body
            $headers = ['Content-Type: application/json'];
            //as we are using guzzle/http library where pass parameters in request body like this
            $requestBody = json_encode([
                'client_id' => config('custom.shopify_client_key'),
                'client_secret' => config('custom.shopify_client_secret'),
                'code' => $code
            ]);
            // $response = $this->makeAnApiCallToShopify('POST', $endpoint, null, $headers, $requestBody);
            $response = $this->makeAPOSTCallToShopify($requestBody, $endpoint, $headers);
            Log::info('Response for getting access token');
            Log::info(json_encode($response));
            if ($response['statusCode'] == 200) {
                $body = $response['body'];
                if (!is_array($body)) {
                    $body = json_decode($body, true);
                }
                if (isset($body['access_token']) && $body['access_token'] !== null) {
                    return $body['access_token'];
                }
            }
            return false;
        } catch (Exception $e) {
            Log::info($e->getMessage() . " " . $e->getLine());
            return false;
        }
    }
    public function shopifyAppInstallationCompleted()
    {
        print_r('App installation completed');
    }
    public function saveStoreDetailsToDatabase($shopDetails, $accessToken)
    {
        try {
            $payload = [
                'access_token' => $accessToken,
                'myshopify_domain' => $shopDetails['myshopify_domain'],
                'id' => $shopDetails['id'],
                'email' => $shopDetails['email'],
                'name' => $shopDetails['name'],
                'address1' => $shopDetails['address1'],
                'address2' => $shopDetails['address2'],
                'phone' => $shopDetails['phone'],
                'zip' => $shopDetails['zip'],
            ];
            $latestInsertedStoreRecord = Store::updateOrCreate(['myshopify_domain' => $shopDetails['myshopify_domain']], $payload);
            Log::info('Store Record: ', [$latestInsertedStoreRecord]);
            //here i am going to create new user against shopify email of store owner which installed the app on 
            //his store & also put primary key of store table in my case which is table_id into user table column 
            //named store_id

            //We will check here if user already existed in database but access token is invalid or user somehow delete app
            //from shopify in that case use will remain same we will only change access_token against that user's store

                $user = User::where('email', $shopDetails['email'])->first();
                Log::info("looking for user against shop owner email User Data: " .$user);
                if(!empty($user)) {
                            Log::info('user with that email already existed only access token changed for that store');
                            return true;
                            exit;
                }
            
            //now user creation starts here
            
                Log::info("table_id is: " .$latestInsertedStoreRecord->table_id);
                $randomPassForNewUser = Str::random(10);
                Log::info('Password Generated:' . " " . $randomPassForNewUser);
                $newUserPayload = [
                    'email' => $shopDetails['email'],
                    'password' => bcrypt($randomPassForNewUser),
                    'store_id' => $latestInsertedStoreRecord->table_id,
                    'name' => $shopDetails['name'],
                    'email_verified_at' => date('Y-m-d h:i:s')
                ];
                //here we check if user already present then we simply update the data otherwise
                //we will create new one by giving payload
                // Log::info('New User Payload is: ' . " " . $newUserPayload);
                try {
                    $latestInsertedUserRecord = User::updateOrCreate(['email' => $shopDetails['email']], $newUserPayload);
                } catch (Exception $e) {
                    Log::info($e->getMessage() . " " . $e->getLine());
                }
                Log::info('Now Sending Email To Registered User');
                //now we will send email to user here with his credentials
                try {
                    $isEmailSent = Mail::to($shopDetails['email'])->send(new NewUserUponAppInstall($newUserPayload, $randomPassForNewUser));
                    if($isEmailSent)
                    {
                        Log::info('Email Sent Successfully');
                        $latestInsertedUserRecord->markEmailAsVerified();
                    }
                   
                } catch (Exception $e) {
                    Log::info($e->getMessage() . " " . $e->getLine());
                }
            return ['success' => true, 'email' => $latestInsertedUserRecord->email];
        } catch (Exception $e) {
            Log::info($e->getMessage() . " " . $e->getLine());
            return false;
        }
    }
}
