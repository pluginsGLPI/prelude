<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class PluginPreludeAPI extends CommonGLPI {

   private static function sendHttpRequest($params) {
      // init stuff
      $prelude_config = PluginPreludeConfig::getInstance();
      $base_uri       = trim($prelude_config->fields['prelude_url'], '/').'/api';
      $http_client    = new \GuzzleHttp\Client(['base_uri' => $base_uri]);

      // declare default params and merge it with provided params
      $default_params = [
         'method'          => 'GET',
         'resource'        => '',
         'allow_redirects' => false,
         'query'           => [], // url parameter
         'body'            => '', // raw data to send in body
         'json'            => '', // json data to send
         'headers'         => ['content-type'  => 'application/json',
                               'Authorization' => 'Bearer '.$prelude_config->fields['api_token']],
      ];
      $params = array_merge($default_params, $params);

      // prepare http params for guzzle
      $http_params = ['allow_redirects' => $params['allow_redirects'],
                      'headers'         => $params['headers']];
      if (count($params['query']) > 0) {
         $http_params['query'] = $params['query'];
      }
      if (!empty($params['body'])) {
         $http_params['body'] = $params['body'];
      }
      if (!empty($params['json'])) {
         $http_params['json'] = $params['json'];
      }

      // send http request
      try {
         $response = $http_client->request($params['method'],
                                           $params['resource'],
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

   public static function getLogs($params = array()) {
      $default_params = [
         'limit' =>  100,
         'offset' => 0,
      ];
      $params = array_merge($default_params, $params);
      $request_params = [
         'query' => [
            'action'  => 'retrieve',
            'request' => ['path' => ['log.timestamp',
                                     'log.host']],
         ]
      ];
      $request_params['query']['request'] = array_merge($request_params['query']['request'],
                                                        $params);
      $request_params['query']['request'] = json_encode($request_params['query']['request']);
      return self::sendHttpRequest($request_params);
   }

   public static function getAlerts($params = array()) {
      $default_params = [
         'limit' =>  100,
         'offset' => 0,
      ];
      $params = array_merge($default_params, $params);
      $request_params = [
         'query' => [
            'action'  => 'retrieve',
            'request' => ['path' => ['alert.create_time',
                                     'alert.classification.text']],
         ]
      ];
      $request_params['query']['request'] = array_merge($request_params['query']['request'],
                                                        $params);
      $request_params['query']['request'] = json_encode($request_params['query']['request']);
      return self::sendHttpRequest($request_params);
   }

   public static function status() {
      $logs   = self::getLogs();
      $alerts = self::getAlerts();
      return array(__("API: get logs", 'prelude')   => boolval($logs),
                   __("API: get alerts", 'prelude') => boolval($alerts));
   }
}
