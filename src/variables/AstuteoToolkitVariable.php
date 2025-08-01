<?php
namespace astuteo\astuteotoolkit\variables;
use astuteo\astuteotoolkit\AstuteoToolkit;
use astuteo\astuteotoolkit\services\CookieService;
use astuteo\astuteotoolkit\services\LocationService;
use astuteo\astuteotoolkit\services\ToolkitService;
use astuteo\astuteotoolkit\services\TransformService;
use astuteo\astuteotoolkit\services\VideoEmbedService;
use astuteo\astuteotoolkit\helpers\ImgixCompatibilityHelper;

use Craft;
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;

class AstuteoToolkitVariable
{
    /**
     * @param $name
     * @param bool $default
     * @return mixed
     */
    public function insecureCookie($name, $default = false) {
        return CookieService::insecureCookie($name, $default);
    }

    public function insecureCookieWithKey($name, $key) {
        return CookieService::insecureCookieWithKey($name, $key);
    }

    public function setInsecureCookie($name, $value, $key = null, $expiration = null) {
        return CookieService::setInsecureCookie($name, $value, $key, $expiration);
    }

    public function unsecureCookie($name, $default = false) {
        return self::insecureCookie($name, $default);
    }


    public function imgixTransformMap($image, $options, $serviceOptions) {
        return (new TransformService)->imgix($image,$options,$serviceOptions);
    }

    public function transformImagerX($image, $options = null, $serviceOptions = null) {
        return (new ImgixCompatibilityHelper)->imagerX($image, $options, $serviceOptions);
    }

	/**
	 * @return array
	 */
    public function countries()
    {
		return LocationService::countries();
    }

	/**
	 * @return array
	 */
    public function states() {
		return LocationService::states();
    }

	/**
	 * @return array
	 */
    public function provinces() {
		return LocationService::provinces();
    }

    public function projectVars() {
        return AstuteoToolkit::$plugin->getSettings()->projectVars;
    }

    /**
	 * Returns true or false if user client supports
	 * webp.
	 * @return bool
	 */
	public function clientSupportsWebp(): bool
	{
		$request = Craft::$app->getRequest();
		return $request->accepts('image/webp');
	}


    /**
     * Call it like this:
     *
     *     {{ craft.astuteoToolkit.getVideoEmbedInfo(url) }}
     *
     * @param $url
     * @return array $url, $id, $thumbnail
     */
    public function getVideoEmbedInfo($url): array
    {
        return (new VideoEmbedService)->getEmbedInfo($url);
    }

	/**
	 * Checks for webp support in image driver
	 *
	 * @return bool
	 */
	public function serverSupportsWebp(): bool
	{
		return ToolkitService::hasSupportForWebP();
	}

    /**
     * @param $image
     * @param $area
     * @return array|false
     */
	public function areaToDimensions($image, $area, $maxWidth = null, $maxHeight = null) {
        return TransformService::areaToDimensions($image, $area, $maxWidth, $maxHeight);
    }



    // Standardized way to pull future events.
    // Assumptions made:
    // End date field handle is "endDate"
    // Start date field handle is "startDate"
    public function futureEvents($options = null) {
        if(isset($options['limit'])) {
            $limit = $options['limit'];
        } else {
            $limit = 10;
        }
        if(isset($options['section'])) {
            $section = $options['section'];
        } else {
            $section = 'events';
        }
        if(isset($options['relatedTo'])) {
            $related = $options['relatedTo'];
        } else {
            $related = '';
        }

        $events = Entry::find()
            ->section($section)
            ->orderBy('startDate asc')
            ->relatedTo($related)
            ->all();

        try {
            $now = DateTimeHelper::toDateTime(DateTimeHelper::currentTimeStamp())->format('Ymd');
        } catch (\Exception $e) {
        }
        $futureEntries = array();

        foreach ($events as $event) {
            if ( !empty($event->endDate) ) {
                // if end date set let's use that to compare
                $compareDate = DateTimeHelper::toDateTime( $event->endDate)->format('Ymd');
            } elseif(!empty($event->startDate)) {
                // otherwise let's use the start date
                $compareDate = DateTimeHelper::toDateTime( $event->startDate)->format('Ymd');
            } else {
                return null;
            }
            // now let's see if that's today or in the future, and if so merge the IDs
            if( $compareDate >= $now ) {
                $futureEntries[] = $event->id;
            }
        }

        return Entry::find()
            ->section($section)
            ->id($futureEntries)
            ->orderBy('startDate asc')
            ->limit($limit)
            ->all();
    }
}
