<?php

namespace astuteo\astuteotoolkit\services;

use Craft;

use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use Imagine\Image\ImageInterface;

/**
 * Class ToolkitService
 *
 * @package astuteo\astuteotoolkit\services
 */
class ToolkitService extends Component
{
	public static $imageDriver = 'gd';

	/**
	 * Detects which image driver to use
	 */
	public static function detectImageDriver()
	{
		$extension = mb_strtolower(Craft::$app->getConfig()->getGeneral()->imageDriver);

		if ($extension === 'gd') {
			self::$imageDriver = 'gd';
		} else if ($extension === 'imagick') {
			self::$imageDriver = 'imagick';
		} else { // autodetect
			self::$imageDriver = Craft::$app->images->getIsGd() ? 'gd' : 'imagick';
		}
	}
	/**
	 * @return bool
	 */
	public static function hasSupportForWebP(): bool
	{
		self::detectImageDriver();

		if (self::$imageDriver === 'gd' && \function_exists('imagewebp')) {
			return true;
		}

		if (self::$imageDriver === 'imagick' && (\count(\Imagick::queryFormats('WEBP')) > 0)) {
			return true;
		}

		$config = self::getConfig();

		if ($config->useCwebp && $config->cwebpPath !== '' && file_exists($config->cwebpPath)) {
			return true;
		}

		return false;
	}

}
