<?php
/**
 * Astuteo Toolkit plugin for Craft CMS 3.x
 *
 * Various tools that we use across client sites. Only useful for Astuteo projects
 *
 * @link      https://astuteo.com
 * @copyright Copyright (c) 2020 Astuteo
 */

/**
 * Astuteo Toolkit config.php
 *
 * This file exists only as a template for the Astuteo Toolkit settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'astuteo-toolkit.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    "assetPath" => "/site-assets/",
    "imgixUrl" => "",
	"loadCpTweaks" => false,
    "devCpNav" => true,
    "includeFeEdit" => true,
    "phoneFormat" =>  [
        'countryCode' => '+{number} ',
        'areaCode' => '({number}) ',
        'prefix' => '{number}-',
        'lastFour' => '{number}'
    ]
];
