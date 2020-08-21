<?php
/**
 * Astuteo Toolkit plugin for Craft CMS 3.x
 *
 * Various tools that we use across client sites. Only useful for Astuteo projects
 *
 * @link      https://astuteo.com
 * @copyright Copyright (c) 2018 Astuteo
 */

namespace astuteo\astuteotoolkit;

use astuteo\astuteotoolkit\assetbundles\cptweaks\AstuteoToolkitCPAsset;
use astuteo\astuteotoolkit\assetbundles\astuteotoolkit\AstuteoToolkitAsset;
use astuteo\astuteotoolkit\services\TransformService;
use astuteo\astuteotoolkit\twigextensions\AstuteoToolkitTwigExtension;
use astuteo\astuteotoolkit\variables\AstuteoToolkitVariable;
use astuteo\astuteotoolkit\models\Settings;
use astuteo\astuteotoolkit\services\ToolkitService;
use astuteo\astuteotoolkit\services\LocationService;
use astuteo\astuteotoolkit\services\CpNavService;

use Craft;
use craft\base\Model;
use craft\web\View;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;
use craft\events\TemplateEvent;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 *
 * @author    Astuteo
 * @package   AstuteoToolkit
 * @since     1.0.0
 *
 * @property  AstuteoToolkitServiceService $astuteoToolkitService
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class AstuteoToolkit extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * AstuteoToolkit::$plugin
     *
     * @var AstuteoToolkit
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '2.0.4';

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Add in our Twig extensions
        Craft::$app->view->registerTwigExtension(new AstuteoToolkitTwigExtension());

        if (Craft::$app->request->getIsCpRequest()) {
            $this->_bindCpEvents();
            if (!Craft::$app->request->getIsAjax() && !Craft::$app->request->getIsConsoleRequest()) {
                Craft::$app->getView()->registerAssetBundle(AstuteoToolkitCPAsset::class);
            }
        }

        if (Craft::$app->request->getIsSiteRequest()) {
            $this->_bindFrontEndEvents();
        }

        // Register services
        $this->setComponents([
            'toolkit' => ToolkitService::class,
            'location' => LocationService::class,
            'transform' => TransformService::class,
            'cpnav' => CpNavService::class,
        ]);

        // Load our AssetBundle
        if (AstuteoToolkit::$plugin->getSettings()->loadCpTweaks && Craft::$app->getRequest()->getIsCpRequest()) {
            Event::on(
                View::class,
                View::EVENT_BEFORE_RENDER_TEMPLATE,
                function (TemplateEvent $event) {
                    try {
                        Craft::$app->getView()->registerAssetBundle(AstuteoToolkitCPAsset::class);
                    } catch (InvalidConfigException $e) {
                        Craft::error(
                            'Error registering AssetBundle - ' . $e->getMessage(),
                            __METHOD__
                        );
                    }
                }
            );
        }

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('astuteoToolkit', AstuteoToolkitVariable::class);
            }
        );


        /**
         * Logging in Craft involves using one of the following methods:
         *
         * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
         * Craft::info(): record a message that conveys some useful information.
         * Craft::warning(): record a warning message that indicates something unexpected has happened.
         * Craft::error(): record a fatal error that should be investigated as soon as possible.
         */
        Craft::info(
            Craft::t(
                'astuteo-toolkit',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }


    // Private Methods
    // =========================================================================

    private function _shouldLoadAssets() {
        $currentUser = Craft::$app->getUser()->identity ?? null;
        if( Craft::$app->config->general->devMode ||
            ($currentUser && $currentUser->can('accessCp'))) {
            return true;
        }
        return false;
    }

    private function _bindFrontEndEvents() {
        // Add edit entry link to front-end templates
        Event::on(
            View::class,
            View::EVENT_END_BODY,
            function (Event $e)
            {
                $element = Craft::$app->getUrlManager()->getMatchedElement();
                if (!$element) return;

                if (
                    $this->_shouldLoadAssets()
                ) {
                    echo '<a
                            href="' . $element->cpEditUrl . '"
                            class="astuteo-edit-entry"
                            target="_blank"
                            rel="noopener"
                          >Edit Entry</a>';
                }
            }
        );
        if ($this->_shouldLoadAssets() && !Craft::$app->request->getIsAjax() && !Craft::$app->request->getIsConsoleRequest()) {
            Craft::$app->getView()->registerAssetBundle(AstuteoToolkitAsset::class);
        }
    }

    private function _bindCpEvents()
    {
        if(!AstuteoToolkit::$plugin->getSettings()->devCpNav || !Craft::$app->config->general->devMode) {
            return;
        }

        Event::on(
            Cp::class,
            Cp::EVENT_REGISTER_CP_NAV_ITEMS,
            function (RegisterCpNavItemsEvent $event) {
                $event->navItems = cpNavService::instance()->addNav($event->navItems);
                AstuteoToolkit::$plugin->getSettings()->loadCpTweaks;
            }
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     */
    protected function settingsHtml(): string
    {
        try {
            return Craft::$app->view->renderTemplate(
                'astuteo-toolkit/settings',
                [
                    'settings' => $this->getSettings()
                ]
            );
        } catch (LoaderError $e) {
        } catch (RuntimeError $e) {
        } catch (SyntaxError $e) {
        } catch (Exception $e) {
        }
    }
}
