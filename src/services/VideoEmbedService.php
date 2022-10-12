<?php

namespace astuteo\astuteotoolkit\services;
use astuteo\astuteotoolkit\AstuteoToolkit;
use craft\base\Component;
use Craft;
use craft\elements\Asset;
use astuteo\astuteotoolkit\helpers\UploadHelper;

/**
 * Class VideoEmbedService
 *
 * @package astuteo\astuteotoolkit\services
 */
class VideoEmbedService extends Component {
    public static function getEmbedInfo($url) {
        if(AstuteoToolkit::$plugin->getSettings()->cacheVideoEmbeds && Craft::$app->cache->exists($url)) {
            return Craft::$app->cache->get($url);
        }
        $embedInfo = [
            'id' => '',
            'url' => '',
            'thumbnail' => '',
            'staticThumb' => true
        ];
        if(self::isYouTube($url)) {
            $videoId = self::getYouTubeId($url);
            if(AstuteoToolkit::$plugin->getSettings()->uploadVideoThumbs && AstuteoToolkit::$plugin->getSettings()->uploadVideoThumbsVolumeId) {
                $volumeId = AstuteoToolkit::$plugin->getSettings()->uploadVideoThumbsVolumeId;
                $thumbLocal = self::storeThumbnail(self::createYouTubeThumbnailMax($videoId), $videoId, $volumeId);
                if($thumbLocal === false) {
                    $thumb = [
                        'url' => self::createYouTubeThumbnailMax($videoId)
                    ];
                    $staticThumb = true;
                } else {
                    $thumb = self::getThumbAsset($videoId . '.jpg', $volumeId);
                    $staticThumb = false;
                }
            } else {
                $thumb = [
                    'url' => self::createYouTubeThumbnailMax($videoId)
                ];
                $staticThumb = true;
            }
            $embedInfo = [
              'id' => $videoId,
              'url' => self::createYouTubeEmbed($videoId),
              'thumbnail' => $thumb,
              'staticThumb' => $staticThumb
            ];

        } elseif(self::isVimeo($url)) {
            $videoId = self::getVimeoId($url);
            $embedInfo = [
                'id' => $videoId,
                'url' => self::createVimeoEmbed($videoId),
                'thumbnail' => [
                    'url' => '',
                ],
                'staticThumb' => true
            ];
        }
        /*
         * Check on various states of caching
         * If we are downloading thumbs, check to make sure all the conditions
         * are met. If we successfully downloaded the thumb, cache the results
         * but don't if we want to download them and it hasn't yet grabbed it from
         * YouTube
         *
         * Note: There is likely a much better way for the conditions.
         */
        if(
            AstuteoToolkit::$plugin->getSettings()->cacheVideoEmbeds
            && isset($videoId)
            && isset($volumeId)
            && AstuteoToolkit::$plugin->getSettings()->uploadVideoThumbs
            && AstuteoToolkit::$plugin->getSettings()->uploadVideoThumbsVolumeId)
            {
                {
                    if(self::getThumbAsset($videoId . 'jpg', $volumeId)) {
                        Craft::$app->cache->set($url, $embedInfo);
                    }
                }
            }
        elseif(AstuteoToolkit::$plugin->getSettings()->cacheVideoEmbeds)
            {
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

    public static function createYouTubeThumbnail($id): string
    {
        return 'https://i.ytimg.com/vi/' . $id . '/mqdefault.jpg';
    }
    public static function createYouTubeThumbnailMax($id): string
    {
        return 'https://i.ytimg.com/vi/' . $id . '/maxresdefault.jpg';
    }


    private static function getThumbAsset($filename, $volumeId) {
        $folderId = Craft::$app->assets->getRootFolderByVolumeId($volumeId)['id'];
        $asset =  Asset::findOne(['folderId' => $folderId, 'filename' => $filename]);
        if($asset) {
            return $asset;
        }
        return false;
    }

    private static function storeThumbnail($url, $id, $volumeId) {
        $filename = $id . '.jpg';
        $existing = self::getThumbAsset($filename, $volumeId);
        if(is_object($existing)) {
            return $existing;
        }
        $tempPath = Craft::$app->path->getTempPath();
        $tempFilePath = (new UploadHelper)->downloadFile($url, $tempPath . '/' . $filename);
        if($tempFilePath) {
            return (new UploadHelper)->uploadToVolume($volumeId,  $tempFilePath, $filename);
        }
        return false;
    }

    /**
     * Is the url a youtube url
     * @param string $url
     * @return boolean
     */
    public static function isYouTube($url)
    {
        return (strpos($url, 'youtube.com/') !== false || strpos($url, 'youtu.be/') !== false);
    }
    /**
     * Is the url a vimeo url
     * @param string $url
     * @return boolean
     */
    public static function isVimeo($url)
    {
        return strpos($url, 'vimeo.com/') !== FALSE;
    }

    /**
     * Parse the YouTube URL, return the video ID
     * https://gist.github.com/ghalusa/6c7f3a00fd2383e5ef33
     * @param string $url
     * @return string
     */
    public static function getYouTubeId($url)
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $match[1];
    }
    /**
     * Parse the Vimeo URL, return the video ID
     * @param string $url
     * @return string
     */
    public static function getVimeoId($url)
    {
        preg_match('%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im', $url, $matches);
        if(!array_key_exists(3, $matches)) {
            return false;
        }
        return $matches[3];
    }

}
