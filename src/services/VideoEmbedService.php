<?php

namespace astuteo\astuteotoolkit\services;
use astuteo\astuteotoolkit\AstuteoToolkit;
use craft\base\Component;
use Craft;
use craft\elements\Asset;
use astuteo\astuteotoolkit\helpers\LoggerHelper;
use astuteo\astuteotoolkit\helpers\UploadHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class VideoEmbedService
 *
 * @package astuteo\astuteotoolkit\services
 */
class VideoEmbedService extends Component {
    public static function getEmbedInfo($url): ?array {
        $settings = AstuteoToolkit::$plugin->getSettings();
        if($settings->cacheVideoEmbeds && Craft::$app->cache->exists($url)) {
            LoggerHelper::info('Retrieved video embed info from cache for: ' . $url);
            return Craft::$app->cache->get($url);
        }
        LoggerHelper::info('Fetching new video embed info for: ' . $url);

        return match (true) {
            self::isYouTube($url) => self::getYouTubeEmbedInfo($url, $settings),
            self::isVimeo($url) => self::getVimeoEmbedInfo($url, $settings),
            self::isWistia($url) => self::getWistiaEmbedInfo($url, $settings),
            default => [
                'id' => '',
                'url' => '',
                'thumbnail' => '',
                'staticThumb' => true,
                'provider' => 'unknown',
            ],
        };
    }
    private static function getYouTubeEmbedInfo(string $url, $settings): ?array {
        $videoId = self::getYouTubeId($url);
        $embedInfo = [
            'id' => $videoId,
            'url' => self::createYouTubeEmbed($videoId),
            'provider' => 'youtube',
        ];

        if($settings->uploadVideoThumbs && $settings->uploadVideoThumbsVolumeId) {
            $volumeId = $settings->uploadVideoThumbsVolumeId;
            $thumbLocal = self::storeThumbnail(self::createYouTubeThumbnailMax($videoId), $videoId, $volumeId);

            $embedInfo['thumbnail'] = $thumbLocal !== false
                ? self::getThumbAsset($videoId . '.jpg', $volumeId)
                : ['url' => self::createYouTubeThumbnailMax($videoId)];

            $embedInfo['staticThumb'] = $thumbLocal === false;
        } else {
            $embedInfo['thumbnail'] = ['url' => self::createYouTubeThumbnailMax($videoId)];
            $embedInfo['staticThumb'] = true;
        }

        if($settings->cacheVideoEmbeds && isset($videoId, $volumeId) && $settings->uploadVideoThumbs && $settings->uploadVideoThumbsVolumeId) {
            if(self::getThumbAsset($videoId . 'jpg', $volumeId)) {
                Craft::$app->cache->set($url, $embedInfo);
            }
        } elseif($settings->cacheVideoEmbeds) {
            Craft::$app->cache->set($url, $embedInfo);
        }

        return $embedInfo;
    }

    private static function getVimeoEmbedInfo(string $url, $settings): ?array {
        $videoId = self::getVimeoId($url);
        $embedInfo = [
            'id' => $videoId,
            'url' => self::createVimeoEmbed($videoId),
            'thumbnail' => [
                'url' => self::createVimeoThumbnail($videoId),
            ],
            'staticThumb' => true,
            'provider' => 'vimeo',
        ];

        if($settings->cacheVideoEmbeds) {
            Craft::$app->cache->set($url, $embedInfo);
        }

        return $embedInfo;
    }

    private static function getWistiaEmbedInfo(string $url, $settings): ?array {
        $videoId = self::getWistiaId($url);
        $embedInfo = [
            'id' => $videoId,
            'url' => self::createWistiaEmbed($videoId),
            'thumbnail' => [
                'url' => '',
            ],
            'staticThumb' => true,
            'provider' => 'wistia',
        ];

        if($settings->cacheVideoEmbeds) {
            Craft::$app->cache->set($url, $embedInfo);
        }

        return $embedInfo;
    }

    public static function createYouTubeEmbed($id): string
    {
        return 'https://www.youtube.com/embed/' . $id;
    }
    public static function createVimeoEmbed($id): string
    {
        return 'https://player.vimeo.com/video/' . $id;
    }
    public static function createWistiaEmbed($id): string
    {
        return 'https://fast.wistia.net/embed/iframe/' . $id;
    }



    public static function createYouTubeThumbnailMax($id): string | bool
    {
        $thumbnail = self::thumbnailExists('https://i.ytimg.com/vi/' . $id . '/maxresdefault.jpg');
        if($thumbnail) return $thumbnail;
        $thumbnail = self::thumbnailExists('https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg');
        if($thumbnail) return $thumbnail;
        $thumbnail = self::thumbnailExists('https://i.ytimg.com/vi/' . $id . '/default.jpg');
        if($thumbnail) return $thumbnail;
        return false;
    }

    private static function thumbnailExists($url): bool | string
    {
        try {
            $response = Craft::$app->api->client->get($url);
            $code = $response->getStatusCode();
            if($code == 200) {
                return $url;
            }
        } catch (GuzzleException $e) {
            LoggerHelper::error('Failed to check if thumbnail exists at ' . $url . ': ' . $e->getMessage());
            return false;
        }
        return false;
    }


    public static function createVimeoThumbnail($id) : string {
        $api = self::getVimeoApi($id);
        if(!$api) {
            return '';
        }
        if (array_key_exists('thumbnail_url', $api)) {
            return $api['thumbnail_url'];
        }
        return '';
    }


    private static function getVimeoApi($id) {
        $url = "//vimeo.com/api/oembed.json?url=https://vimeo.com/" . $id;
        try {
            $response = Craft::$app->api->client->get($url);
            $body = $response->getBody();
            $string = StringHelper::toString($body);
            return Json::decodeIfJson($string);
        } catch (GuzzleException $e) {
            LoggerHelper::error('Failed to get Vimeo API data for ID ' . $id . ': ' . $e->getMessage());
            return false;
        }
    }

    private static function getThumbAsset($filename, $volumeId): bool|Asset
    {
        $folderId = Craft::$app->assets->getRootFolderByVolumeId($volumeId)['id'];
        $asset =  Asset::findOne(['folderId' => $folderId, 'filename' => $filename]);
        if($asset) {
            return $asset;
        }
        return false;
    }

    private static function storeThumbnail($url, $id, $volumeId): bool|Asset
    {
        $filename = $id . '.jpg';
        $existing = self::getThumbAsset($filename, $volumeId);
        if(is_object($existing)) {
            return $existing;
        }
        $tempPath = Craft::$app->path->getTempPath();
        $tempFilePath = (new UploadHelper)->downloadFile($url, $tempPath . '/' . $filename);
        if($tempFilePath) {
            $result = (new UploadHelper)->uploadToVolume($volumeId, $tempFilePath, $filename);
            if (!$result) {
                LoggerHelper::error('Failed to upload thumbnail to volume: ' . $filename);
            } else {
                LoggerHelper::info('Successfully stored thumbnail for video ID: ' . $id);
            }
            return $result;
        }
        LoggerHelper::error('Failed to download thumbnail from: ' . $url);
        return false;
    }

    /**
     * Is the url a youtube url
     * @param string $url
     * @return boolean
     */
    public static function isYouTube(string $url): bool
    {
        return (str_contains($url, 'youtube.com/') || str_contains($url, 'youtu.be/') || str_contains($url, 'youtube-nocookie.com/'));
    }

    /**
     * Is the url a vimeo url
     * @param string $url
     * @return boolean
     */
    public static function isVimeo(string $url): bool
    {
        return str_contains($url, 'vimeo.com/');
    }

    /**
     * Is the url a Wistia url
     * @param string $url
     * @return boolean
     */
    public static function isWistia(string $url): bool
    {
        return (str_contains($url, 'wistia.com/') || str_contains($url, 'wistia.net/'));
    }

    /**
     * Parse the YouTube URL, return the video ID
     * Covers these URL formats:
     * - youtube.com/v/abc123
     * - youtube.com/v/abc123?version=3&autohide=1
     * - youtube.com/embed/abc123
     * - youtube.com/embed/abc123?rel=0
     * - youtube.com/watch?v=abc123
     * - youtube.com/watch?v=abc123&feature=related
     * - youtube.com/watch?v=abc123&playnext=1&list=abc123&feature=results_main
     * - youtube.com/watch?v=abc123&feature=youtu.be
     * - youtube.com/watch?feature=endscreen&v=abc123&NR=1
     * - youtube.com/watch?feature=player_embedded&v=abc123
     * - youtube.com/watch?feature=player_detailpage&v=abc123#t=31s
     * - youtu.be/abc123
     * - youtu.be/abc123?feature=youtu.be
     * - youtube.com/user/abc123#p/u/1/abc123
     * - youtube.com/user/abc123?feature=watch
     * - youtube.com/user/abc123?v=abc123
     * - youtube.com/user/abc123?v=abc123&feature=related
     * - youtube-nocookie.com/v/abc123?version=3&autohide=1
     * - youtube.com/c/ChannelName/live
     * @param string $url
     * @return bool|string
     */
    public static function getYouTubeId(string $url): bool|string
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/|youtube.com/c/.*?/live)([^"&?/ ]{11})%i', $url, $match);
        if (isset($match[1])) {
            LoggerHelper::info('Successfully extracted YouTube ID: ' . $match[1] . ' from URL: ' . $url);
            return $match[1];
        }
        preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matchesLive);
        if (isset($matchesLive[1])) {
            LoggerHelper::info('Successfully extracted YouTube ID (live): ' . $matchesLive[1] . ' from URL: ' . $url);
            return $matchesLive[1];
        }
        LoggerHelper::warning('Failed to extract YouTube ID from URL: ' . $url);
        return false;
    }

    /**
     * Parse the Vimeo URL, return the video ID
     * Covers these URL formats:
     * - vimeo.com/abc123
     * - vimeo.com/abc123?foo=bar
     * - vimeo.com/channels/abc123
     * - vimeo.com/channels/abc123/abc123
     * - vimeo.com/groups/abc123/videos/abc123
     * - vimeo.com/album/abc123/video/abc123
     * - player.vimeo.com/video/abc123
     * - vimeo.com/video/abc123
     * - vimeo.com/video/abc123?foo=bar
     * @param string $url
     * @return bool|string
     */
    public static function getVimeoId(string $url): bool|string
    {
        preg_match('%^https?://(?:www\.|player\.)?vimeo.com/(?:channels/(?:\w+/)?|groups/([^/]*)/videos/|album/(\d+)/video/|video/|)(\d+)(?:$|/|\?)(?:[?]?.*)$%im', $url, $matches);
        if (isset($matches[3])) {
            LoggerHelper::info('Successfully extracted Vimeo ID: ' . $matches[3] . ' from URL: ' . $url);
            return $matches[3];
        }

        // A simpler fallback to handle any other possible Vimeo URL formats.
        preg_match('/vimeo.*\/(\d+)/i', $url, $fallbackMatches);
        if (isset($fallbackMatches[1])) {
            LoggerHelper::info('Successfully extracted Vimeo ID (fallback): ' . $fallbackMatches[1] . ' from URL: ' . $url);
            return $fallbackMatches[1];
        }

        LoggerHelper::warning('Failed to extract Vimeo ID from URL: ' . $url);
        return false;
    }

    /**
     * Parse the Wistia URL, return the video ID
     * Covers these URL formats:
     * - yoursite.wistia.com/medias/abc123
     * - fast.wistia.net/embed/iframe/abc123
     * @param string $url
     * @return bool|string
     */
    public static function getWistiaId(string $url): bool|string
    {
        // Handle formats like: https://yoursite.wistia.com/medias/1n5odqzvb9
        preg_match('/wistia\.com\/medias\/([a-zA-Z0-9]+)/', $url, $matches);
        if (isset($matches[1])) {
            LoggerHelper::info('Successfully extracted Wistia ID: ' . $matches[1] . ' from URL: ' . $url);
            return $matches[1];
        }

        // Handle formats like: https://fast.wistia.net/embed/iframe/1n5odqzvb9
        preg_match('/wistia\.net\/embed\/iframe\/([a-zA-Z0-9]+)/', $url, $matches);
        if (isset($matches[1])) {
            LoggerHelper::info('Successfully extracted Wistia ID (iframe): ' . $matches[1] . ' from URL: ' . $url);
            return $matches[1];
        }

        LoggerHelper::warning('Failed to extract Wistia ID from URL: ' . $url);
        return false;
    }
}
