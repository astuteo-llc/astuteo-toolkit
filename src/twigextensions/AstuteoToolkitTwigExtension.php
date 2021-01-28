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
        if(is_array($param)) {
            $file = $param[0];
            $asset_path = $param[1];
        } else {
            $file = $param;
            $asset_path = AstuteoToolkit::$plugin->getSettings()->assetPath;
        }
        static $manifest = null;
        $path           = $this->_preparePath($file, $asset_path);
        $manifest_path  = $_SERVER['DOCUMENT_ROOT'];
        $manifest_path .= $asset_path . '/rev-manifest.json';

        if (is_null($manifest) && file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
        }
        if (isset($manifest[$path])) {
            $path = $manifest[$path];
        }
        $path = $this->_addBasePath($path);
        return $asset_path . $path;
    }

    public function astuteoMix($param)
    {
        if(is_array($param)) {
            $file = $param[0];
            $asset_path = $param[1];
        } else {
            $file = $param;
            $asset_path = AstuteoToolkit::$plugin->getSettings()->assetPath;
        }
        static $manifest = null;
        $path           = $this->_preparePath($file, $asset_path, 'mix');
        $manifest_path  = $_SERVER['DOCUMENT_ROOT'];
        $manifest_path .= $asset_path . '/mix-manifest.json';


        if (is_null($manifest) && file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
        }

        if (isset($manifest[$path])) {
            $path = $manifest[$path];
            $path;
        }
        $path = $this->_addBasePath($path);
        return $asset_path . $path;
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

    private function _stripBasePath($path)
    {
        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
        }
        return $path;
    }
}
