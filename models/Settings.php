<?php namespace Waka\MsGraph\Models;

use Model;
use Backend\Models\User as UserModel;

class Settings extends Model
{


    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 'waka_cloudis_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';


    public function ListMsGraphUsers() {
        return UserModel::where('msgraph_id', '<>', null)->lists('login', 'id');
    }
}