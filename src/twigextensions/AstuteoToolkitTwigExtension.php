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

use Craft;
use craft\base\Component;


/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    Astuteo
 * @package   AstuteoToolkit
 * @since     1.0.0
 */
class AstuteoToolkitTwigExtension extends \Twig_Extension
{
    private $base_path;
    // Public Methods
    // =========================================================================

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'AstuteoToolkit';
    }

    /**
     * Returns an array of Twig filters, used in Twig templates via:
     *
     *      <link rel="stylesheet" media="screen" href="{{ '/site-assets/css/global.css' | astuteoRev }}"/>
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('astuteoRev', [$this, 'astuteoRev']),
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
            new \Twig_SimpleFunction('astuteoRev', [$this, 'astuteoRev']),
        ];
    }


    // Rev stuff
    /**
     * Our function called via Twig; it can do anything you want
     *
     * @param null $text
     *
     * @return string
     */
    public function astuteoRev($file)
    {
        static $manifest = null;

        $asset_path = AstuteoToolkit::$plugin->getSettings()->assetPath;



        $path           = $this->preparePath($file, $asset_path);
        $manifest_path  = $_SERVER['DOCUMENT_ROOT'];
        $manifest_path .= $asset_path . '/rev-manifest.json';


        // print_r('path: ' . $path);
        // print_r('manifest: ' . $manifest_path);

        // looking for rev-manifest file in public folder
        // and storing the contents of the file
        if (is_null($manifest) && file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
        }

        // Find the revved version path of the file in the manifest
        if (isset($manifest[$path])) {
            $path = $manifest[$path];
        }

        // We remove, then re-add the base path since it's not in
        // the keys for each file in the manifest file
        $path = $this->addBasePath($path);
        return $asset_path . $path;
    }
    public function addBasePath($path)
    {
        return $this->base_path . $path;
    }



    public function preparePath($path, $asset_path) {
        $updatePath = str_replace($asset_path, '', $path);
        $updatePath = $this->stripBasePath($updatePath);
        return $updatePath;
    }

    public function stripBasePath($path)
    {
        if ($this->base_path !== '/') {
            // take off the starting slash
            $base_path = substr($this->base_path, 1);

            // remove base path and any possible double-slashes
            $path = str_replace($base_path, '', $path);
            $path = str_replace('//', '/', $path);

            return $path;
        } else {
            // strip off starting slash if it exists
            if (substr($path, 0, 1) === '/') {
                $path = substr($path, 1);
            }

            return $path;
        }
    }

    public function getBasePath($settings)
    {
        if (!$settings || $settings->gulprev_path === '') {
            return '/';
        }


        // if the path doesn't start with a /, add one
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }

        // if the path doesn't end with a /, add one
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }

        return $path;
    }


}
