<?php
/**
 * Astuteo Toolkit plugin for Craft CMS 3.x
 *
 * test
 *
 * @link      https://astuteo.com
 * @copyright Copyright (c) 2021 astuteo
 */

namespace astuteo\astuteotoolkit\console\controllers;

use astuteo\astuteotoolkit\AstuteoToolkit;
use astuteo\astuteotoolkit\services\AstuteoBuildService;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Default Command
 *
 * The first line of this class docblock is displayed as the description
 * of the Console Command in ./craft help
 *
 * Craft can be invoked via commandline console by using the `./craft` command
 * from the project root.
 *
 * Console Commands are just controllers that are invoked to handle console
 * actions. The segment routing is plugin-name/controller-name/action-name
 *
 * The actionIndex() method is what is executed if no sub-commands are supplied, e.g.:
 *
 * ./craft astuteo-toolkit/default
 *
 * Actions must be in 'kebab-case' so actionDoSomething() maps to 'do-something',
 * and would be invoked via:
 *
 * ./craft astuteo-toolkit/default/do-something
 *
 * @author    astuteo
 * @package   AstuteoToolkit
 * @since     2.0.0
 */
class AddController extends Controller
{
    /**
     * Pulls and copies all our build-config. Recommended only for new projects or where existing files are backed up.
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return mixed
     */
    public function actionAll(): bool
    {
        if(!$this->_canRun()) {
            return false;
        }
        (new AstuteoBuildService)->addAll();
        return true;
    }

    /**
     * Pulls down just our Laravel Mix build boilerplate
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return bool
     */
    public function actionMix(): bool
    {
        if(!$this->_canRun()) {
            return false;
        }
        (new AstuteoBuildService)->onlyAddMix();
        return true;
    }

    /**
     * Adds our bin/deploy process
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return bool
     */
    public function actionDeploy(): bool
    {
        if(!$this->_canRun()) {
            return false;
        }
        (new AstuteoBuildService)->onlyAddDeploy();
        return true;
    }

    /**
     * Adds our .github/ files to project
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return bool
     */
    public function actionGithub(): bool
    {
        if(!$this->_canRun()) {
            return false;
        }
        (new AstuteoBuildService)->onlyAddGithub();
        return true;
    }

    /**
     * Adds our dot files (.eslintrc.yml, .csscomb.json, etc) for IDE to project
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return bool
     */
    public function actionEditorFiles(): bool
    {
        if(!$this->_canRun()) {
            return false;
        }
        (new AstuteoBuildService)->onlyAddEditorFiles();
        return true;
    }

    /**
     * Adds and configures /scripts
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return bool
     */
    public function actionScripts(): bool
    {
        if(!$this->_canRun()) {
            return false;
        }
        (new AstuteoBuildService)->OnlyAddScripts();
        return true;
    }

    /**
     * Adds example src/* and template/* files
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return bool
     */
    public function actionSource(): bool
    {
        if(!$this->_canRun()) {
            return false;
        }
        (new AstuteoBuildService)->onlyAddSource();
        return true;
    }

    /**
     * Adds our Build config package
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return bool
     */
    public function actionNpm(): bool
    {
        if(!$this->_canRun()) {
            return false;
        }
        (new AstuteoBuildService)->addNpmOnly();
        return true;
    }

    /**
     * Attempts to migrate our old Blendid project-config.json to Mix version
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return bool
     */
    public function actionMigrateBlendid(): bool
    {
        if(!$this->_canRun()) {
            return false;
        }
        (new AstuteoBuildService)->migrateBlendid();
        return true;
    }

    private function _canRun(): bool
    {
        if(!Craft::$app->config->general->devMode) {
            return false;
        };
        return true;
    }

}
