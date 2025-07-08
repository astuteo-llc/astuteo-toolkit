<?php
namespace astuteo\astuteotoolkit\helpers;

use craft\base\Component;
use Craft;

/**
 * ImgixCompatibilityHelper
 * 
 * Maps Imgix parameters to Imager-X for seamless transition between services
 */
class ImgixCompatibilityHelper extends Component
{
    /**
     * Transform image using Imager-X with Imgix parameter compatibility
     */
    public function imagerX($image, $options = null, $serviceOptions = null) {
        if (empty($image)) {
            return null;
        }

        if (!Craft::$app->plugins->isPluginEnabled('imager-x')) {
            return $this->fallbackToCraft($image, $options, $serviceOptions);
        }

        $translatedServiceOptions = $this->translateServiceOptions($serviceOptions, $options);
        $translatedOptions = $this->translateMainOptions($options, $image);

        // Merge options, prioritizing mode from serviceOptions if trim=auto was detected
        if (isset($translatedServiceOptions['mode']) && 
            isset($serviceOptions['trim']) && $serviceOptions['trim'] === 'auto') {
            $translatedOptions['mode'] = $translatedServiceOptions['mode'];
            // Remove mode from serviceOptions to avoid duplication
            unset($translatedServiceOptions['mode']);
        }

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
     * Auto-select best available transform service
     */
    public function auto($image, $options = null, $serviceOptions = null) {
        if (Craft::$app->plugins->isPluginEnabled('imager-x')) {
            return $this->imagerX($image, $options, $serviceOptions);
        }

        return $this->fallbackToCraft($image, $options, $serviceOptions);
    }

    /**
     * Map Imgix service parameters to Imager-X format
     */
    public function translateServiceOptions($serviceOptions, $mainOptions) {
        if (empty($serviceOptions)) {
            return [];
        }

        $translatedOptions = [];
        $effects = [];

        foreach ($serviceOptions as $key => $value) {
            switch ($key) {
                case 'auto':
                    if (is_string($value) && strpos($value, 'format') !== false) {
                        $translatedOptions['autoFormat'] = true;
                    }
                    if (is_string($value) && strpos($value, 'compress') !== false) {
                        $translatedOptions['autoCompress'] = true;
                    }
                    if (is_string($value) && strpos($value, 'enhance') !== false) {
                        $effects['enhance'] = true;
                    }
                    break;
                case 'fm':
                    $translatedOptions['format'] = $value;
                    break;
                case 'q':
                    $translatedOptions['quality'] = $value;
                    break;
                // Image adjustments
                case 'blur':
                    $effects['blur'] = $value;
                    break;
                case 'bri':
                    $effects['brightness'] = $value;
                    break;
                case 'con':
                    $effects['contrast'] = $value;
                    break;
                case 'sat':
                    $effects['saturation'] = $value;
                    break;
                case 'hue':
                    $effects['hue'] = $value;
                    break;
                case 'sharp':
                    $effects['sharpen'] = $value;
                    break;
                case 'gam':
                    $effects['gamma'] = $value;
                    break;
                // Background and padding
                case 'bg':
                    $translatedOptions['bgColor'] = $value;
                    break;
                case 'pad':
                    $translatedOptions['allowUpscale'] = (bool)$value;
                    break;
                case 'trim':
                    // CraftTransformer::trim() expects a float, convert or use default
                    if (is_numeric($value)) {
                        $translatedOptions['trim'] = (float)$value;
                    } elseif ($value === 'auto') {
                        // Use a gentler trim value for 'auto'
                        $translatedOptions['trim'] = 0.02; // Gentle fuzz value for white backgrounds
                        // When trim=auto is used, we want to use 'fit' mode to maintain aspect ratio
                        $translatedOptions['mode'] = 'fit';
                    }
                    // If not numeric and not 'auto', don't pass the parameter
                    break;
                default:
                    $translatedOptions[$key] = $value;
            }
        }

        if (!empty($effects)) {
            $translatedOptions['effects'] = $effects;
        }

        return $translatedOptions;
    }

    /**
     * Map Imgix transform parameters to Imager-X format
     */
    public function translateMainOptions($options, $image) {
        if (empty($options)) {
            return [];
        }

        $translatedOptions = [];

        foreach ($options as $key => $value) {
            switch ($key) {
                case 'w':
                    $translatedOptions['width'] = $value;
                    break;
                case 'h':
                    $translatedOptions['height'] = $value;
                    break;
                case 'fit':
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
                        if (isset($image->focalPoint)) {
                            $translatedOptions['position'] = $this->focalPointToPosition($image->focalPoint);
                        }
                    } else {
                        $translatedOptions['position'] = $value;
                    }
                    break;
                case 'rect':
                    if (is_string($value) && strpos($value, ',') !== false) {
                        $parts = explode(',', $value);
                        if (count($parts) === 4) {
                            $translatedOptions['cropZoom'] = [
                                'x' => (int)$parts[0],
                                'y' => (int)$parts[1],
                                'width' => (int)$parts[2],
                                'height' => (int)$parts[3]
                            ];
                        }
                    }
                    break;
                case 'flip':
                    if ($value === 'h') {
                        $translatedOptions['flipHorizontally'] = true;
                    } elseif ($value === 'v') {
                        $translatedOptions['flipVertically'] = true;
                    } elseif ($value === 'hv' || $value === 'vh') {
                        $translatedOptions['flipHorizontally'] = true;
                        $translatedOptions['flipVertically'] = true;
                    }
                    break;
                case 'rot':
                    $translatedOptions['rotate'] = (int)$value;
                    break;
                case 'dpr':
                    $translatedOptions['ratio'] = (float)$value;
                    break;
                default:
                    $translatedOptions[$key] = $value;
            }
        }

        return $translatedOptions;
    }

    /**
     * Convert focal point coordinates to position string
     */
    public function focalPointToPosition($focalPoint) {
        if (!is_array($focalPoint) || !isset($focalPoint['x']) || !isset($focalPoint['y'])) {
            return 'center-center';
        }

        $x = $focalPoint['x'];
        $y = $focalPoint['y'];

        $xPos = $x < 0.33 ? 'left' : ($x > 0.66 ? 'right' : 'center');
        $yPos = $y < 0.33 ? 'top' : ($y > 0.66 ? 'bottom' : 'center');

        return $yPos . '-' . $xPos;
    }

    /**
     * Fallback to Craft's native image transforms
     */
    public function fallbackToCraft($image, $options = null, $serviceOptions = null) {
        if (empty($image)) {
            return null;
        }

        $transformParams = [];

        if (isset($options['w'])) {
            $transformParams['width'] = $options['w'];
        }
        if (isset($options['h'])) {
            $transformParams['height'] = $options['h'];
        }

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
                case 'max':
                    $transformParams['mode'] = 'max';
                    break;
                case 'min':
                    $transformParams['mode'] = 'min';
                    break;
                default:
                    $transformParams['mode'] = 'crop';
            }
        } elseif (isset($transformParams['width']) && isset($transformParams['height'])) {
            $transformParams['mode'] = 'crop';
        }

        if (isset($options['crop'])) {
            if ($options['crop'] === 'focalpoint' && isset($image->focalPoint)) {
                $transformParams['position'] = $this->focalPointToPosition($image->focalPoint);
            } else {
                $transformParams['position'] = $options['crop'];
            }
        }

        if (isset($options['rot'])) {
            $transformParams['rotate'] = (int)$options['rot'];
        }

        if (isset($serviceOptions['fm'])) {
            $transformParams['format'] = $serviceOptions['fm'];
        }

        if (isset($serviceOptions['q'])) {
            $transformParams['quality'] = $serviceOptions['q'];
        }

        try {
            $transformedImage = $image->getUrl($transformParams);
            return $transformedImage;
        } catch (\Exception $e) {
            Craft::error('Craft native transform failed: ' . $e->getMessage(), __METHOD__);
            return $image->url ?? null;
        }
    }
}
