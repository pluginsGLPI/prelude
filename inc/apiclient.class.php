<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use League\OAuth2\Client\Token\AccessToken;

class PluginPreludeAPIClient extends CommonGLPI {

   const DEFAULT_LIMIT  = 1000;
   const DEFAULT_OFFSET = 0;

   /**
    * Return the prelude API base uri
    * @return string the uri
    */
   static function getAPIBaseUri() {
      $prelude_config = PluginPreludeConfig::getConfig();
      return trim($prelude_config['prelude_url'], '/').'/api';
   }

   /**
    * Connect to prelude API
    * @param  array $params [description]
    */
   static function connect($params = array()) {
      return PluginPreludeOauthProvider::connect($params);
   }

   /**
    * check all api endpoints
    * @return array list of label -> boolean
    */
   static function status() {
      return [__("Prelude", 'prelude')
                  => self::preludeStatus(),
              __("Prelude access token", 'prelude')
                  => is_string(self::getCurrentAccessToken()),
              __("Prelude alerts", 'prelude')
                  => is_array(self::getAlerts()),
              __("Prelude logs", 'prelude')
                  => is_array(self::getLogs())];
   }

   /**
    * check api status
    * @return boolean true if all endpoints success to connect
    */
   static function globalStatus() {
      return !in_array(false, self::status());
   }

   /**
    * Check presence of prelude application
    * @return boolean
    */
   static function preludeStatus() {
      $http_client = new \GuzzleHttp\Client(['base_uri' => self::getAPIBaseUri()]);
      $params      = [
         'http_errors' => false,
      ];
      $response    = $http_client->request('', '', $params);
      $code        = $response->getStatusCode();

      if (!in_array($code, [200, 403])) {
         return false;
      }

      return true;
   }

   /**
    * Retrieve logs within prelude api
    * @param  array  $params with theses possible keys:
    *                        - limit: restrict the number of answer returned by the api
    *                        - offset: start the answer collection to the provided offset
    *                        - path: list of idmef nodes to retrieve.
    *                          (see https://www.prelude-siem.org/projects/prelude/wiki/IDMEFPath)
    *                        - criteria: list of criteria to filter the query
    *                          (see https://www.prelude-siem.org/projects/prelude/wiki/IDMEFCriteria)
    * @return array  the logs with path in keys and corresponding values
    */
   public static function getLogs($params = array()) {
      self::checkAccessToken();

      // what we need at minima in idmef tree
      $default_paths = [
         'log.timestamp',
         'log.host',
      ];

      // contruct the options sent to the query
      $default_params =
         ['limit'   => self::DEFAULT_LIMIT,
          'offset'  => self::DEFAULT_OFFSET,
          'path'    => [],
          'criteria' => []
      ];
      $params = array_merge($default_params, $params);
      $params['path'] = array_merge($default_paths, $params['path']);
      $query_options = [
         'query' => [
            'action'  => 'retrieve',
            'request' => json_encode($params),
         ]
      ];

      // send the query to prelude
      $logs_json = self::sendHttpRequest('GET', '', $query_options);
      $logs      = json_decode($logs_json, true);

      // merges key for response (otherwise he will have indexed keys)
      if (isset($logs['response'])) {
         foreach($logs['response'] as &$response) {
            $response = array_combine($params['path'], $response);
         }
      }

      return $logs['response'];
   }

   /**
    * Retrieve alerts within prelude api
    * @param  array  $params with theses possible keys:
    *                        - limit: restrict the number of answer returned by the api
    *                        - offset: start the answer collection to the provided offset
    *                        - path: list of idmef nodes to retrieve.
    *                          (see https://www.prelude-siem.org/projects/prelude/wiki/IDMEFPath)
    *                        - criteria: list of criteria to filter the query
    *                          (see https://www.prelude-siem.org/projects/prelude/wiki/IDMEFCriteria)
    * @return array  the alerts with path in keys and corresponding values
    */
   public static function getAlerts($params = array()) {
      self::checkAccessToken();

      // what we need at minima in idmef tree
      $default_paths = [
         'alert.create_time',
         'alert.classification.text',
         'alert.source(0).node.address(0).address',
         'alert.target(0).node.address(0).address',
         'alert.analyzer(-1).name',
      ];

      // contruct the options sent to the query
      $default_params =
         ['limit'    => self::DEFAULT_LIMIT,
          'offset'   => self::DEFAULT_OFFSET,
          'path'     => [],
          'criteria' => []
      ];
      $params = array_merge($default_params, $params);
      $params['path'] = array_merge($default_paths, $params['path']);
      $query_options = [
         'query' => [
            'action'  => 'retrieve',
            'request' => json_encode($params),
         ]
      ];

      // send the query to prelude
      $alerts_json = self::sendHttpRequest('GET', '', $query_options);
      $alerts      = json_decode($alerts_json, true);

      // merges key for response (otherwise he will have indexed keys)
      if (isset($alerts['response'])) {
         foreach($alerts['response'] as &$response) {
            $response = array_combine($params['path'], $response);
         }
      }

      return $alerts['response'];
   }

   /**
    * Send an http query with guzzle library and manage the return
    * @param  string $method      HTTP method (GET/POST/etc),
    *                             see https://en.wikipedia.org/wiki/HTTP#Request_methods
    * @param  string $ressource   the endpoint to access (after the api base url)
    * @param  array  $http_params some parameter to send with the query, here is the default keys:
    *                              - allow_redirects (default false),
    *                                 permit server to autoredirect the http call.
    *                              - query: parameters send in url.
    *                              - body: raw data to append to the query body.
    *                              - json: json data to append to the query body.
    *                                      This option cannot be used with body option.
    *                              - headers: parameters to send in http query headers
    * @return mixed               the output returned by the http query
    */
   private static function sendHttpRequest($method = 'GET', $ressource = '', $http_params = array()) {
      // init stuff
      $http_client = new \GuzzleHttp\Client(['base_uri' => self::getAPIBaseUri()]);

      // retrieve access token
      if (!$access_token_str = self::getCurrentAccessToken()) {
         return false;
      }

      // declare default params and merge it with provided params
      $default_params = [
         'allow_redirects' => false,
         'query'           => [], // url parameter
         'body'            => '', // raw data to send in body
         'json'            => '', // json data to send
         'headers'         => ['content-type'  => 'application/json',
                               'Authorization' => 'Bearer '.$access_token_str],
      ];
      $http_params = array_merge($default_params, $http_params);

      //remove empty values
      $http_params = array_filter($http_params, function($value) {
         return $value !== "";
      });

      // send http request
      try {
         $response = $http_client->request($method,
                                           $ressource,
                                           $http_params);
      } catch (RequestException $e) {
         $debug = ["Prelude API error"];
         $debug = [$http_params];
         $debug[] = Psr7\str($e->getRequest());
         if ($e->hasResponse()) {
            $debug[] = Psr7\str($e->getResponse());
         }
         Toolbox::logDebug($debug);
         return false;
      }
      // parse http response
      $http_code        = $response->getStatusCode();
      $reason_phrase    = $response->getReasonPhrase();
      $protocol_version = $response->getProtocolVersion();

      // check http errors
      if (intval($http_code) > 400) {
         // we have an error if http code is greater than 400
         return false;
      }
      // cast body as string, guzzle return strems
      $json        = (string) $response->getBody();
      $prelude_res = json_decode($json, true);

      // check prelude error
      $prelude_error = false;
      if (isset($prelude_res['logs'])) {
         foreach($prelude_res['logs'] as $log) {
            if (isset($log['errno'])) {
               $prelude_error = true;
            }
         }

         if ($prelude_error) {
            Toolbox::logDebug($prelude_res['logs']);
            return false;
         }
      }
      return $json;
   }

   /**
    * Store an outh access token in plugin config
    * @param  AccessToken $token instance of a token
    *                            provided by League\OAuth2\Client\Token\AccessToken
    * @return boolean
    */
   static function storeToken(AccessToken $token) {
      $json = json_encode($token->jsonSerialize());
      return Config::setConfigurationValues('plugin:Prelude',
                                            array('api_token' => $json));
   }

   /**
    * Retrieve the current access token from the plugin config
    * @return mixed false if we fail to retrieve the token
    *               or an instance of League\OAuth2\Client\Token\AccessToken
    */
   static function retrieveToken() {
      $prelude_config = PluginPreludeConfig::getConfig();
      if (isset($prelude_config['api_token'])
          && $access_token_array = json_decode($prelude_config['api_token'], true)) {
         return new AccessToken($access_token_array);
      }

      return false;
   }

   /**
    * delete the oauth token store in db
    */
   static function deleteToken() {
     return Config::setConfigurationValues('plugin:Prelude', array('api_token' => ''));
   }

   /**
    * get the access token in string
    * @return mixed false if we fail to retrieve the token
    *               of the token in string
    */
   static function getCurrentAccessToken() {
     if ($token = self::retrieveToken()) {
         return $token->getToken();
      }

      return false;
   }

   /**
    * check if the access token stored in db is valid and not expired
    * if fail, send a refresh query to get a new access token
    */
   static function checkAccessToken() {
      if ($prev_access_token = self::retrieveToken()) {
         if ($prev_access_token->hasExpired()) {
            $provider = PluginPreludeOauthProvider::getInstance();
            $new_access_token = $provider->getAccessToken('refresh_token', [
               'refresh_token' => $prev_access_token->getRefreshToken()
            ]);

            self::storeToken($new_access_token);
         }
      }
   }

}
