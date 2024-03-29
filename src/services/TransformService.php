<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
use craft\base\Component;
use Craft;

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
        $path = $this->prepUrl($image->url, $image->volumeId,AstuteoToolkit::$plugin->getSettings()->imgixUrl);
        $mappedOptions = $this->imgixMap($options,$serviceOptions,$image->focalPoint);
        return $path . $mappedOptions;
    }

    // Processes Imgix to Craft Native where possible

    /**
     * @param $options
     * @param $serviceOptions
     * @param null $focalPoint
     * @return false|string
     */
    public function imgixMap($options, $serviceOptions, $focalPoint = null) {
        $params = '?';
        if(!$options) {
            return false;
        }
        foreach ($options as  $key => $option) {
            switch ($key) {
                case 'mode':
                    if($option == 'crop') {
                        $imgixParam = $this->imgixMapCrop($focalPoint);
                        break;
                    }
                case 'format':
                    $imgixParam = 'fm=' . $option;
                    break;
                case 'width':
                    $imgixParam = 'w=' . $option;
                    break;
                case 'height':
                    $imgixParam = 'h=' . $option;
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

        return $params;
    }



    /**
     * Calculate Width and Height from target area
     */
    public function areaToDimensions($image, $area, $maxWidth, $maxHeight) {
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
            $width =  round($width * $scale);
        }
        if($maxHeight && $maxHeight < $height) {
            $scale = $maxHeight / $height;
            $height = round($height * $scale);
            $width =  round($width * $scale);
        }
        return [
            'width' => $width,
            'height' => $height
        ];
    }


    /**
     * @param $focalPoint
     * Function to return cropped with focal point mapped
     * @return string
     */
    private function imgixMapCrop($focalPoint) {
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
    private function prepUrl($url, $volumeId, $updateUrl) {
        $volume = Craft::$app->volumes->getVolumeById($volumeId);
        $volumeUrl = Craft::parseEnv($volume->url);
        return str_replace($volumeUrl,$updateUrl,$url);
    }
}
