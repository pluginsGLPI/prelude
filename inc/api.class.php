<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class PluginPreludeAPI extends CommonGLPI {

   static function connect($params) {
      PluginPreludeOauthProvider::connect($params);
   }

   static function status() {
      return [__("Prelude access token", 'prelude')
                  => is_string(PluginPreludeConfig::getCurrentAccessToken()),
              __("Prelude alerts", 'prelude')
                  => is_array(self::getAlerts()),
              __("Prelude logs", 'prelude')
                  => is_array(self::getLogs())];
   }

   static function globalStatus() {
      return !in_array(false, self::status());
   }

   public static function getLogs($params = array()) {
      $default_params = [
         'query' => [
            'action'  => 'retrieve',
            'request' => ['path' => ['log.timestamp',
                                     'log.host'],
                          'limit' =>  100,
                          'offset' => 0],
         ]
      ];
      $params = array_merge($default_params, $params);
      $params['query']['request'] = json_encode($params['query']['request']);
      return self::sendHttpRequest('GET', '', $params);
   }

   public static function getAlerts($params = array()) {
      $default_params = [
         'query' => [
            'action'  => 'retrieve',
            'request' => ['path' => ['alert.create_time',
                                     'alert.classification.text'],
                          'limit' =>  100,
                          'offset' => 0],
         ]
      ];
      $params = array_merge($default_params, $params);
      $params['query']['request'] = json_encode($params['query']['request']);
      return self::sendHttpRequest('GET', '', $params);
   }

   private static function sendHttpRequest($method = 'GET', $ressource = '', $http_params = array()) {
      // init stuff
      $prelude_config   = PluginPreludeConfig::getConfig();
      $base_uri         = trim($prelude_config['prelude_url'], '/').'/api';
      $http_client      = new \GuzzleHttp\Client(['base_uri' => $base_uri]);

      // retrieve access token
      if (!$access_token_str = PluginPreludeConfig::getCurrentAccessToken()) {
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

}
