<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
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
            return false;
        }
        // check if is console or logged in user request
        $request = Craft::$app->getRequest();
        if ( $request->getIsConsoleRequest() || !Craft::$app->getUser()->getIsGuest() ) {
            return false;
        }
        // see if we have key
        $email = App::env('DEV_AUTO_LOGIN') ?? null;
        // get service
        if(!$email) {
            return false;
        }
        $user = Craft::$app->users->getUserByUsernameOrEmail($email);
        if($user) {
            Craft::$app->user->loginByUserId($user->id);
            return true;
        }
        return false;
    }
}
