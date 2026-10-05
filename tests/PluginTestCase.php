<?php

use Backend\Classes\AuthManager;
use System\Classes\UpdateManager;
use System\Classes\PluginManager;
use October\Rain\Database\Model as ActiveRecord;
use October\Tests\Concerns\InteractsWithAuthentication;

abstract class PluginTestCase extends TestCase
{
    use InteractsWithAuthentication;

    protected $pluginTestCaseLoadedPlugins = [];

    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        $app['cache']->setDefaultDriver('array');
        $app->setLocale('en');

        $app->singleton('auth', function ($app) {
            $app['auth.loaded'] = true;

            return AuthManager::instance();
        });

        $dbConnection = 'sqlite';

        $dbConnections = [];
        $dbConnections['sqlite'] = [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ];

        if (env('APP_ENV') === 'testing' && Config::get('database.useConfigForTesting', false)) {
            $dbConnection = Config::get('database.default', 'sqlite');

            $dbConnections[$dbConnection] = Config::get('database.connections' . $dbConnection, $dbConnections['sqlite']);
        }

        $app['config']->set('database.default', $dbConnection);
        $app['config']->set('database.connections.' . $dbConnection, $dbConnections[$dbConnection]);

        $app->setPluginsPath(realpath(base_path().Config::get('cms.pluginsPath')));

        return $app;
    }

    public function setUp()
    {
        PluginManager::forgetInstance();
        UpdateManager::forgetInstance();

        parent::setUp();

        $this->runOctoberUpCommand();

        $this->pluginTestCaseLoadedPlugins = [];
        $pluginCode = $this->guessPluginCodeFromTest();

        if ($pluginCode !== false) {
            $this->runPluginRefreshCommand($pluginCode, false);
        }

        Mail::pretend();
    }

    public function tearDown()
    {
        $this->flushModelEventListeners();
        parent::tearDown();
        unset($this->app);
    }

    protected function runOctoberUpCommand()
    {
        Artisan::call('october:up');
    }

    protected function runPluginRefreshCommand($code, $throwException = true)
    {
        if (!preg_match('/^[\w+]*\.[\w+]*$/', $code)) {
            if (!$throwException) {
                return;
            }
            throw new Exception(sprintf('Invalid plugin code: "%s"', $code));
        }

        $manager = PluginManager::instance();
        $plugin = $manager->findByIdentifier($code);

        if (!$plugin) {
            $namespace = '\\'.str_replace('.', '\\', strtolower($code));
            $path = array_get($manager->getPluginNamespaces(), $namespace);

            if (!$path) {
                if (!$throwException) {
                    return;
                }
                throw new Exception(sprintf('Unable to find plugin with code: "%s"', $code));
            }

            $plugin = $manager->loadPlugin($namespace, $path);
        }

        $this->pluginTestCaseLoadedPlugins[$code] = $plugin;

        if (!empty($plugin->require)) {
            foreach ((array) $plugin->require as $dependency) {
                if (isset($this->pluginTestCaseLoadedPlugins[$dependency])) {
                    continue;
                }

                $this->runPluginRefreshCommand($dependency);
            }
        }

        Artisan::call('plugin:refresh', ['name' => $code]);
    }

    protected function getPluginObject($code = null)
    {
        if ($code === null) {
            $code = $this->guessPluginCodeFromTest();
        }

        if (isset($this->pluginTestCaseLoadedPlugins[$code])) {
            return $this->pluginTestCaseLoadedPlugins[$code];
        }
    }

    protected function flushModelEventListeners()
    {
        foreach (get_declared_classes() as $class) {
            if ($class == 'October\Rain\Database\Pivot') {
                continue;
            }

            $reflectClass = new ReflectionClass($class);
            if (
                !$reflectClass->isInstantiable() ||
                !$reflectClass->isSubclassOf('October\Rain\Database\Model') ||
                $reflectClass->isSubclassOf('October\Rain\Database\Pivot')
            ) {
                continue;
            }

            $class::flushEventListeners();
        }

        ActiveRecord::flushEventListeners();
    }

    protected function guessPluginCodeFromTest()
    {
        $reflect = new ReflectionClass($this);
        $path = $reflect->getFilename();
        $basePath = $this->app->pluginsPath();

        $result = false;

        if (strpos($path, $basePath) === 0) {
            $result = ltrim(str_replace('\\', '/', substr($path, strlen($basePath))), '/');
            $result = implode('.', array_slice(explode('/', $result), 0, 2));
        }

        return $result;
    }
}
