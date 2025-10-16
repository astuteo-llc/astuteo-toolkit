<?php
namespace astuteo\astuteotoolkit\helpers;

use craft\base\Component;
use Craft;
use craft\helpers\App;
use astuteo\astuteotoolkit\AstuteoToolkit;
use astuteo\astuteotoolkit\helpers\LoggerHelper;

/**
 * ImgixCompatibilityHelper
 *
 * Maps Imgix parameters to Imager-X for seamless transition between services.
 * This helper class provides compatibility between Imgix and Imager-X, allowing
 * you to use Imgix-style parameters with the Imager-X plugin or fall back to
 * Craft's native transforms if Imager-X is not available.
 *
 * @package astuteo\astuteotoolkit\helpers
 * @since 3.3.0
 */
class ImgixCompatibilityHelper extends Component
{
    // Default fuzz values if settings are not provided
    private const TRIM_AUTO_FUZZ_DEFAULT = 0.02;   // Gentle fuzz value for white backgrounds
    private const TRIM_COLOR_FUZZ_DEFAULT = 0.01;  // Small fuzz value to remove color edges

    private function getTrimAutoFuzzSetting(): float
    {
        $settings = AstuteoToolkit::$plugin && AstuteoToolkit::$plugin->getSettings() ? AstuteoToolkit::$plugin->getSettings() : null;
        if ($settings && method_exists($settings, 'getTrimAutoFuzz')) {
            $val = $settings->getTrimAutoFuzz();
            if (is_numeric($val)) {
                $num = (float)$val;
                return max(0.0, min(1.0, $num));
            }
        }
        return self::TRIM_AUTO_FUZZ_DEFAULT;
    }

    private function getTrimColorFuzzSetting(): float
    {
        $settings = AstuteoToolkit::$plugin && AstuteoToolkit::$plugin->getSettings() ? AstuteoToolkit::$plugin->getSettings() : null;
        if ($settings && method_exists($settings, 'getTrimColorFuzz')) {
            $val = $settings->getTrimColorFuzz();
            if (is_numeric($val)) {
                $num = (float)$val;
                return max(0.0, min(1.0, $num));
            }
        }
        return self::TRIM_COLOR_FUZZ_DEFAULT;
    }
    /**
     * Rounds a numeric value to ensure consistent integer dimensions.
     *
     * This method ensures compatibility with Imgix by rounding dimension values
     * to integers, which is important for consistent image transformations.
     *
     * @param mixed $value The value to round
     * @return int The rounded value
     */
    private function handleUnit($value): int
    {
        return round($value);
    }
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
    public function imagerX($image, $options = null, $serviceOptions = null)
    {
        if (empty($image)) {
            return null;
        }
        $settings = AstuteoToolkit::$plugin->getSettings();
        App::maxPowerCaptain();

        if (!Craft::$app->plugins->isPluginEnabled('imager-x') || $settings->getPreferNativeTransforms()) {
            LoggerHelper::warning('Skipping Imgix, either preferNativeTransforms is true, or Imager-x is not installed');
            return $this->fallbackToCraft($image, $options, $serviceOptions);
        }

        // Calculate dimensions based on ratio if needed
        if ($options) {
            $options = $this->calculateDimensionsFromRatio($options);
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
    public function auto($image, $options = null, $serviceOptions = null) {
        // Allow opting out of Imager-X via plugin settings
        $settings = AstuteoToolkit::$plugin->getSettings();
        $preferNative = false;
        if ($settings && method_exists($settings, 'getPreferNativeTransforms')) {
            $preferNative = (bool)$settings->getPreferNativeTransforms();
            if ($preferNative) {
                LoggerHelper::warning('preferNativeTransforms is true');
            }
        }

        if (!$preferNative && Craft::$app->plugins->isPluginEnabled('imager-x')) {
            return $this->imagerX($image, $options, $serviceOptions);
        }

        // Either Imager-X is not enabled or settings prefer Craft native transforms
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
    private function translateServiceOptions($serviceOptions, $mainOptions): array
    {
        if (empty($serviceOptions)) {
            return [];
        }

        $translatedOptions = [];
        $effects = [];

        // List of supported Imgix service params that map to Imager X
        $supportedKeys = [
            'auto', 'fm', 'format', 'q', 'blur', 'bri', 'con', 'sat', 'hue', 'sharp', 'gam', 'bg', 'pad', 'trim', 'fill', 'fill-color'
        ];

        foreach ($serviceOptions as $key => $value) {
            if (!in_array($key, $supportedKeys, true)) {
                continue;
            }
            switch ($key) {
                case 'auto':
                    // PHP 7.4 compatible: use strpos instead of str_contains
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
                case 'format':
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
                case 'fill':
                    $translatedOptions['fill'] = $value;
                    break;
                case 'fill-color':
                    $translatedOptions['fill-color'] = $value;
                    break;
                case 'trim':
                    // Handle different trim values
                    if (is_numeric($value)) {
                        $translatedOptions['trim'] = (float)$value;
                    } elseif ($value === 'auto') {
                        // Use a gentler trim value for 'auto'
                        $translatedOptions['trim'] = $this->getTrimAutoFuzzSetting(); // Gentle fuzz value for white backgrounds
                        // When trim=auto is used, we want to use 'fit' mode to maintain aspect ratio
                        $translatedOptions['mode'] = 'fit';
                    } elseif ($value === 'color') {
                        // For trim=color, use a small trim value to remove color edges
                        $translatedOptions['trim'] = $this->getTrimColorFuzzSetting();
                    }
                    // If not numeric, 'auto', or 'color', don't pass the parameter
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
    private function translateMainOptions($options, $image): array
    {
        if (empty($options)) {
            return [];
        }

        $translatedOptions = [];

        foreach ($options as $key => $value) {
            switch ($key) {
                case 'w':
                case 'width':
                    $translatedOptions['width'] = $this->handleUnit($value);
                    break;
                case 'h':
                case 'height':
                    $translatedOptions['height'] = $this->handleUnit($value);
                    break;
                case 'fit':
                case 'mode':
                    switch ($value) {
                        case 'crop':
                            $translatedOptions['mode'] = 'crop';
                            break;
                        case 'clip':
                        case 'fit':
                            $translatedOptions['mode'] = 'fit';
                            $translatedOptions['position'] = 'center-center';
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
                        case 'fill':
                        case 'fillmax':
                            $translatedOptions['mode'] = 'letterbox';
                            // Use letterbox mode to fit image within dimensions and fill remaining space with background
                            break;
                        default:
                            $translatedOptions['mode'] = $value;
                    }
                    break;
                case 'trim':
                    if (is_numeric($value)) {
                        $translatedOptions['trim'] = (float)$value;
                    } elseif ($value === 'auto') {
                        // Mirror service option handling: gentle auto trim and fit mode
                        $translatedOptions['trim'] = $this->getTrimAutoFuzzSetting();
                        $translatedOptions['mode'] = 'fit';
                    } elseif ($value === 'color') {
                        $translatedOptions['trim'] = $this->getTrimColorFuzzSetting();
                    }
                    // If not recognized, do not pass 'trim' through as a string
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
                    // PHP 7.4 compatible: use strpos instead of str_contains
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
     * Calculate dimensions based on ratio if one dimension is missing.
     *
     * This method calculates the missing dimension (width or height) based on the provided ratio.
     * If both width and height are provided, or if no ratio is provided, the options are returned unchanged.
     * Uses round() to ensure compatibility with Imgix and Imager.
     *
     * @param array $options The options array containing width, height, and ratio
     * @return array The options array with calculated dimensions
     */
    private function calculateDimensionsFromRatio(array $options): array
    {
        // Check if we need to calculate a dimension based on ratio
        $hasRatio = isset($options['ratio']) && is_numeric($options['ratio']);

        if (!$hasRatio) {
            return $options;
        }

        // Check for both Imgix-style ('w') and Imager-X style ('width') parameters
        $width = isset($options['w']) && is_numeric($options['w']) ? $options['w'] :
               (isset($options['width']) && is_numeric($options['width']) ? $options['width'] : null);
        $height = isset($options['h']) && is_numeric($options['h']) ? $options['h'] :
                (isset($options['height']) && is_numeric($options['height']) ? $options['height'] : null);

        // Calculate missing dimension if ratio is provided
        if ($width && !$height) {
            $rawCalculatedHeight = $width * $options['ratio'];
            $calculatedHeight = $this->handleUnit($rawCalculatedHeight);

            $options['h'] = $calculatedHeight;
            $options['height'] = $calculatedHeight;
        } elseif (!$width && $height) {
            $rawCalculatedWidth = $height / $options['ratio'];
            $calculatedWidth = $this->handleUnit($rawCalculatedWidth);
            $options['w'] = $calculatedWidth;
            $options['width'] = $calculatedWidth;
        }

        return $options;
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
    private function focalPointToPosition($focalPoint): string
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
    private function fallbackToCraft($image, $options = null, $serviceOptions = null)
    {
        if (empty($image)) {
            return null;
        }

        App::maxPowerCaptain();

        if ($options) {
            $options = $this->calculateDimensionsFromRatio($options);
        }

        $transformParams = [];

        if (isset($options['w'])) {
            $transformParams['width'] = $this->handleUnit($options['w']);
        }
        if (isset($options['h'])) {
            $transformParams['height'] = $this->handleUnit($options['h']);
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

        // Support both 'format' (native Craft) and 'fm' (Imgix-style)
        if (isset($serviceOptions['format'])) {
            $transformParams['format'] = $serviceOptions['format'];
        } elseif (isset($serviceOptions['fm'])) {
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
