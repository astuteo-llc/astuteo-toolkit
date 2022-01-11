<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
use Craft;
use craft\base\Component;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class MixService extends Component {
    public static function getManifestUrl($reference) {
        $web = Craft::getAlias('@webroot');
        $web = StringHelper::trimRight($web, '/');
        $manifest = $web . '/mix-manifest.json';
        if(!file_exists($manifest)) {
            return $reference;
        }
        $json = self::decodeJsonFile($manifest);
        return self::checkMultiplePathsForKey($json, $reference);
    }
    private static function decodeJsonFile($file) {
        $contents = file_get_contents($file);
        return Json::decodeIfJson($contents);
    }

    private static function checkMultiplePathsForKey($json, $key) {
        if(isset($json[$key])) {
            return $json[$key];
        }
        if(isset($json['/' . $key])) {
            return $json['/' . $key];
        }
       return $key;
    }
}
