<?php

/**
 * Class file
 *
 * @author Tobias Munk <schmunk@usrbin.de>
 * @link http://www.phundament.com/
 * @copyright Copyright &copy; 2012 diemeisterei GmbH
 * @license http://www.phundament.com/license
 */

namespace config;
use Composer\Script\Event;

/**
 * P3Setup provides composer hooks
 *
 * This setup class triggers `./yiic migrate` at post-install and post-update.
 * For a package the class triggers `./yiic <vendor/<packageName>-<action>` at post-package-install and
 * post-package-update.
 * See composer manual (http://getcomposer.org/doc/articles/scripts.md)
 *
 * Usage example
 *
 * config.php
 *     'params' => array(
        'composer.hooks' => array(
            'post-update' => array('yiic', 'migrate'),
            'post-install' => array('yiic', 'migrate'),
            'yiisoft/yii-install' => array('yiic', 'webapp', realpath(dirname(__FILE__))),
        ),
    )
);

 * composer.json
 *     "scripts": {
        "pre-install-cmd": "install\\P3Setup::preInstall",
        "post-install-cmd": "install\\P3Setup::postInstall",
        "pre-update-cmd": "install\\P3Setup::preUpdate",
        "post-update-cmd": "install\\P3Setup::postUpdate",
        "post-package-install": [
        "install\\P3Setup::postPackageInstall"
        ],
        "post-package-update": [
        "install\\P3Setup::postPackageUpdate"
        ]
    }

 *
 *
 * @author Tobias Munk <schmunk@usrbin.de>
 * @package phundament.app
 * @since 0.7.1
 */

defined('YII_PATH') or define('YII_PATH', dirname(__FILE__).'/../vendor/yiisoft/yii/framework');
defined('P3_CONSOLE_CONFIG') or define('P3_CONSOLE_CONFIG', dirname(__FILE__).'/console.php');

class P3Setup
{
    /**
     * Displays welcome message
     * @static
     * @param \Composer\Script\Event $event
     */
    public static function preInstall(Event $event)
    {
        $composer = $event->getComposer();
        // do stuff
        echo "Welcome to Phundament Installation 3 via composer\n\n";
        echo "This setup script will download all packages specified in composer.json. It will also trigger the creation of a " .
        "web application and invoke the required migrations, please answer the upcoming confirmation questions with [y]es.\n\n";
        if (self::confirm("Install Phundament 3 now?")) {
            self::runHook('pre-install');
        } else {
            exit("Installation aborted.\n");
        }
    }

    /**
     * Executes ./yiic migrate
     * @static
     * @param \Composer\Script\Event $event
     */
    public static function postInstall(Event $event)
    {
        self::runHook('post-install');
        echo "\n\nInstallation completed.\n\nThank you for choosing Phundament 3!\n\n";
    }

    /**
     * Displays welcome message
     *
     * @static
     * @param \Composer\Script\Event $event
     */
    public static function preUpdate(Event $event)
    {
        echo "Welcome to Phundament Installation 3 via composer\n\nUpdating your application to the lastest available packages...\n";
        self::runHook('pre-update');
    }

    /**
     * Executes ./yiic migrate
     *
     * @static
     * @param \Composer\Script\Event $event
     */
    public static function postUpdate(Event $event)
    {
        self::runHook('post-update');
        echo "\n\nUpdate completed.\n\n";
    }

    /**
     * Executes ./yiic <vendor/<packageName>-<action>
     *
     * @static
     * @param \Composer\Script\Event $event
     */
    public static function postPackageInstall(Event $event)
    {
        $installedPackage = $event->getOperation()->getPackage();
        $hookName = $installedPackage->getPrettyName().'-install';
        self::runHook($hookName);
    }

    /**
     * Executes ./yiic <vendor/<packageName>-<action>
     *
     * @static
     * @param \Composer\Script\Event $event
     */
    public static function postPackageUpdate(Event $event)
    {
        $installedPackage = $event->getOperation()->getTargetPackage();
        $commandName = $installedPackage->getPrettyName().'-update';
        self::runHook($commandName);
    }

    /**
     * Asks user to confirm by typing y or n.
     *
     * Credits to Yii CConsoleCommand
     *
     * @param string $message to echo out before waiting for user input
     * @return bool if user confirmed
     */
    public static function confirm($message)
    {
        echo $message . ' [yes|no] ';
        return !strncasecmp(trim(fgets(STDIN)), 'y', 1);
    }

    /**
     * Runs Yii command, if available (defined in config/console.php)
     */
    private static function runHook($name){
        $app = self::getYiiApplication();
        if ($app === null) return;

        if (isset($app->params['composer.hooks'][$name])) {
            $args = $app->params['composer.hooks'][$name];
            $app->commandRunner->addCommands(\Yii::getPathOfAlias('system.cli.commands'));
            $app->commandRunner->run($args);
        }
    }

    /**
     * Creates console application, if Yii is available
     */
    private static function getYiiApplication()
    {
        if (!is_file(YII_PATH.'/yii.php'))
        {
            return null;
        }

        require_once(YII_PATH.'/yii.php');
        spl_autoload_register(array('YiiBase', 'autoload'));

        if (\Yii::app() === null) {
            if (is_file(P3_CONSOLE_CONFIG)) {
                $app = \Yii::createConsoleApplication(P3_CONSOLE_CONFIG);
            } else {
                throw new \Exception("File from P3_CONSOLE_CONFIG not found");   
            }            
        } else {
            $app = \Yii::app();
        }
        return $app;
    }

}
