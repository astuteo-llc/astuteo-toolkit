<?php
namespace astuteo\astuteotoolkit\helpers;

use craft\base\Component;
use Craft;

/**
 * ImgixCompatibilityHelper
 * 
 * Maps Imgix parameters to Imager-X for seamless transition between services.
 * This helper class provides compatibility between Imgix and Imager-X, allowing
 * you to use Imgix-style parameters with the Imager-X plugin or fall back to
 * Craft's native transforms if Imager-X is not available.
 *
 * @package astuteo\astuteotoolkit\helpers
 * @since 6.0.0
 */
class ImgixCompatibilityHelper extends Component
{
    /**
     * Transform image using Imager-X with Imgix parameter compatibility.
     * 
     * This method takes an image asset and transforms it using Imager-X, translating
     * Imgix-style parameters to the format expected by Imager-X. If Imager-X is not
     * available, it falls back to Craft's native transform functionality.
     * 
     * @param mixed $image The image asset to transform
     * @param array|null $options Main transform options (width, height, fit, etc.)
     * @param array|null $serviceOptions Additional service-specific options (format, quality, effects, etc.)
     * @return string|null The URL of the transformed image, or null if transformation failed
     * @throws \Exception If the transformation fails (caught internally)
     */
    public function imagerX(mixed $image, array $options = null, array $serviceOptions = null): ?string
    {
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
            // Use getPluginInstance() for Craft 4/5 compatibility, fallback to getPlugin() for Craft 3
            $plugin = method_exists(Craft::$app->plugins, 'getPluginInstance') 
                ? Craft::$app->plugins->getPluginInstance('imager-x') 
                : Craft::$app->plugins->getPlugin('imager-x');

            $transformedImage = $plugin->imager->transformImage(
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
     * Auto-select best available transform service.
     * 
     * This method automatically selects the best available image transform service.
     * If Imager-X is available, it will use that; otherwise, it falls back to
     * Craft's native transform functionality.
     * 
     * @param mixed $image The image asset to transform
     * @param array|null $options Main transform options (width, height, fit, etc.)
     * @param array|null $serviceOptions Additional service-specific options (format, quality, effects, etc.)
     * @return string|null The URL of the transformed image, or null if transformation failed
     */
    public function auto(mixed $image, array $options = null, array $serviceOptions = null) {
        if (Craft::$app->plugins->isPluginEnabled('imager-x')) {
            return $this->imagerX($image, $options, $serviceOptions);
        }

        return $this->fallbackToCraft($image, $options, $serviceOptions);
    }

    /**
     * Map Imgix service parameters to Imager-X format.
     * 
     * This method translates Imgix service-specific parameters (like auto, fm, q, etc.)
     * to the format expected by Imager-X. It handles various image adjustments,
     * background settings, and special cases like trim=auto.
     * 
     * @param array|null $serviceOptions The Imgix service options to translate
     * @param array|null $mainOptions The main transform options (used for context in some translations)
     * @return array The translated options in Imager-X format
     */
    private function translateServiceOptions(?array $serviceOptions, ?array $mainOptions): array
    {
        if (empty($serviceOptions)) {
            return [];
        }

        $translatedOptions = [];
        $effects = [];

        foreach ($serviceOptions as $key => $value) {
            switch ($key) {
                case 'auto':
                    if (is_string($value) && str_contains($value, 'format')) {
                        $translatedOptions['autoFormat'] = true;
                    }
                    if (is_string($value) && str_contains($value, 'compress')) {
                        $translatedOptions['autoCompress'] = true;
                    }
                    if (is_string($value) && str_contains($value, 'enhance')) {
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
     * Map Imgix transform parameters to Imager-X format.
     * 
     * This method translates the main Imgix transform parameters (like w, h, fit, etc.)
     * to the format expected by Imager-X. It handles dimensions, cropping modes,
     * positioning, flipping, rotation, and other transform-specific options.
     * 
     * @param array|null $options The Imgix transform options to translate
     * @param mixed $image The image asset (used for focal point information)
     * @return array The translated options in Imager-X format
     */
    private function translateMainOptions(?array $options, mixed $image): array
    {
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
                    if (is_string($value) && str_contains($value, ',')) {
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
     * Convert focal point coordinates to position string.
     * 
     * This method converts the focal point coordinates (x, y values between 0 and 1)
     * to a position string in the format 'top-left', 'center-center', 'bottom-right', etc.
     * This is used for positioning crops based on the focal point of an image.
     * 
     * @param array|null $focalPoint The focal point coordinates with 'x' and 'y' keys
     * @return string The position string in the format 'vertical-horizontal'
     */
    private function focalPointToPosition(?array $focalPoint): string
    {
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
     * Fallback to Craft's native image transforms.
     * 
     * This method is used when Imager-X is not available or when the Imager-X transform fails.
     * It translates Imgix-style parameters to Craft's native transform parameters and
     * applies the transform using Craft's built-in functionality.
     * 
     * @param mixed $image The image asset to transform
     * @param array|null $options Main transform options (width, height, fit, etc.)
     * @param array|null $serviceOptions Additional service-specific options (format, quality, etc.)
     * @return string|null The URL of the transformed image, or null if transformation failed
     * @throws \Exception If the transformation fails (caught internally)
     */
    private function fallbackToCraft(mixed $image, array $options = null, array $serviceOptions = null): ?string
    {
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
