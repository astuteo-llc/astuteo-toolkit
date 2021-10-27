<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
use astuteo\astuteotoolkit\models\Settings;
use craft\base\Component;
use Craft;
use craft\helpers\Console;
use craft\test\console\ConsoleTest;
use craft\helpers\FileHelper;
use craft\helpers\App;

/**
 * Class AstuteoBuildService
 *
 * @package astuteo\astuteotoolkit\services
 */

class AstuteoBuildService extends Component {
    private static $exampleRepo = 'https://github.com/astuteo-llc/build-config.git';
    private static $referencePath = CRAFT_BASE_PATH . '/a-tmp/';
    private static $examplePaths = [
        "mix" => [
            "reference/config/mix/project-config.js" => "config/mix/project-config.js",
            "reference/config/mix/safelist.js" => "config/mix/safelist.js",
            "reference/config/mix/sample-local-config.js" => "config/mix/sample-local-config.js",
            "example.webpack.mix.js" => "webpack.mix.js",
            "example.package.json" => "package.json",
            "reference/.nvmrc" => ".nvmrc",
            "example.tailwind.config.js" => "tailwind.config.js",
            "reference/.browserslistrc" => ".browserslistrc"
        ],
        "editorFiles" => [
            "reference/.csscomb.json" => ".csscomb.json",
            "reference/babel.config.js" => "babel.config.js",
            "reference/stylelint.config.js" => "stylelint.config.js",
            "reference/.prettierignore" => ".prettierignore",
        ],
        "github" => [
            "reference/.github" => ".github",
        ],
        "migrateBlendid" => [
          "reference/config/mix/migrate.blendid.text" => "config/mix/project-config.js"
        ],
        "blendidSource" => CRAFT_BASE_PATH . "/config/build/path-config.json",
        "deploy" => [
            "reference/bin/deploy" => "bin/deploy",
            "reference/config/deploy.conf" => "config/deploy.conf",
        ],
        "npm" => [
            "@astuteo/prettier-config",
            "@astuteo/build-config@latest",
            "tailwindcss",
            "alpinejs"
        ],
        "src" => [
            "reference/src" => "./src",
            "reference/templates" => "./templates"
        ],
        "scriptsDir" => [
            "reference/scripts" => "./scripts",
        ],
        "scripts" => [
            "reference/scripts/example.local.env.sh" => "scripts/.env.sh"
        ]
    ];

    public function addAll() {
        Console::clearScreen();
        Console::outputWarning('Continuing could remove existing mix, src, deploy or script files. Be sure to commit changes.');
        $accept = Console::prompt('Ready to continue? [y/n]: ');
        if($accept === 'y') {
            Console::clearScreen();
            self::_cloneReference();
            self::addMix();
            self::addSource();
            self::addDeploy();
            self::addScripts();
            if(Console::prompt('Do you want to add our NPM packages now? [y/n]: ') === 'y') {
                self::addNpmPackages();
            }
            self::_cleanUp();
        }
        return false;
    }

    public function onlyAddMix() {
        self::_cloneReference();
        self::addMix();
        self::_cleanUp();
    }

    public function onlyAddEditorFiles() {
        self::_cloneReference();
        self::addEditorFiles();
        self::_cleanUp();
    }

    public function onlyAddDeploy() {
        self::_cloneReference();
        self::addDeploy();
        self::_cleanUp();
    }

    public function onlyAddGithub() {
        self::_cloneReference();
        self::addGithub();
        self::_cleanUp();
    }


    public function onlyAddSource() {
        self::_cloneReference();
        self::addSource();
        self::_cleanUp();
    }

    public function onlyAddScripts() {
        self::_cloneReference();
        self::addScripts();
        self::_cleanUp();
    }

    public function addNpmOnly() {
        self::addNpmPackages();
    }

    public function addSource() {
        self::_sectionMessage('ADD SRC & TEMPLATE DIRECTORIES');
        $dirs = self::$examplePaths['src'];
        foreach ($dirs as $key => $value) {
            self::_checkDir($value);
            self::_copyDirs($key, $value);
        }
        self::_doneMessage('Template and src directories copied');
    }
    public function addDotfiles() {
        $dirs = self::$examplePaths['dotFiles'];
        foreach ($dirs as $key => $value) {
            self::_copyDirs($key, $value);
        }
    }

    private function _copyDirs($src, $dest) {
        FileHelper::copyDirectory(self::$referencePath . $src, CRAFT_BASE_PATH . '/' . $dest);
    }

    public function addScripts() {
        self::_sectionMessage('ADD & CONFIGURE SCRIPTS');
        self::_checkDir('scripts');

        // copy over folder
        $dirs = self::$examplePaths['scriptsDir'];
        foreach ($dirs as $key => $value) {
            self::_copyDirs($key, $value);
        }

        $productionDefaults = AstuteoToolkit::$plugin->getSettings()->productionDefaults;

        // process .env.sh to replace values
        $localPassword = Console::prompt('Local db password: ');
        $assetPath = Console::prompt('Asset path for both local and live: ', $options = [ "default" => "public_html/uploads/"]);
        $publicPath = Console::prompt('Local public path: ', $options = [ "default" => "public_html"]);
        $backups = Console::prompt('Local backup path?: ', $options = [ "default" => "backups"]);
        $remoteLogin = Console::prompt('Remote login: ', $options = [ "default" => $productionDefaults["user"] . "@" . $productionDefaults["ip"]]);
        $remoteSSH = Console::prompt('Remote SSH port: ', $options = [ "default" => "22"]);
        $remotePath  = Console::prompt('Remote root path: ', $options = [ "default" => $productionDefaults["path"]]);
        $remoteDb = Console::prompt('Remote database name: ', $options = [ "default" => $productionDefaults["dbname"]]);
        $remoteUser = Console::prompt('Remote username: ', $options = [ "default" => $productionDefaults["dbuser"]]);
        $remotePassword = Console::prompt('Remote database password: ');
        $remoteBackup = Console::prompt('Full-path remote backup: ', $options = [ "default" => self::_checkEndSlash($remotePath) . 'backups']);

        $replace = [
            "localPassword" => $localPassword,
            "assetPath" => self::_checkEndSlash($assetPath),
            "publicPath" => $publicPath,
            "backups" => self::_checkEndSlash($backups),
            "remotePassword" => $remotePassword,
            "remoteLogin" => $remoteLogin,
            "remoteSSH" => $remoteSSH,
            "remotePath" => self::_checkEndSlash($remotePath),
            "remoteDb" => $remoteDb,
            "remoteUser" => $remoteUser,
            "remoteBackup" => self::_checkEndSlash($remoteBackup)
        ];
        $files = self::$examplePaths['scripts'];
        self::_addFiles($files, $replace);
        shell_exec('chmod +x ./scripts/*');
        self::_doneMessage('Scripts copied to /scripts');
    }

    private function _checkEndSlash($path) {
        $path = trim($path);
        if(substr($path, -1) === '/') {
            return $path;
        }
        return $path . '/';
    }


    public function addNpmPackages() {
        $packages= self::$examplePaths['npm'];
        shell_exec('nvm use');
        foreach ($packages as $package) {
            Console::stdout( PHP_EOL . 'Adding ' . $package . PHP_EOL, Console::FG_GREEN);
            $accept = Console::prompt('Add ' . $package . ' ? [y/n]: '. PHP_EOL);
            if($accept === 'y') {
                shell_exec('yarn add ' . $package);
            }
        }
    }


    public function addGithub() {
        $dirs = self::$examplePaths['github'];
        foreach ($dirs as $key => $value) {
            self::_checkDir($value);
            self::_copyDirs($key, $value);
        }
        self::_doneMessage('Github files copied to .github');
        Console::outputWarning( 'NOTE: Currently the deploy files sample content must be manually updated with server credentials', Console::FG_RED);
    }

    public function addDeploy() {
        self::_sectionMessage('CONFIG BIN/DEPLOY');
        self::_checkDir('config', false);
        self::_checkDir('bin');

        $productionDefaults = AstuteoToolkit::$plugin->getSettings()->productionDefaults;
        $stagingDefaults = AstuteoToolkit::$plugin->getSettings()->stagingDefaults;

        $stagingIp = Console::prompt('Staging IP address? ', $options = [ "default" => $stagingDefaults["ip"]]);
        $stagingPath = Console::prompt('Staging Project Root? ', $options = [ "default" => $stagingDefaults["path"]]);
        $projectGit = Console::prompt('Project GitHub? (user/repo.git) ', $options = [ "default" => "astuteo-llc/project.git"]);
        $productionIp = Console::prompt('Production IP address? ', $options = [ "default" => $productionDefaults["ip"]]);
        $productionPath = Console::prompt('Production Project Root? ', $options = [ "default" => $productionDefaults["path"]]);
        $phpVersion = Console::prompt('Production PHP Version? ', $options = [ "default" => $productionDefaults["php"]]);

        $replace = [
            "stagingIp" =>  $stagingIp ? $stagingIp : 'STAGING IP',
            "stagingPath" => $stagingPath ? $stagingIp : 'STAGING PATH',
            "projectGit" => $projectGit ? $projectGit : 'astuteo-llc/project.git',
            "productionIp" => $productionIp ? $productionIp : 'PRODUCTION IP',
            "productionPath" => $productionPath ? $productionPath : 'PRODUCTION PATH',
            "phpVersion" => $phpVersion
        ];

        $files = self::$examplePaths['deploy'];
        self::_addFiles($files, $replace);
        self::_doneMessage('Deploy files added to bin/deploy and config/deploy.conf');
    }

    public function addMix() {
        self::_sectionMessage('ADD LARAVEL MIX');
        self::_checkDir('config', false);
        self::_checkDir('config/mix');
        $localDomain = Console::prompt('Local Domain? ', $options = [ "default" => self::_getLocalUrl()]);
        $replace = [
          'localDomain' => $localDomain
        ];
        $files = self::$examplePaths['mix'];
        self::_addFiles($files, $replace);
        self::_doneMessage('Laravel Mix files added');
    }


    public function addEditorFiles() {
            self::_sectionMessage('ADD EDITOR FILES');
            $files = self::$examplePaths['editorFiles'];
            self::_addFiles($files);
            self::_doneMessage('Editor files added');
    }

    public function migrateBlendid() {
        self::_sectionMessage('MIGRATE BLENDID');
        self::_cloneReference();
        $files = self::$examplePaths['migrateBlendid'];
        $config = self::$examplePaths['blendidSource'];
        $configJson = json_decode(self::_getFileContents($config));

        $srcDir         = self::_cleanBlendidPath($configJson->src);
        $dest           = self::_cleanBlendidPath($configJson->dest);
        $assets         = self::_cleanBlendidPath($configJson->assets);
        $templates      = self::_cleanBlendidPath($configJson->templates);
        $javascripts    = self::_cleanBlendidPath($configJson->javascripts);
        $stylesheets    = self::_cleanBlendidPath($configJson->stylesheets);
        $static         = self::_cleanBlendidPath($configJson->static);
        $images         = self::_cleanBlendidPath($configJson->images);
        $fonts          = self::_cleanBlendidPath($configJson->fonts);
        $icons          = self::_cleanBlendidPath($configJson->icons);


        $sassFiles      = Console::prompt('What are your main Sass entry files? (e.g. app.scss, forum.scss) ', $options = [ "default" => 'app.scss']);
        $jsFiles        = Console::prompt('What are your main Javascript entry files? (e.g. app.js, contact.js) ', $options = [ "default" => 'app.js']);
        $webPub         = Console::prompt('Webroot directory? (e.g. public_html) ', $options = [ "default" => 'public_html']);

        $baseDest = $assets;

        $replace = [
            'localDomain' => self::_getLocalUrl(),
            'destPublic' => $webPub,
            'destCss' => self::_convertDest($baseDest, $stylesheets->dest),
            'destJs' => self::_convertDest($baseDest, $javascripts->dest),
            'destImages' => self::_convertDest($baseDest, $images->dest),
            'destFonts' => self::_convertDest($baseDest, $fonts->dest),
            'jsFiles' => self::_convertSrc($javascripts->src, $srcDir, $dest, $jsFiles),
            'sassFiles' => self::_convertSrc($stylesheets->src, $srcDir, $dest, $sassFiles),
            'imageDirectories' =>  self::_convertSrc($images->src, $srcDir, $dest),
            'fontDirectories' =>  self::_convertSrc($fonts->src, $srcDir, $dest),
            'staticDirectories' => self::_convertSrc($static->src, $srcDir, $dest),
            'watchFiles' => self::_convertWatch($templates, $javascripts->src, $stylesheets->src, $srcDir),
            'purge' => ''
        ];
        self::_addFiles($files, $replace);
        self::_doneMessage('Blendid project-path.json migrated to project-config.js');
    }

    private function _convertDest($assetDir, $destDir) {
        return '"' . $assetDir . '/' . $destDir . '"';
    }

    private function _sectionMessage($string) {
        Console::stdout( PHP_EOL, Console::FG_CYAN);
        Console::stdout('************************' . PHP_EOL, Console::FG_CYAN);
        Console::stdout($string . PHP_EOL, Console::FG_CYAN);
        Console::stdout('************************' . PHP_EOL, Console::FG_CYAN);
        Console::stdout( PHP_EOL, Console::FG_CYAN);
    }

    private function _doneMessage($string) {
        Console::stdout( PHP_EOL, Console::FG_GREEN);
        Console::stdout('[✓] ' . $string . PHP_EOL, Console::FG_GREEN);
        Console::stdout( PHP_EOL, Console::FG_CYAN);
    }

    private function _getLocalUrl() {
        return App::env('PRIMARY_SITE_URL');
    }

    private function _convertWatch($tDir, $jsDir, $cssDir, $srcDir) {
        return '"./'. $srcDir . $jsDir . '/**/*.js","./' . $tDir . '/**/*.**"';
    }

    private function _convertSrc($src, $srcDir = 'src', $destDir = 'public_html/site-assets/', $files = null) {
        if($files) {
            $files = explode (",", $files);
            $string = '';
            foreach ($files as $file) {
                $string = $string . '"' . $srcDir . '/' . $src . '/' . $file . '"' . ',';
            }
            return $string;
        }
        return '"' . $srcDir . '/' . $src . '"';

    }

    private function _cleanBlendidPath($path) {
        if(is_string($path)) {
            return ltrim($path,'./' );
        }
        return $path;
    }

    private function _getFileContents($file) {
        return file_get_contents($file);
    }


    private function _addFiles($files, $replace = []) {
        foreach ($files as $key => $value) {
            $file = self::$referencePath . $key;
            $result = self::_replaceTemplate($file, $replace);
            if($result) {
                self::_saveExample($result, './' . $value);
            }
        }
        return true;
    }


    private function _cloneReference(): bool
    {
        shell_exec('rm -rf ' . self::$referencePath);
        shell_exec('git clone ' . self::$exampleRepo . ' ' . self::$referencePath);
        return true;
    }

    private function _checkDir($dir, $allowReplace = true) {
        $fullPath = CRAFT_BASE_PATH . '/' . $dir;
        FileHelper::createDirectory($fullPath);
        $isEmpty = FileHelper::isDirectoryEmpty($fullPath) ?? true;
        if(!$isEmpty && $allowReplace) {
            Console::outputWarning( $fullPath . ' is not Empty ', Console::FG_RED);
            $replaceQuestion = Console::prompt('Do you want to replace it? [y/n] ');
            if($replaceQuestion === 'y') {
                FileHelper::clearDirectory($fullPath);
            } else {
                return false;
            }
        }
        if(!$isEmpty) {
            FileHelper::createDirectory($fullPath);
        }
    }

    private function _cleanUp(): bool {
        FileHelper::removeDirectory(self::$referencePath);
        return true;
    }

    private function _saveExample($content, $dest) {
        file_put_contents($dest, $content);
    }

    private function _replaceTemplate($file, $vars=array()) {

        if(file_exists($file)){
            $file = self::_getFileContents($file);
            if (preg_match_all("/{{(.*?)}}/", $file, $m)) {
                foreach ($m[1] as $i => $varname) {
                    $file = str_replace($m[0][$i], sprintf('%s',$vars[$varname]),$file);
                }
            }
            return $file;
        }
        Console::stdout('Template source not found.', Console::FG_RED);
        return false;
    }

}
