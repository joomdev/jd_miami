<?php
/**
 * @package SJ Social Media Counter
 * @version 1.0.1
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2014 YouTech Company. All Rights Reserved.
 * @author YouTech Company http://www.smartaddons.com
 */

defined('_JEXEC') or die ();

if (!class_exists('oauth')) {
    require_once dirname(__FILE__) . '/OAuth.php';
}

class LinkedInException extends Exception
{
}

if (!class_exists('LinkedInOAuth')) {

    class LinkedInOAuth
    {
        const _API_OAUTH_REALM = 'http://api.linkedin.com';
        const _API_OAUTH_VERSION = '1.0';
        const _DEFAULT_RESPONSE_FORMAT = 'xml';
        const _GET_RESPONSE = 'lResponse';
        const _GET_TYPE = 'lType';
        const _INV_SUBJECT = 'Invitation to connect';
        const _INV_BODY_LENGTH = 200;
        const _METHOD_TOKENS = 'POST';
        const _NETWORK_LENGTH = 1000;
        const _NETWORK_HTML = '<a>';
        const _RESPONSE_JSON = 'JSON';
        const _RESPONSE_JSONP = 'JSONP';
        const _RESPONSE_XML = 'XML';
        const _SHARE_COMMENT_LENGTH = 700;
        const _SHARE_CONTENT_TITLE_LENGTH = 200;
        const _SHARE_CONTENT_DESC_LENGTH = 400;
        const _URL_ACCESS = 'https://api.linkedin.com/uas/oauth/accessToken';
        const _URL_API = 'https://api.linkedin.com';
        const _URL_AUTH = 'https://www.linkedin.com/uas/oauth/authenticate?oauth_token=';
        const _URL_REQUEST = 'https://api.linkedin.com/uas/oauth/requestToken?scope=r_basicprofile+r_emailaddress+r_network+r_fullprofile+r_contactinfo';
        const _URL_REVOKE = 'https://api.linkedin.com/uas/oauth/invalidateToken';
        const _VERSION = '3.2.0';
        protected $callback;
        protected $token = NULL;
        protected $application_key, $application_secret;
        protected $response_format = self::_DEFAULT_RESPONSE_FORMAT;
        public $last_request_headers, $last_request_url;

        public function __construct($config)
        {
            if (!is_array($config)) {
                throw new LinkedInException('LinkedInOAuth->__construct(): bad data passed, $config must be of type array.');
            }
            $this->setApplicationKey($config['appKey']);
            $this->setApplicationSecret($config['appSecret']);
            $this->setCallbackUrl($config['callbackUrl']);
        }

        public function __destruct()
        {
            unset($this);
        }

        private function checkResponse($http_code_required, $response)
        {
            if (is_array($http_code_required)) {
                array_walk($http_code_required, function ($value, $key) {
                    if (!is_int($value)) {
                        throw new LinkedInException('LinkedInOAuth->checkResponse(): $http_code_required must be an integer or an array of integer values');
                    }
                });
            } else {
                if (!is_int($http_code_required)) {
                    throw new LinkedInException('LinkedInOAuth->checkResponse(): $http_code_required must be an integer or an array of integer values');
                } else {
                    $http_code_required = array(
                        $http_code_required
                    );
                }
            }
            if (!is_array($response)) {
                throw new LinkedInException('LinkedInOAuth->checkResponse(): $response must be an array');
            }
            if (in_array($response['info']['http_code'], $http_code_required)) {
                $response['success'] = TRUE;
            } else {
                $response['success'] = FALSE;
                $response['error'] = 'HTTP response from LinkedIn end-point was not code ' . implode(', ', $http_code_required);
            }
            return $response;
        }

        public function connections($options = '~/connections')
        {
            if (!is_string($options)) {
                throw new LinkedInException('LinkedInOAuth->connections(): bad data passed, $options must be of type string.');
            }
            $query = self::_URL_API . '/v1/people/' . trim($options);
            $response = $this->fetch('GET', $query);
            return $this->checkResponse(200, $response);
        }

        public function followedCompanies()
        {
            $query = self::_URL_API . '/v1/people/~/following/companies';
            $response = $this->fetch('GET', $query);
            return $this->checkResponse(200, $response);
        }

        public function profile($options = '~')
        {
            if (!is_string($options)) {
                throw new LinkedInException('LinkedIn->profile(): bad data passed, $options must be of type string.');
            }

            $query = self::_URL_API . '/v1/people/' . trim($options);
            $response = $this->fetch('GET', $query);

            return $this->checkResponse(200, $response);
        }

        public function isThrottled($response)
        {
            $return_data = FALSE;
            if (!empty($response) && is_string($response)) {
                $temp_response = $this->xmlToArray($response);
                if ($temp_response !== FALSE) {
                    if (array_key_exists('error', $temp_response) && ($temp_response['error']['children']['status']['content'] == 403) && preg_match('/throttle/i', $temp_response['error']['children']['message']['content'])) {
                        $return_data = TRUE;
                    }
                }
            }
            return $return_data;
        }

        protected function fetch($method, $url, $data = NULL, $parameters = array())
        {
            if (!extension_loaded('curl')) {
                throw new LinkedInException('LinkedInOAuth->fetch(): PHP cURL extension does not appear to be loaded/present.');
            }
            try {
                $oauth_consumer = new OAuthConsumer($this->getApplicationKey(), $this->getApplicationSecret(), $this->getCallbackUrl());
                $oauth_token = $this->getToken();
                $oauth_token = (!is_null($oauth_token)) ? new OAuthToken($oauth_token['oauth_token'], $oauth_token['oauth_token_secret']) : NULL;
                $defaults = array(
                    'oauth_version' => self::_API_OAUTH_VERSION
                );
                $parameters = array_merge($defaults, $parameters);
                $oauth_req = OAuthRequest::from_consumer_and_token($oauth_consumer, $oauth_token, $method, $url, $parameters);
                $oauth_req->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $oauth_consumer, $oauth_token);
                if (!$handle = curl_init()) {
                    throw new LinkedInException('LinkedInOAuth->fetch(): cURL did not initialize properly.');
                }
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($handle, CURLOPT_URL, $url);
                curl_setopt($handle, CURLOPT_VERBOSE, FALSE);
                $header = array(
                    $oauth_req->to_header(self::_API_OAUTH_REALM)
                );
                if (is_null($data)) {
                    $header[] = 'Content-Type: text/plain; charset=UTF-8';
                    switch ($this->getResponseFormat()) {
                        case self::_RESPONSE_JSON:
                            $header[] = 'x-li-format: json';
                            break;
                        case self::_RESPONSE_JSONP:
                            $header[] = 'x-li-format: jsonp';
                            break;
                    }
                } else {
                    $header[] = 'Content-Type: text/xml; charset=UTF-8';
                    curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                }
                curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
                $this->last_request_url = $url;
                $this->last_request_headers = $header;
                $return_data['linkedin'] = curl_exec($handle);
                $return_data['info'] = curl_getinfo($handle);
                $return_data['oauth']['header'] = $oauth_req->to_header(self::_API_OAUTH_REALM);
                $return_data['oauth']['string'] = $oauth_req->base_string;
                if (self::isThrottled($return_data['linkedin'])) {
                    throw new LinkedInException('LinkedInOAuth->fetch(): throttling limit for this user/application has been reached for LinkedIn resource - ' . $url);
                }
                curl_close($handle);
                return $return_data;
            } catch (OAuthException $e) {
                throw new LinkedInException('OAuth exception caught: ' . $e->getMessage());
            }
        }

        public function getApplicationKey()
        {
            return $this->application_key;
        }

        public function getApplicationSecret()
        {
            return $this->application_secret;
        }

        public function getCallbackUrl()
        {
            return $this->callback;
        }

        public function getResponseFormat()
        {
            return $this->response_format;
        }

        public function getToken()
        {
            return $this->token;
        }

        public function getTokenAccess()
        {
            return $this->getToken();
        }

        public function lastRequestHeader()
        {
            return $this->last_request_headers;
        }

        public function lastRequestUrl()
        {
            return $this->last_request_url;
        }

        public function raw($method, $url, $body = NULL)
        {
            if (!is_string($method)) {
                throw new LinkedInException('LinkedInOAuth->raw(): bad data passed, $method must be of string value.');
            }
            if (!is_string($url)) {
                throw new LinkedInException('LinkedInOAuth->raw(): bad data passed, $url must be of string value.');
            }
            if (!is_null($body) && !is_string($url)) {
                throw new LinkedInException('LinkedInOAuth->raw(): bad data passed, $body must be of string value.');
            }
            $query = self::_URL_API . '/v1' . trim($url);
            return $this->fetch($method, $query, $body);
        }

        public function setApplicationKey($key)
        {
            $this->application_key = $key;
        }

        public function setApplicationSecret($secret)
        {
            $this->application_secret = $secret;
        }

        public function setCallbackUrl($url)
        {
            $this->callback = $url;
        }

        public function setGroupSettings($gid, $xml)
        {
            if (!is_string($gid)) {
                throw new LinkedInException('LinkedInOAuth->setGroupSettings(): bad data passed, $token_access should be in array format.');
            }
            if (!is_string($xml)) {
                throw new LinkedInException('LinkedInOAuth->setGroupSettings(): bad data passed, $token_access should be in array format.');
            }
            $query = self::_URL_API . '/v1/people/~/group-memberships/' . trim($gid);
            $response = $this->fetch('PUT', $query, $xml);
            return $this->checkResponse(200, $response);
        }

        public function setResponseFormat($format = self::_DEFAULT_RESPONSE_FORMAT)
        {
            $this->response_format = $format;
        }

        public function setToken($token)
        {
            if (!is_null($token) && !is_array($token)) {
                throw new LinkedInException('LinkedInOAuth->setToken(): bad data passed, $token_access should be in array format.');
            }
            $this->token = $token;
        }

        public function setTokenAccess($token_access)
        {
            $this->setToken($token_access);
        }

        public function getFollowersCount($id)
        {
            $query = self::_URL_API . '/v1/companies/' . $id . ':(id,name,num-followers)';
            $response = $this->fetch('GET', $query);
            return $this->checkResponse(200, $response);
        }

        public function xmlToArray($xml)
        {
            if (!is_string($xml)) {
                throw new LinkedInException('LinkedInOAuth->xmlToArray(): bad data passed, $xml must be a non-zero length string.');
            }
            $parser = xml_parser_create();
            xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
            xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
            if (xml_parse_into_struct($parser, $xml, $tags)) {
                $elements = array();
                $stack = array();
                foreach ($tags as $tag) {
                    $index = count($elements);
                    if ($tag['type'] == 'complete' || $tag['type'] == 'open') {
                        $elements[$tag['tag']] = array();
                        $elements[$tag['tag']]['attributes'] = (array_key_exists('attributes', $tag)) ? $tag['attributes'] : NULL;
                        $elements[$tag['tag']]['content'] = (array_key_exists('value', $tag)) ? $tag['value'] : NULL;
                        if ($tag['type'] == 'open') {
                            $elements[$tag['tag']]['children'] = array();
                            $stack[count($stack)] =& $elements;
                            $elements =& $elements[$tag['tag']]['children'];
                        }
                    }
                    if ($tag['type'] == 'close') {
                        $elements =& $stack[count($stack) - 1];
                        unset($stack[count($stack) - 1]);
                    }
                }
                $return_data = $elements;
            } else {
                $return_data = FALSE;
            }
            xml_parser_free($parser);
            return $return_data;
        }
    }
}
?>