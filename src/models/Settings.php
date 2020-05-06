<?php
/***
 * @link      https://www.astuteo.com
 * @copyright Copyright (c) 2019 astuteo
 */
namespace astuteo\astuteotoolkit\models;
use astuteo\astuteotoolkit\AstuteoToolkit;
use Craft;
use craft\base\Model;

class Settings extends Model
{
    public $assetPath = '/site-assets/';
	public $loadCpTweaks = false;
	public $imgixUrl = '';
}
