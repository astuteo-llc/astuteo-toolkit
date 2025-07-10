<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
use astuteo\astuteotoolkit\helpers\LoggerHelper;
use astuteo\astuteotoolkit\models\Settings;
use craft\base\Component;
use Craft;
use craft\helpers\App;

/**
 * Class AstuteoBuildService
 *
 * @package astuteo\astuteotoolkit\services
 */

class AutoLoginService extends Component {

    public static function login() : bool {
        // check if dev
        $isDev = App::env('ENVIRONMENT') === 'dev' ?? false;
        if(!$isDev) {
            LoggerHelper::warning('Auto-login attempted in non-dev environment');
            return false;
        }
        // check if is console or logged in user request
        $request = Craft::$app->getRequest();
        if ( $request->getIsConsoleRequest() || !Craft::$app->getUser()->getIsGuest() ) {
            LoggerHelper::info('Auto-login skipped: console request or user already logged in');
            return false;
        }
        // see if we have key
        $email = App::env('DEV_AUTO_LOGIN') ?? null;
        // get service
        if(!$email) {
            LoggerHelper::warning('Auto-login failed: DEV_AUTO_LOGIN environment variable not set');
            return false;
        }
        $user = Craft::$app->users->getUserByUsernameOrEmail($email);
        if($user) {
            Craft::$app->user->loginByUserId($user->id);
            LoggerHelper::info('Auto-login successful for user: ' . $email);
            return true;
        }
        LoggerHelper::error('Auto-login failed: User not found with email: ' . $email);
        return false;
    }
}
