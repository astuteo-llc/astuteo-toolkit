<?php

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
use astuteo\astuteotoolkit\services\AstuteoBuildService;

use Craft;
use craft\base\Model;
use craft\web\View;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use craft\console\Application as ConsoleApplication;
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;
use craft\web\UrlManager;
use craft\events\TemplateEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Plugins;
use yii\base\Event;
use yii\base\InvalidConfigException;

class AstuteoToolkit extends Plugin
{
    public static $plugin;
    public string $schemaVersion = '4.0.0';

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
        });

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $variable = $event->sender;
                $variable->set('astuteoToolkit', AstuteoToolkitVariable::class);
            }
        );

        Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function () {
            Craft::$app->view->registerTwigExtension(new AstuteoToolkitTwigExtension());
            if (Craft::$app->request->getIsCpRequest()) {
                $this->_bindCpEvents();
                if (!Craft::$app->request->getIsAjax() && !Craft::$app->request->getIsConsoleRequest()) {
                    Craft::$app->getView()->registerAssetBundle(AstuteoToolkitCPAsset::class);
                }
            }
        });

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'astuteo\astuteotoolkit\console\controllers';
        }

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['astuteologin'] = 'astuteo-toolkit/auto-login';
            }
        );

        $this->setComponents([
            'toolkit' => ToolkitService::class,
            'location' => LocationService::class,
            'transform' => TransformService::class,
            'cpnav' => CpNavService::class,
            'build' => AstuteoBuildService::class,
        ]);

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
    }

    private function attachEventHandlers(): void
    {
        if (Craft::$app->request->getIsSiteRequest()) {
            $this->_bindFrontEndEvents();
        }
    }

    private function _shouldLoadAssets() {
        $currentUser = Craft::$app->getUser()->identity ?? null;
        if( Craft::$app->config->general->devMode ||
            ($currentUser && $currentUser->can('accessCp'))) {
            return true;
        }
        return false;
    }

    private function _bindFrontEndEvents() {
        if(AstuteoToolkit::$plugin->getSettings()->includeFeEdit) {
            Event::on(
                View::class,
                View::EVENT_END_BODY,
                function (Event $e)
                {
                    $element = Craft::$app->getUrlManager()->getMatchedElement();
                    if (!$element) return;

                    if ($this->_shouldLoadAssets()) {
                        echo '<a
                            href="' . $element->cpEditUrl . '"
                            class="astuteo-edit-entry"
                            target="_blank"
                            rel="noopener"
                          >Edit Entry</a>';
                    }
                }
            );
        }
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

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
