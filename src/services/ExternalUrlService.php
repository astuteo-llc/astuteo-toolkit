<?php

namespace astuteo\astuteotoolkit\services;

use craft\base\Component;
use craft\helpers\StringHelper;

class ExternalUrlService extends Component {
    public static function cleanUrl($string, $upgrade = false) : string {
        $string = StringHelper::trim($string);
        $containsHttp = StringHelper::contains($string, '://', false);
        if ($containsHttp && !$upgrade) {
            return $string;
        } elseif ($containsHttp && $upgrade) {
            return StringHelper::replaceBeginning($string, 'http://', 'https://');
        }
        $string = StringHelper::replace($string, ':/', '://');
        if(StringHelper::startsWith($string, 'http://', false)) {
            if($upgrade) {
                $string = StringHelper::replaceBeginning($string, 'http://', 'https://');
            }
            return $string;
        }
        if(StringHelper::startsWith($string, 'https://', false)) {
            return $string;
        }
        return 'https://' . $string;
    }
}
