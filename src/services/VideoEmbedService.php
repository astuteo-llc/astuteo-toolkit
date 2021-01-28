<?php

namespace astuteo\astuteotoolkit\services;
use astuteo\astuteotoolkit\AstuteoToolkit;
use craft\base\Component;
use Craft;
use craft\elements\Asset;
use astuteo\astuteotoolkit\helpers\UploadHelper;

use craft\base\Model;
use craft\errors\ElementNotFoundException;
use yii\base\Exception;


/**
 * Class VideoEmbedService
 *
 * @package astuteo\astuteotoolkit\services
 */
class VideoEmbedService extends Component {


    public function getEmbedInfo($url) {
        if(AstuteoToolkit::$plugin->getSettings()->cacheVideoEmbeds && Craft::$app->cache->exists($url)) {
            return Craft::$app->cache->get($url);
        }
        $embedInfo = [
            'id' => '',
            'url' => '',
            'thumbnail' => '',
            'staticThumb' => true
        ];
        if($this->isYouTube($url)) {
            $videoId = $this->getYouTubeId($url);
            if(AstuteoToolkit::$plugin->getSettings()->uploadVideoThumbs && AstuteoToolkit::$plugin->getSettings()->uploadVideoThumbsVolumeId) {
                $volumeId = AstuteoToolkit::$plugin->getSettings()->uploadVideoThumbsVolumeId;
                $this->storeThumbnail($this->createYouTubeThumbnailMax($videoId), $videoId, $volumeId);
                $thumb = $this->getThumbAsset($videoId . '.jpg', $volumeId);
                $staticThumb = false;
            } else {
                $thumb = [
                    'url' => $this->createYouTubeThumbnailMax($videoId)
                ];
                $staticThumb = true;
            }
            $embedInfo = [
              'id' => $videoId,
              'url' => $this->createYouTubeEmbed($videoId),
              'thumbnail' => $thumb,
              'staticThumb' => $staticThumb
            ];

        } elseif($this->isVimeo($url)) {
            $videoId = $this->getVimeoId($url);
            $embedInfo = [
                'id' => $videoId,
                'url' => $this->createVimeoEmbed($videoId),
                'thumbnail' => [
                    'url' => '',
                ],
                'staticThumb' => true
            ];
        }

        if(AstuteoToolkit::$plugin->getSettings()->cacheVideoEmbeds) {
            Craft::$app->cache->set($url, $embedInfo, 'P1Y');
        }
        return $embedInfo;
    }


    public function createYouTubeEmbed($id): string
    {
        return 'https://www.youtube.com/embed/' . $id;
    }
    public function createVimeoEmbed($id): string
    {
        return 'https://player.vimeo.com/video/' . $id;
    }

    public function createYouTubeThumbnail($id): string
    {
        return 'https://i.ytimg.com/vi/' . $id . '/mqdefault.jpg';
    }
    public function createYouTubeThumbnailMax($id): string
    {
        return 'https://i.ytimg.com/vi/' . $id . '/maxresdefault.jpg';
    }


    private function getThumbAsset($filename, $volumeId) {
        $folderId = Craft::$app->assets->getRootFolderByVolumeId($volumeId)['id'];
        return Asset::findOne(['folderId' => $folderId, 'filename' => $filename]);
    }

    private function storeThumbnail($url, $id, $volumeId) {
        $filename = $id . '.jpg';
        $existing = $this->getThumbAsset($filename, $volumeId);
        if(is_object($existing)) {
            return $existing;
        }
        $tempPath = Craft::$app->path->getTempPath();
        $tempFilePath = UploadHelper::downloadFile($url, $tempPath . $filename);
        if($tempFilePath) {
            return UploadHelper::uploadToVolume($volumeId, $tempPath . $filename, $filename);
        }
    }


    /**
     * Is the url a youtube url
     * @param string $url
     * @return boolean
     */
    public function isYouTube($url)
    {
        return (strpos($url, 'youtube.com/') !== false || strpos($url, 'youtu.be/') !== false);
    }
    /**
     * Is the url a vimeo url
     * @param string $url
     * @return boolean
     */
    public function isVimeo($url)
    {
        return strpos($url, 'vimeo.com/') !== FALSE;
    }

    /**
     * Parse the YouTube URL, return the video ID
     * https://gist.github.com/ghalusa/6c7f3a00fd2383e5ef33
     * @param string $url
     * @return string
     */
    public function getYouTubeId($url)
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $match[1];
    }
    /**
     * Parse the Vimeo URL, return the video ID
     * @param string $url
     * @return string
     */
    public function getVimeoId($url)
    {
        preg_match('%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im', $url, $matches);
        return $matches[3];
    }

}
