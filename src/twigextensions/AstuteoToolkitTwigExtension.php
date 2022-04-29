<?php
/**
 * Astuteo Toolkit plugin for Craft CMS 3.x
 *
 * Various tools that we use across client sites. Only useful for Astuteo projects
 *
 * @link      https://astuteo.com
 * @copyright Copyright (c) 2018 Astuteo
 */

namespace astuteo\astuteotoolkit\twigextensions;

use astuteo\astuteotoolkit\AstuteoToolkit;
use astuteo\astuteotoolkit\services\MixService;
use astuteo\astuteotoolkit\services\ExternalUrlService;


use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Craft;


/**
 * @author    Astuteo
 * @package   AstuteoToolkit
 * @since     1.0.0
 */
class AstuteoToolkitTwigExtension extends AbstractExtension
{
    private $base_path;
    // Public Methods
    // =========================================================================

    /**
     * @return string The extension name
     */
    public function getName()
    {
        return 'AstuteoToolkit';
    }

    /**
     * Returns an array of Twig filters, used in Twig templates via:
     *      <link rel="stylesheet" media="screen" href="{{ '/site-assets/css/global.css' | astuteoRev }}"/>
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('astuteoRev', [$this, 'astuteoRev']),
            new TwigFilter('astuteoMix', [$this, 'astuteoMix']),
            new TwigFilter('astuteoMarks', [$this, 'astuteoMarks']),
            new TwigFilter('astuteoPhone', [$this, 'astuteoPhone']),
            new TwigFilter('astuteoExternalUrl', [$this, 'astuteoExternalUrl']),
        ];
    }

    /**
     * Returns an array of Twig functions, used in Twig templates via:
     *
     *      {% set this = someFunction('something') %}
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('astuteoRev', [$this, 'astuteoRev']),
            new TwigFunction('astuteoMix', [$this, 'astuteoMix']),
            new TwigFunction('astuteoMarks', [$this, 'astuteoMarks']),
            new TwigFilter('astuteoPhone', [$this, 'astuteoPhone']),
            new TwigFilter('astuteoExternalUrl', [$this, 'astuteoExternalUrl']),
        ];
    }

    public function astuteoExternalUrl($string) : string {
        return ExternalUrlService::cleanUrl($string);
    }

    public function astuteoPhone($string) {
        $string = trim($string);
        $update = preg_replace_callback('/[+\(]?(\d{1,3})?[ -\)]?[- \(]?(\d{3})[-\)]{0,2}?[ .-x]?(\d{3})[ .-]?(\d{4})/', function($match) {
            return $this->buildPhone($match);
        }, $string);
        return trim($update);
    }

    private function buildPhone($result) {
        $format = AstuteoToolkit::$plugin->getSettings()->phoneFormat;
        $string = '';
        if($result[1]) {
            $string = preg_replace('/{number}/', $result[1], $format['countryCode']);
        }
        if($result[2]) {
            $string = $string. preg_replace('/{number}/', $result[2], $format['areaCode']);
        }
        if($result[3]) {
            $string = $string. preg_replace('/{number}/', $result[3], $format['prefix']);
        }
        if($result[4]) {
            $string = $string. preg_replace('/{number}/', $result[4], $format['lastFour']);
        }

        return $string;
    }

    public function astuteoMarks($text) {
        $marks = [
            '®',
            '™',
            '©'
        ];
        $replace = [
            '<sup class="mark mark-r">®</sup>',
            '<sup class="mark mark-tm">™</sup>',
            '<sup class="mark mark-c">©</sup>'
        ];
        $string = str_replace($marks, $replace, $text);
        return $string;
    }

    /**
     * Append or rename static files from our
     * build system
     *
     * @param $file
     * @return string
     */
    public function astuteoRev($param)
    {
        $file = $this->getAssetFile($param);


        return $this->_processManifest($file, $this->getAssetPathFile($param), 'blendid');
    }

    public function astuteoMix($param)
    {
        return MixService::getManifestUrl($param);
    }

    private function getAssetFile($param) {
        if(is_array($param)) {
            return $param[0];
        }
        return $param;
    }
    private function getAssetPathFile($param) {
        if(is_array($param)) {
            return $param[1];
        }
        return AstuteoToolkit::$plugin->getSettings()->assetPath;
    }

    private function _processManifest($file, $asset_path, $version = 'blendid') {
        $manifest = null;
        $manifest_path  = $_SERVER['DOCUMENT_ROOT'] . $asset_path . '/';
        $manifest_path .= ($version === 'blendid') ? 'rev-manifest.json' : 'mix-manifest.json';

        if(is_null($manifest) && file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
        }
        // if the file is set directly at the correct path, let's process it
        // and stop
        if(isset($manifest[$file])) {
            return $manifest[$file];
        }

        // otherwise, let's try and clean the path and find it based on our
        // base setting
        $path = $this->_preparePath($file, $asset_path, $version);
        if(isset($manifest[$path])) {
            $path = $manifest[$path];
        }
        $path = $this->_addBasePath($path);
        return $this->_cleanPath($asset_path . $path);
    }

    private function _addBasePath($path)
    {
        return $this->base_path . $path;
    }

    private function _preparePath($path, $asset_path, $version = 'blendid') {
        $updatePath = str_replace($asset_path, '', $path);
        $updatePath = $this->_stripBasePath($updatePath);
        if($version === 'blendid') {
            return $updatePath;
        } elseif ($version === 'mix') {
            return '/' . $updatePath;
        }
    }

    private function _cleanPath($path) {
        return str_replace('//', '/', $path);
    }

    private function _stripBasePath($path)
    {
        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
        }
        return $path;
    }
}
