<?php namespace Waka\MsGraph;

use App;
use Backend;
use Config;
use Illuminate\Foundation\AliasLoader;
use System\Classes\PluginBase;
use Backend\Models\User as UserModel;
use Backend\Controllers\Users as UsersController;
use Lang;
use Wcli\Wconfig\Models\Settings as WconfigSetting;
use Event;

/**
 * msgraph Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'msgraph',
            'description' => 'No description provided yet...',
            'author'      => 'waka',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        // $this->app->singleton('msgraph', function ($app) {
        //     return new Waka\MsGraph\Classes\MsGraph;
        // });

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        $this->bootPackages();

        //CA NE MARCHE PAS
        UserModel::extend(function ($model) {
            $model->addDynamicMethod('getHasMsIdAttribute', function() use ($model) {
                if ($model->msgraph_id) {
                    return true;
                } else {
                    return false;
                }
            });
            $model->addDynamicMethod('listMsGraphUsers', function() use ($model) {
                return UserModel::where('msgraph_id', '<>', null)->lists('login', 'id');
            });
        });

        Event::listen('backend.form.extendFields', function ($widget) {
            // Only for the User controller
            if (!$widget->getController() instanceof UsersController) {
                return;
            }
            // Only for the User model
            if (!$widget->model instanceof UserModel) {
                return;
            }
            // Add an extra description field

            $widget->addTabFields([
                'msgraph_id' => [
                    'tab' => 'OFFICE 365',
                    'label' => 'MsGraph ID',
                    'readOnly' => true,
                ],
            ]);
        });

        Event::listen('backend.list.extendColumns', function ($widget) {
            // Only for the User controller
            if (!$widget->getController() instanceof UsersController) {
                return;
            }

            // Only for the User model
            if (!$widget->model instanceof UserModel) {
                return;
            }
            // Add an extra birthday column
            $widget->addColumns([
                'hasMsId' => [
                    'label' => 'MS ID',
                    'type' => 'switch',
                ],
            ]);
        });

        WconfigSetting::extend(function ($setting) {
            $setting->addDynamicMethod('listMsGraphUsers', function () {
                return UserModel::where('msgraph_id', '<>', null)->lists('login', 'id');
            });
        });

        Event::listen('backend.form.extendFields', function ($widget) {

            //trace_log('yo');
            if (!$widget->getController() instanceof \System\Controllers\Settings) {
                return;
            }

            // Only for the User model
            if (!$widget->model instanceof WconfigSetting) {
                return;
            }

            if ($widget->isNested === true) {
                return;
            }

            $widget->addTabFields([
                'drive_account' => [
                    'tab' => 'Office 365',
                    'label' => "Compte principal pour le drive",
                    'type' => 'dropdown',
                    'placeholder' => "Choisssez un utilisateur",
                    'options' => 'listMsGraphUsers'
                ],
                'drive_folder' => [
                    'tab' => 'Office 365',
                    'label' => "Dossier drive",
                    'default' => "notilac_cloud"
                ],
                'base_request' => [
                    'tab' => 'Office 365',
                    'label' => "Requête de base",
                    'default' => "sites/notilac.sharepoint.com"
                ],
            ]);
        });
        
    }

    public function bootPackages()
    {
        // Get the namespace of the current plugin to use in accessing the Config of the plugin
        $pluginNamespace = str_replace('\\', '.', strtolower(__NAMESPACE__));

        // Instantiate the AliasLoader for any aliases that will be loaded
        $aliasLoader = AliasLoader::getInstance();

        // Get the packages to boot
        $packages = Config::get($pluginNamespace . '::packages');

        // Boot each package
        foreach ($packages as $name => $options) {
            // Setup the configuration for the package, pulling from this plugin's config
            if (!empty($options['config']) && !empty($options['config_namespace'])) {
                Config::set($options['config_namespace'], $options['config']);
            }
            // Register any Service Providers for the package
            if (!empty($options['providers'])) {
                foreach ($options['providers'] as $provider) {
                    App::register($provider);
                }
            }
            // Register any Aliases for the package
            if (!empty($options['aliases'])) {
                foreach ($options['aliases'] as $alias => $path) {
                    $aliasLoader->alias($alias, $path);
                }
            }
        }
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Waka\MsGraph\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'waka.msgraph.admin.super' => [
                'tab' => 'Waka - MsGraph',
                'label' => 'Super Administrateur de MsGraph',
            ],
            'waka.msgraph.admin.base' => [
                'tab' => 'Waka - MsGraph',
                'label' => 'Administrateur de MsGraph',
            ],
            'waka.msgraph.user' => [
                'tab' => 'Waka - MsGraph',
                'label' => 'Utilisateur de MsGraph',
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerSettings()
    {
        return [
            'admin_graph' => [
                'label' => Lang::get('waka.msgraph::lang.settings.label'),
                'description' => Lang::get('waka.msgraph::lang.settings.description'),
                'category' => Lang::get('waka.msgraph::lang.settings.category'),
                'icon' => 'wicon-windows',
                'url' => Backend::url('waka/msgraph/admin'),
                'permissions' => ['waka.msgraph.admin'],
            ],
        ];
    }
}
