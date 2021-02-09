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
class DefaultController extends Controller
{
    /**
     * Handle astuteo-toolkit/default/setup console commands
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return mixed
     */
    public function actionSetup()
    {
        if(!Craft::$app->config->general->devMode) {
            echo 'only run in dev mode';
            return false;
        };

        $astuteoScript = Craft::$app->path->getVendorPath() . '/astuteo/astuteo-toolkit/src/example.sh';
        $astuteoDest = CRAFT_BASE_PATH . '/astuteo.sh';
        if (!copy($astuteoScript,$astuteoDest)) {
            echo "failed to copy $astuteoScript...\n";
        }
        echo "Added astuteo script. You can run at ./astuteo.sh \n";
        return;
    }
}
