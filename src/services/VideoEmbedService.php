<?php

namespace astuteo\astuteotoolkit\services;
use astuteo\astuteotoolkit\AstuteoToolkit;
use craft\base\Component;
use Craft;


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
            'thumbnail' => ''
        ];

        if($this->isYouTube($url)) {
            $videoId = $this->getYouTubeId($url);
            $embedInfo = [
              'id' => $videoId,
              'url' => $this->createYouTubeEmbed($videoId),
              'thumbnail' => $this->createYouTubeThumbnail($videoId),
              'thumbnailMaxRes' => $this->createYouTubeThumbnailMax($videoId)
            ];
        } elseif($this->isVimeo($url)) {
            $videoId = $this->getVimeoId($url);
            $embedInfo = [
                'id' => $videoId,
                'url' => $this->createVimeoEmbed($videoId),
                'thumbnail' => '',
                'thumbnailMaxRes' => ''
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
