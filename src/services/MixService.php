<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
use Craft;
use craft\base\Component;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class MixService extends Component {
    public static function getManifestUrl($reference) {
        $append = '';
        if(is_array($reference)) {
            $filePath = $reference['file'];
            $append = $reference['append'] ?? '';
        } else {
            $filePath = $reference;
        }
        $web = Craft::getAlias('@webroot');
        $web = StringHelper::trimRight($web, '/');
        $manifest = $web . '/mix-manifest.json';
        if(!file_exists($manifest)) {
            return $filePath;
        }
        $json = self::decodeJsonFile($manifest);
        return self::checkMultiplePathsForKey($json, $filePath, $append);
    }
    private static function decodeJsonFile($file) {
        $contents = file_get_contents($file);
        return Json::decodeIfJson($contents);
    }

    private static function checkMultiplePathsForKey($json, $key, $append = '') {
        if(isset($json[$key])) {
            $match = $json[$key];
            return self::hasParameter($match) ? $match . $append : $match;
        }
        if(isset($json['/' . $key])) {
            $match = $json['/' . $key];
            return self::hasParameter($match) ? $match . $append : $match;
        }
        return $key;
    }

    private static function hasParameter($string) {
        if(strpos($string, '?')) {
            return true;
        }
        return false;
    }
}
