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
        ];
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
        $file = $this->getAssetFile($param);
        if(Craft::$app->cache->exists($file)) {
            return Craft::$app->cache->get($file);
        }
        $revUrl = $this->_processManifest($file, $this->getAssetPathFile($param), 'mix');
        Craft::$app->cache->set($file, $revUrl);
        return $revUrl;
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
        $path = $this->_preparePath($file, $asset_path, $version);
        if($version === 'blendid') {
            $manifest_path  = $_SERVER['DOCUMENT_ROOT'] . $asset_path . '/rev-manifest.json';
        } elseif($version === 'mix') {
            $manifest_path  = $_SERVER['DOCUMENT_ROOT'] . '/mix-manifest.json';
        }

        if(is_null($manifest) && file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
        }
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
        if($version === 'blendid') {
            $updatePath = str_replace($asset_path, '', $path);
            $updatePath = $this->_stripBasePath($updatePath);
            return $updatePath;
        } elseif ($version === 'mix') {
            return $path;
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
