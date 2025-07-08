<?php
namespace astuteo\astuteotoolkit\helpers;

use craft\base\Component;
use Craft;

/**
 * Class ImgixCompatibilityHelper
 *
 * Provides Imgix-compatible transforms using Imager-X or Craft native transforms
 * Handles parameter translation and fallback logic for smooth migration
 *
 * @package astuteo\astuteotoolkit\helpers
 */
class ImgixCompatibilityHelper extends Component
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
     * @param array|null $mainOptions
     * @return array
     */
    public function translateServiceOptions($serviceOptions, $mainOptions) {
        if (empty($serviceOptions)) {
            return [];
        }

        $translatedOptions = [];

        // Map Imgix service options to Imager-X equivalents
        foreach ($serviceOptions as $key => $value) {
            switch ($key) {
                case 'auto':
                    // Handle auto=format,compress
                    if (is_string($value) && strpos($value, 'format') !== false) {
                        $translatedOptions['autoFormat'] = true;
                    }
                    if (is_string($value) && strpos($value, 'compress') !== false) {
                        $translatedOptions['autoCompress'] = true;
                    }
                    break;
                case 'fm':
                    // Format mapping
                    $translatedOptions['format'] = $value;
                    break;
                case 'q':
                    // Quality
                    $translatedOptions['quality'] = $value;
                    break;
                default:
                    // Pass through other options
                    $translatedOptions[$key] = $value;
            }
        }

        return $translatedOptions;
    }

    /**
     * Translate main transform options from Imgix format to Imager-X
     *
     * @param array|null $options
     * @param $image
     * @return array
     */
    public function translateMainOptions($options, $image) {
        if (empty($options)) {
            return [];
        }

        $translatedOptions = [];

        // Map Imgix main options to Imager-X equivalents
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'w':
                    $translatedOptions['width'] = $value;
                    break;
                case 'h':
                    $translatedOptions['height'] = $value;
                    break;
                case 'fit':
                    // Map Imgix fit modes to Imager-X modes
                    switch ($value) {
                        case 'crop':
                            $translatedOptions['mode'] = 'crop';
                            break;
                        case 'clip':
                            $translatedOptions['mode'] = 'fit';
                            break;
                        case 'scale':
                            $translatedOptions['mode'] = 'stretch';
                            break;
                        case 'max':
                            $translatedOptions['mode'] = 'max';
                            break;
                        case 'min':
                            $translatedOptions['mode'] = 'min';
                            break;
                        default:
                            $translatedOptions['mode'] = $value;
                    }
                    break;
                case 'crop':
                    if (is_string($value) && strpos($value, 'focalpoint') !== false) {
                        // Use asset focal point if available
                        if (isset($image->focalPoint)) {
                            $translatedOptions['position'] = $this->focalPointToPosition($image->focalPoint);
                        }
                    } else {
                        // Direct position mapping
                        $translatedOptions['position'] = $value;
                    }
                    break;
                default:
                    // Pass through other options
                    $translatedOptions[$key] = $value;
            }
        }

        return $translatedOptions;
    }

    /**
     * Convert focal point coordinates to position string
     *
     * @param array $focalPoint
     * @return string
     */
    public function focalPointToPosition($focalPoint) {
        if (!is_array($focalPoint) || !isset($focalPoint['x']) || !isset($focalPoint['y'])) {
            return 'center-center';
        }

        $x = $focalPoint['x'];
        $y = $focalPoint['y'];

        // Convert to left/center/right and top/center/bottom
        $xPos = $x < 0.33 ? 'left' : ($x > 0.66 ? 'right' : 'center');
        $yPos = $y < 0.33 ? 'top' : ($y > 0.66 ? 'bottom' : 'center');

        return $yPos . '-' . $xPos;
    }

    /**
     * Fallback to Craft's native image transforms
     *
     * @param $image
     * @param array|null $options
     * @param array|null $serviceOptions
     * @return string|null
     */
    public function fallbackToCraft($image, $options = null, $serviceOptions = null) {
        if (empty($image)) {
            return null;
        }

        // Convert Imgix options to Craft transform parameters
        $transformParams = [];

        // Handle width and height
        if (isset($options['w'])) {
            $transformParams['width'] = $options['w'];
        }
        if (isset($options['h'])) {
            $transformParams['height'] = $options['h'];
        }

        // Handle fit/crop mode
        if (isset($options['fit'])) {
            switch ($options['fit']) {
                case 'crop':
                    $transformParams['mode'] = 'crop';
                    break;
                case 'clip':
                    $transformParams['mode'] = 'fit';
                    break;
                case 'scale':
                    $transformParams['mode'] = 'stretch';
                    break;
                default:
                    $transformParams['mode'] = 'crop';
            }
        } else {
            // Default to crop if width and height are specified
            if (isset($transformParams['width']) && isset($transformParams['height'])) {
                $transformParams['mode'] = 'crop';
            }
        }

        // Handle position/crop
        if (isset($options['crop'])) {
            if ($options['crop'] === 'focalpoint' && isset($image->focalPoint)) {
                $transformParams['position'] = $this->focalPointToPosition($image->focalPoint);
            } else {
                $transformParams['position'] = $options['crop'];
            }
        }

        // Handle format
        if (isset($serviceOptions['fm'])) {
            $transformParams['format'] = $serviceOptions['fm'];
        }

        // Handle quality
        if (isset($serviceOptions['q'])) {
            $transformParams['quality'] = $serviceOptions['q'];
        }

        try {
            // Apply transform using Craft's native transform
            $transformedImage = $image->getUrl($transformParams);
            return $transformedImage;
        } catch (\Exception $e) {
            Craft::error('Craft native transform failed: ' . $e->getMessage(), __METHOD__);
            return $image->url ?? null;
        }
    }
}