<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
use craft\base\Component;
use Craft;
use craft\helpers\StringHelper;

/**
 * Class TransformService
 *
 * @package astuteo\astuteotoolkit\services
 */
class TransformService extends Component {

    /**
     * @param $image
     * @param null $options
     * @param null $serviceOptions
     * @return string|null
     */
    public function imgix($image, $options = null, $serviceOptions = null) {
        if (empty($image)) {
            return null;
        }
        
        $path = $this->prepUrl($image, AstuteoToolkit::$plugin->getSettings()->imgixUrl);
        $mappedOptions = $this->imgixMap($options, $serviceOptions, $image->focalPoint);
        $finalUrl = $path . $mappedOptions;
        
        return $finalUrl;
    }

    /**
     * @param $options
     * @param $serviceOptions
     * @param null $focalPoint
     * @return false|string
     */
    public static function imgixMap($options, $serviceOptions, $focalPoint = null) {
        $params = '?';
        if(!$options) {
            return false;
        }
        
        $mode = array_key_exists('mode', $options) ? $options['mode'] : 'crop';
        $options['mode'] = $mode;

        foreach ($options as  $key => $option) {
            switch ($key) {
                case 'mode':
                    if($option == 'crop') {
                        $imgixParam = self::imgixMapCrop($focalPoint);
                        break;
                    }
                    if($option == 'fill' || $option == 'fillmax' ) {
                        $imgixParam = 'fit=' . $option;
                        break;
                    }
                    $imgixParam = 'mode=' . $option;
                    break;
                case 'format':
                    $imgixParam = 'fm=' . $option;
                    break;
                case 'width':
                    $imgixParam = 'w=' . self::handleUnit($option);
                    break;
                case 'height':
                    $imgixParam = 'h=' . self::handleUnit($option);
                    break;
                case 'ratio':
                    if(key_exists('width', $options)) {
                        $rawHeight = $option * $options['width'];
                        $roundedHeight = self::handleUnit($rawHeight);
                        $imgixParam = 'h=' . $roundedHeight;
                        break;
                    }
                    if(key_exists('height', $options)) {
                        $rawWidth = $option * $options['height'];
                        $roundedWidth = self::handleUnit($rawWidth);
                        $imgixParam = 'w=' . $roundedWidth;
                        break;
                    }
                    break;
                default:
                    $imgixParam = $key . '=' . $option;
                    break;
            };
            $params = $params . '&' . $imgixParam;
        }

        if($serviceOptions) {
            foreach ($serviceOptions as $key => $option) {
                $params = $params . '&' . $key . '=' . $option;
            }
        }
        $defaults = AstuteoToolkit::$plugin->getSettings()->imgixDefaultParams;
        if($defaults) {
            foreach ($defaults as $key => $option) {
                if (is_array($options)
                    && is_array($serviceOptions)
                    && !key_exists( $key, $options)
                    && !key_exists( $key, $serviceOptions)) {
                    $params = $params . '&' . $key . '=' . $option;
                }
            }
        }
        return $params;
    }



    public static function handleUnit($value) {
        $rounded = round($value);
        return $rounded;
    }

    /**
     * Calculate Width and Height from target area
     */
    public static function areaToDimensions($image, $area, $maxWidth, $maxHeight) {
        if(!isset($image['width']) || !isset($image['height'])) {
            return false;
        }
        
        $ratio = $image['width'] / $image['height'];
        $y = sqrt($area/$ratio);
        
        $height = round($y);
        $width = round($ratio * $y);

        if($maxWidth && $maxWidth < $width) {
            $scale = $maxWidth / $width;
            $height = round($height * $scale);
            $width = round($width * $scale);
        }
        
        if($maxHeight && $maxHeight < $height) {
            $scale = $maxHeight / $height;
            $height = round($height * $scale);
            $width = round($width * $scale);
        }
        
        return [
            'width' => $width,
            'height' => $height
        ];
    }

    public static function imgixUpgradeSettings($settings) {
        if(!is_string($settings)) {
            return [];
        }
        $settings = StringHelper::trim($settings);
        $updated = StringHelper::split($settings, '&');
        $remap = [];
        foreach($updated as $value) {
            [$k, $v] = StringHelper::explode($value,'=');
            $remap[ $k ] = $v;
        }
        return  $remap;
    }


    /**
     * @param $focalPoint
     * @return string
     */
    private static function imgixMapCrop($focalPoint) {
        $param = 'fit=crop';
        // Default, no need to do more
        if($focalPoint['x'] == 0.5 && $focalPoint['y'] == 0.5) {
            return $param;
        }
        $param = $param . '&crop=focalpoint';
        $param = $param . '&fp-x=' . $focalPoint['x'];
        $param = $param . '&fp-y=' . $focalPoint['y'];
        $param = $param . '&fp-z=1';
        return $param;
    }

    /**
     * @param $url
     * @param $volumeId
     * @param $updateUrl
     * @return string|string[]
     */
    private function prepUrl($image, $updateUrl) {
        $basePath = $image->volume->fs->subfolder;
        $imagePath =  $image->path;
        return trim("{$updateUrl}/{$basePath}/{$imagePath}");
    }
}
