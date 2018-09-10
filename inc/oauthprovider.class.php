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
   }

   protected function checkResponse(ResponseInterface $response, $data) {
      return ' ';
   }

   protected function createResourceOwner(array $response, AccessToken $token) {

   }

   static function getInstance() {
      $prelude_config = PluginPreludeConfig::getConfig();
      $provider = new self([
         'baseuri'      => $prelude_config['prelude_url'],
         'clientId'     => $prelude_config['api_client_id'],
         'clientSecret' => $prelude_config['api_client_secret'],
      ]);

      return $provider;
   }

   static function connect($params = []) {
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

      } else if (empty($params['state'])
                 || ($params['state'] !== $_SESSION['oauth2state'])) {
         // Check given state against previously stored one to mitigate CSRF attack

         unset($_SESSION['oauth2state']);
         exit('Invalid state');

      } else {
         try {
            // Try to get an access token using the authorization code grant.
            $access_token = $provider->getAccessToken(
               'authorization_code',
               ['code' => $params['code']]
            );

            // We have an access token, and we can store it
            PluginPreludeAPIClient::storeToken($access_token);

         } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

            // Failed to get the access token or user details.
            exit($e->getMessage());

         }
      }
   }

}
