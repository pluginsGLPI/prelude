<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class PluginPreludeOauthProvider extends AbstractProvider {
   use BearerAuthorizationTrait;

   protected $baseuri;

   function __construct($options = []) {
      parent::__construct($options);
      if (isset($options['baseuri'])) {
         $this->baseuri = rtrim($options['baseuri'], '/');
      }
   }

   function getBaseAuthorizationUrl() {
      return $this->baseuri.'/api_authorize';
   }

   function getBaseAccessTokenUrl(array $params) {
      return $this->baseuri.'/api_token';
   }

   function getResourceOwnerDetailsUrl(AccessToken $token) {
      return '';
   }

   protected function getDefaultScopes() {
      return [];
   }

   protected function checkResponse(ResponseInterface $response, $data) {
      return ' ';
   }

   protected function createResourceOwner(array $response, AccessToken $token) {

   }

   private static function getInstance() {
      $prelude_config = PluginPreludeConfig::getConfig();
      $provider = new self([
         'baseuri'      => $prelude_config['prelude_url'],
         'clientId'     => $prelude_config['api_client_id'],
         'clientSecret' => $prelude_config['api_client_secret'],
      ]);

      return $provider;
   }

   static function connect($params = array()) {
      $provider = self::getInstance();

      // If we don't have an authorization code then get one
      if (!isset($params['code'])) {
         // Fetch the authorization URL from the provider; this returns the
         // urlAuthorize option and generates and applies any necessary parameters
         // (e.g. state).
         $authorizationUrl = $provider->getAuthorizationUrl();

         // Get the state generated for you and store it to the session.
         $_SESSION['oauth2state'] = $provider->getState();

         // Redirect the user to the authorization URL.
         Html::redirect($authorizationUrl);
         exit;

      // Check given state against previously stored one to mitigate CSRF attack
      } else if (empty($params['state'])
                 || ($params['state'] !== $_SESSION['oauth2state'])) {

         unset($_SESSION['oauth2state']);
         exit('Invalid state');

      } else try {
         // Try to get an access token using the authorization code grant.
         $access_token = $provider->getAccessToken('authorization_code',
                                                   ['code' => $params['code']]);

         // We have an access token, and we can store it
         PluginPreludeConfig::storeAccessToken($access_token);


         // The provider provides a way to get an authenticated API request for
         // the service, using the access token; it returns an object conforming
         // to Psr\Http\Message\RequestInterface.
         $request = $provider->getAuthenticatedRequest(
            'GET',
            'http://brentertainment.com/oauth2/lockdin/resource',
            $access_token
         );

      } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

         // Failed to get the access token or user details.
         exit($e->getMessage());

      }
   }

   /**
    * check if the access token stored in db is valid and not expired
    * if fail, send a refresh query to get a new access token
    */
   static function checkAccessToken() {
      if ($prev_access_token = PluginPreludeConfig::retrieveToken()) {
         if ($prev_access_token->hasExpired()) {
            $provider = self::getInstance();
            $new_access_token = $provider->getAccessToken('refresh_token', [
               'refresh_token' => $prev_access_token->getRefreshToken()
            ]);

            PluginPreludeConfig::storeAccessToken($new_access_token);
         }
      }
   }

}