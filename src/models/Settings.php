<?php
/***
 * @link      https://www.astuteo.com
 * @copyright Copyright (c) 2019 astuteo
 */
namespace astuteo\astuteotoolkit\models;
use craft\base\Model;

class Settings extends Model
{
    public $assetPath = '/site-assets/';
	public $loadCpTweaks = false;
	public $devCpNav = true;
	public $imgixUrl = '';
}
