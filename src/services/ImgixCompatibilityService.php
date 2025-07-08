<?php

namespace astuteo\astuteotoolkit\services;

use craft\base\Component;
use Craft;

/**
 * Class ImgixCompatibilityService
 *
 * Provides Imgix-compatible transforms using Imager-X or Craft native transforms
 * Handles parameter translation and fallback logic for smooth migration
 *
 * @package astuteo\astuteotoolkit\services
 */
class ImgixCompatibilityService extends Component
{
    /**
     * Translate and execute Imager-X transform with Imgix compatibility
     *
     * @param $image
     * @param array|null $options
     * @param array|null $serviceOptions
     * @return string|null
     */
    public function imagerX($image, $options = null, $serviceOptions = null) {
        if (empty($image)) {
            return null;
        }

        // Check if Imager-X is available
        if (!Craft::$app->plugins->isPluginEnabled('imager-x')) {
            return $this->fallbackToCraft($image, $options, $serviceOptions);
        }

        // Translate service options (handle Imgix-specific parameters)
        $translatedServiceOptions = $this->translateServiceOptions($serviceOptions, $options);

        // Translate main options if needed
        $translatedOptions = $this->translateMainOptions($options, $image);

        try {
            $transformedImage = Craft::$app->plugins->getPlugin('imager-x')->imager->transformImage(
                $image,
                $translatedOptions,
                $translatedServiceOptions
            );
            return $transformedImage->url ?? $image->url;
        } catch (\Exception $e) {
            Craft::error('Imager-X transform failed: ' . $e->getMessage(), __METHOD__);
            return $this->fallbackToCraft($image, $options, $serviceOptions);
        }
    }

    /**
     * Auto-detect best available transform service
     *
     * @param $image
     * @param array|null $options
     * @param array|null $serviceOptions
     * @return string|null
     */
    public function auto($image, $options = null, $serviceOptions = null) {
        // Priority: Imager-X > Craft Native
        if (Craft::$app->plugins->isPluginEnabled('imager-x')) {
            return $this->imagerX($image, $options, $serviceOptions);
        }

        return $this->fallbackToCraft($image, $options, $serviceOptions);
    }

    /**
     * Translate service options from Imgix format to Imager-X
     *
     * @param array|null $serviceOptions
     * @param array|null $mainOptions (passed by reference to allow modification)
     * @return array
     */
    private function translateServiceOptions($serviceOptions, &$mainOptions) {
        if (!$serviceOptions) {
            return [];
        }

        $translated = [];

        foreach ($serviceOptions as $key => $value) {
            switch ($key) {
                case 'trim':
                    if ($value === 'auto') {
                        // Convert Imgix trim=auto to Imager-X equivalent
                        $translated['trim'] = 0.02; // Gentle trimming threshold
                        $translated['allowUpscale'] = false;

                        // Force fit mode to prevent aggressive cropping like Imgix trim=auto
                        if ($mainOptions && isset($mainOptions['width']) && isset($mainOptions['height'])) {
                            $mainOptions['mode'] = 'fit';
                        }
                    } else {
                        $translated[$key] = (float) $value;
                    }
                    break;
                case 'transformer':
                    $translated[$key] = $value;
                    break;
                default:
                    $translated[$key] = $value;
                    break;
            }
        }

        return $translated;
    }

    /**
     * Translate main options from Imgix format to Imager-X
     *
     * @param array|null $options
     * @param $image
     * @return array
     */
    private function translateMainOptions($options, $image) {
        if (!$options) {
            return [];
        }

        $translated = [];

        // Map common Imgix parameters to Imager-X
        $paramMap = [
            'w' => 'width',
            'h' => 'height',
            'q' => 'jpegQuality',
            'fm' => 'format',
            'fit' => 'mode',
            'crop' => 'position',
            'bg' => 'bgColor',
            'blur' => 'blur',
            'sharp' => 'sharpen',
            'ar' => 'ratio',
        ];

        // Value translations for specific parameters
        $modeTranslations = [
            'crop' => 'crop',
            'fit' => 'fit',
            'fill' => 'stretch',
            'scale' => 'fit',
            'clip' => 'crop'
        ];

        $positionTranslations = [
            'top' => 'top-center',
            'bottom' => 'bottom-center',
            'left' => 'center-left',
            'right' => 'center-right',
            'center' => 'center-center',
            'entropy' => 'entropy',
            'attention' => 'attention',
            'face' => 'face',
            'faces' => 'faces'
        ];

        foreach ($options as $key => $value) {
            if (isset($paramMap[$key])) {
                $newKey = $paramMap[$key];

                // Handle special value translations
                if ($newKey === 'mode' && isset($modeTranslations[$value])) {
                    $translated[$newKey] = $modeTranslations[$value];
                } elseif ($newKey === 'position' && isset($positionTranslations[$value])) {
                    $translated[$newKey] = $positionTranslations[$value];
                } else {
                    $translated[$newKey] = $value;
                }
            } else {
                // Handle special cases
                switch ($key) {
                    case 'mode':
                        if ($value === 'crop' && isset($image->focalPoint)) {
                            $translated['mode'] = 'crop';
                            $translated['position'] = $this->focalPointToPosition($image->focalPoint);
                        } else {
                            $translated['mode'] = $modeTranslations[$value] ?? $value;
                        }
                        break;
                    default:
                        // Pass through Imager-X native parameters
                        $translated[$key] = $value;
                        break;
                }
            }
        }

        return $translated;
    }

    /**
     * Convert focal point to Imager-X position string
     *
     * @param array $focalPoint
     * @return string
     */
    private function focalPointToPosition($focalPoint) {
        if (!$focalPoint || (!isset($focalPoint['x']) && !isset($focalPoint['y']))) {
            return 'center-center';
        }

        $x = $focalPoint['x'] ?? 0.5;
        $y = $focalPoint['y'] ?? 0.5;

        // Convert to percentage string for Imager-X
        return round($x * 100) . '% ' . round($y * 100) . '%';
    }

    /**
     * Fallback to Craft native transforms
     *
     * @param $image
     * @param array|null $options
     * @param array|null $serviceOptions
     * @return string|null
     */
    private function fallbackToCraft($image, $options, $serviceOptions) {
        if (empty($image)) {
            return null;
        }

        $transformOptions = [
            'quality' => 85, // Default quality
        ];

        if ($options) {
            foreach ($options as $key => $value) {
                switch ($key) {
                    case 'w':
                    case 'width':
                        $transformOptions['width'] = (int) $value;
                        break;
                    case 'h':
                    case 'height':
                        $transformOptions['height'] = (int) $value;
                        break;
                    case 'q':
                    case 'quality':
                    case 'jpegQuality':
                        $transformOptions['quality'] = (int) $value;
                        break;
                    case 'fm':
                    case 'format':
                        $transformOptions['format'] = $value;
                        break;
                    case 'fit':
                    case 'mode':
                        $modeMap = [
                            'crop' => 'crop',
                            'fit' => 'fit',
                            'fill' => 'stretch',
                            'stretch' => 'stretch'
                        ];
                        $transformOptions['mode'] = $modeMap[$value] ?? 'crop';
                        break;
                    default:
                        // Pass through other Craft-compatible parameters
                        $transformOptions[$key] = $value;
                        break;
                }
            }
        }

        // Handle service options (skip Imgix-specific ones like trim)
        if ($serviceOptions) {
            foreach ($serviceOptions as $key => $value) {
                if ($key !== 'trim') { // Skip trim as Craft doesn't support it
                    $transformOptions[$key] = $value;
                }
            }
        }

        try {
            return $image->getUrl($transformOptions);
        } catch (\Exception $e) {
            Craft::error('Craft native transform failed: ' . $e->getMessage(), __METHOD__);
            return $image->url; // Last resort: return original
        }
    }
}