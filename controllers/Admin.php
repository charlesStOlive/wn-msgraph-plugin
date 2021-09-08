<?php namespace Waka\MsGraph\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;
use Backend\Models\User as UserModel;
use Brick\VarExporter\VarExporter;
/**
 * WakaUsers Back-end Controller
 */
class Admin extends Controller
{
    /**
     * @var array Behaviors that are implemented by this controller.
     */
    public $implement = [
    ];



    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Waka.MsGraph', 'admin_graph');
    }

    public function index() {
        if(\MsGraphAdmin::isConnected()) {
            return $this->makePartial('index_connected');
        } else {
            return $this->makePartial('connect');
        }
    }
    public function onConnect() {
        return $this->connect();
    }
    public function connect() {
        return \MsGraphAdmin::connect();
    }

    // public function onTestDrive() {
    //     $fileAdress = storage_path('app/media/test.jpeg');
    //     //trace_log($fileAdress);
    //     $file = \File::get($fileAdress);
    //     \MsGraphAdmin::files()->upload('test.jpeg', $fileAdress);
    // }
    public function onTestDrive() {
        // $fileAdress = storage_path('app/media/test.jpeg');
        // trace_log($fileAdress);
        // $fileContent = \File::get($fileAdress);
        // trace_log($fileContent);
        // \MsGraphAdmin::files()->upload('demo/test.jpeg', \File::get($fileAdress));
        
        // $childs = \MsGraphAdmin::files()->gelotChilds('demo');
        // trace_log($childs);

        // $childs = \MsGraphAdmin::files()->createFolder('un/deux');
        // trace_log($childs);

        

        // $childs = \MsGraphAdmin::files()->site();
        // trace_log($childs);

        // $childs = \MsGraphAdmin::files()->getChilds('demo');
        // trace_log($childs);

        \MsGraphAdmin::files()->upload('demo/test.txt', 'salut les gros nazes');
        $get = \MsGraphAdmin::files()->getFileUrlContent('demo/test.txt');
        \Storage::put('demo/test.txt', $get);




        $this->vars['wakaUserCount'] = 1;
        $this->vars['userFinded'] = 1;
    }
    public function onListSites() {
        $result = \MsGraphAdmin::files()->getSites();
        /**/trace_log($result);
        $this->vars['result'] = VarExporter::export($result,VarExporter::NO_CLOSURES);
        return true;

    }
    public function onListGroups() {
        $result = \MsGraphAdmin::files()->getGroups();
        /**/trace_log($result);
        $this->vars['result'] = VarExporter::export($result,VarExporter::NO_CLOSURES);
        return true;
    }

    public function onSync() {
        if(\MsGraphAdmin::isConnected()) {
            $users = \MsGraphAdmin::get('users');
            //trace_log($users);
            $users = $users['value'];
            $userFinded = 0;
            $wakaUserCount = UserModel::count();
            foreach($users as $user) {
                $mail = $user['mail'] ?? "Error_";
                //trace_sql();
                $wakaUser = UserModel::where('email', $mail)->first();
                if($wakaUser) {
                    $userFinded++;
                    //trace_log($wakaUser->email);
                    //trace_log($user['id']);
                    $wakaUser->msgraph_id = $user['id'];
                    $wakaUser->save();
                    \MsGraphAdmin::contacts()->userid($user['id'])->get();
                }
            }
        }
        $this->vars['wakaUserCount'] = $wakaUserCount;
        $this->vars['userFinded'] = $userFinded;
        //La redireection du partial est appelé dans le bouton je n'ai pas réussi avec makePartial.         
    }
}
