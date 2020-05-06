<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
use craft\base\Component;
use Craft;


class TransformService extends Component {

    public function imgix($image, $options = null, $serviceOptions = null) {
        if (empty($image)) {
           return null;
        }
        $path = $this->prepUrl($image->url, $image->volumeId,AstuteoToolkit::$plugin->getSettings()->imgixUrl);
        $mappedOptions = $this->imgixMap($options,$serviceOptions,$image->focalPoint);
        $assembled = $path . $mappedOptions;
        return $assembled;
    }

    // Processes Imgix to Craft Native where possible
    public function imgixMap($options, $serviceOptions, $focalPoint = null) {
        $params = '';
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

    // Function to return cropped with focalpoint mapped
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


    private function prepUrl($url, $volumeId, $updateUrl) {
        $volume = Craft::$app->volumes->getVolumeById($volumeId);
        $volumeUrl = Craft::parseEnv($volume->url);
        return str_replace($volumeUrl,$updateUrl,$url);
    }
}
