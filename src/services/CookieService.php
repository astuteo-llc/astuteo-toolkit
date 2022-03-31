<?php

namespace astuteo\astuteotoolkit\services;
use craft\base\Component;
use Craft;
use craft\helpers\Json;

/**
 * Class CpNavService
 *
 * @package astuteo\astuteotoolkit\services
 */
class CookieService extends Component {
    public static function setInsecureCookie($name, $value, $key = null) {
        if(!$key) {
            $result = setcookie($name, $value, time()+36000, '/');
            return;
        }
        if(!isset($_COOKIE[$name])) {
            setcookie($name, '{}', time()+36000, '/');
        }
        $encodedCookie = self::insecureCookie($name);
        // if we are setting a key, make sure we don't override
        // existing keys
        $cookieObject = Json::decodeIfJson($encodedCookie);
        $cookieObject[$key] = $value;
        $updated = json_encode($cookieObject);
        $result = setcookie($name, $updated, time()+36000, '/');
        return;
    }

    public static function insecureCookie($name, $default = false) {
        if(!isset($_COOKIE[$name])) {
            return $default;
        } else {
            return $_COOKIE[$name];
        }
    }

    public static function insecureCookieWithKey($name, $key) {
        if(!isset($_COOKIE[$name])) {
            return null;
        }
        $cookie = Json::decodeIfJson($_COOKIE[$name]);
        if(!isset($cookie[$key])) {
            return null;
        }
        return $cookie[$key];
    }
}
