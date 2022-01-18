<?php namespace Waka\MsGraph\Behaviors;

use Backend\Classes\ControllerBehavior;

use Session;
use Waka\Mailer\Behaviors\MailBehavior;
use Waka\Mailer\Classes\MailCreator;
use Waka\Mailer\Models\WakaMail;
use Waka\Utils\Classes\DataSource;

class OutlookBehavior extends MailBehavior
{
    use \Waka\Utils\Classes\Traits\StringRelation;
    protected $mailBehaviorWidget;
    protected $mailDataWidget;
    protected $controller;
    public $errors;

    public function __construct($controller)
    {
        parent::__construct($controller);
        $this->controller = $controller;
        $this->mailBehaviorWidget = $this->createMailBehaviorWidget();
        $this->mailDataWidget = $this->createMailDataWidget();
        $this->errors = [];
        \Event::listen('waka.utils::conditions.error', function ($error) {
            array_push($this->errors, $error);
        });
        
    }

    /**
     ******************** LOAD DES POPUPS et du test******************************
     */

    public function onLoadOutlookBehaviorPopupForm()
    {
        $this->getPopupOutlookContent();
        if(\BackendAuth::getUser()->hasMsId) {
            return $this->makePartial('$/waka/msgraph/behaviors/outlookbehavior/_popup.htm');
        } else {
            return $this->makePartial('$/waka/msgraph/behaviors/outlookbehavior/_error_msg_popup.htm');
        }
        
    }

    public function onLoadOutlookBehaviorContentForm()
    {
        $this->getPopupOutlookContent();
        if(\BackendAuth::getUser()->hasMsId) {
            return ['#popupActionContent' => $this->makePartial('$/waka/msgraph/behaviors/outlookbehavior/_content.htm')];
        } else {
            return ['#popupActionContent' => $this->makePartial('$/waka/msgraph/behaviors/outlookbehavior/_error_msg.htm')];
        }
    }

    public function getPopupOutlookContent()
    {
        $modelClass = post('modelClass');
        $modelId = post('modelId');
        //datasource
        $ds = \DataSources::findByClass($modelClass);
        $options = $ds->getProductorOptions('Waka\Mailer\Models\WakaMail', $modelId);
        $contact = $ds->getContact('to', $modelId);
        //
        $this->mailBehaviorWidget->getField('email')->options = $contact;
        $cc = $ds->getContact('cc', $modelId);
        $this->mailBehaviorWidget->getField('cc')->options = $cc;
        $this->mailBehaviorWidget->addFields([
            'send_brouillon' => [
                'label' => 'waka.msgraph::lang.outlook.draf_send',
                'cssClass' => 'inline-options',
                'type'    => 'radio',
                'default' => 'draft',
                'options' => [
                    'draft' => 'waka.msgraph::lang.outlook.draft',
                    'send' => 'waka.msgraph::lang.outlook.send'
                ]
            ]
        ]); 
        $this->vars['mailBehaviorWidget'] = $this->mailBehaviorWidget;
        $this->vars['modelId'] = $modelId;
        $this->vars['errors'] = $this->errors;
        $this->vars['modelClass'] = $modelClass;
        $this->vars['options'] = $options;
    }

    /**
     * Cette fonction est utilisé lors du test depuis le controller wakamail.
     */
    public function onLoadMailTestForm()
    {
        $productorId = post('productorId');
        $wakaMail = WakaMail::find($productorId);
        $dataSourceCode = $wakaMail->data_source;
        $ds = \DataSources::find($dataSourceCode);
        $options = $ds->getProductorOptions('Waka\Mailer\Models\WakaMail');
        $contact = $ds->getContact('to', null);
        $this->mailBehaviorWidget->getField('email')->options = $contact;
        $cc = $ds->getContact('cc', null);
        $this->mailBehaviorWidget->getField('cc')->hidden = true;
        $this->mailDataWidget->getField('subject')->value = $wakaMail->subject;
        $this->vars['productorId'] = $productorId;
        $this->vars['mailDataWidget'] = $this->mailDataWidget;
        $this->vars['mailBehaviorWidget'] = $this->mailBehaviorWidget;
        $this->vars['modelId'] = null;
        $this->vars['options'] = $options;
        return $this->makePartial('$/waka/msgraph/behaviors/outlookbehavior/_test.htm');
    }
    /**
     * Cette fonction est utilisé lors du test depuis le controller wakamail.
     */
    public function onSelectWakaMailOutlook()
    {
        $productorId = post('productorId');
        $modelClass = post('modelClass');
        $modelId = post('modelId');
        $ds = \DataSources::findByClass($modelClass);
        $wakaMail = WakaMail::find($productorId);


        $subject = $ds->dynamyseText($wakaMail->subject, $modelId);
        $this->mailDataWidget->getField('subject')->value = $subject;
        $this->vars['mailDataWidget'] = $this->mailDataWidget;

        $askDataWidget = $this->createAskDataWidget();
        $asks = $ds->getProductorAsks('Waka\Mailer\Models\WakaMail',$productorId, $modelId);
        $askDataWidget->addFields($asks);
        $this->vars['askDataWidget'] = $askDataWidget;

        return [
            '#mailDataWidget' => $this->makePartial('$/waka/mailer/behaviors/mailbehavior/_widget_data.htm'),
            '#askDataWidget' => $this->makePartial('$/waka/utils/models/ask/_widget_ask_data.htm'),
        ];
    }

    public function onOutlookBehaviorPartialValidation()
    {

        $datas = post();
        $errors = $this->CheckValidation($datas);
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        $productorId = $datas['productorId'];
        $modelId = $datas['modelId'];
        if (post('testHtml')) {
            $this->vars['html'] = MailCreator::find($productorId)->setModelId($modelId)->setAsksResponse($datas['mailData_array'] ?? [])->renderHtmlforTest();
            return $this->makePartial('$/waka/mailer/behaviors/mailbehavior/_html.htm');
        } 
        //
        $datasEmail = [
            'emails' => $datas['mailBehavior_array']['email'],
            'subject' => $datas['mailData_array']['subject'],
            'asks' => $datas['asks_array'] ?? [],
        ];
        $mailCreator = MailCreator::find($productorId)->setModelId($modelId)->setAsksResponse($datas['mailData_array'] ?? []);
        $sendType = $datas['send_brouillon'] ?? 'draft';
        $usermsId = \BackendAuth::getUser()->msgraph_id;
        $mail =  $mailCreator->renderOutlook($datasEmail,$usermsId, $sendType);
        //trace_log("resultat envoi email");
        //trace_log($mail);
        if($sendType == 'draft') {
            \Flash::success(\Lang::get('waka.msgraph::lang.outlook.draft_ok'));
        } else {
            \Flash::success(\Lang::get('waka.msgraph::lang.outlook.send_ok'));
        }
        
    }
    /**
     * Validations
     */
    public function CheckValidation($inputs)
    {
        $rules = [
            'productorId' => 'required',
            'modelId' => 'required'
        ];
        $is_test = $inputs['testHtml'] ?? false;
        if (!$is_test) {
            $rules['mailData_array.subject'] = 'required | min:3';
            $rules['mailBehavior_array.email'] = 'required';
        }

        $validator = \Validator::make($inputs, $rules);

        if ($validator->fails()) {
            return $validator->messages()->first();
        } else {
            return false;
        }
    }

    /**
     * ************************************Traitement par lot**********************************
     */
    public function onLotOutlook()
    {
         if(!\BackendAuth::getUser()->hasMsId) {
            return ['#popupActionContent' => $this->makePartial('$/waka/msgraph/behaviors/outlookbehavior/_error_msg.htm')];
        } 
        $modelClass = post('modelClass');
        $ds = \DataSources::findByClass($modelClass);
        $options = $ds->getPartialIndexOptions('Waka\Mailer\Models\WakaMail');
        //
        $this->vars['options'] = $options;
        $this->vars['mailDataWidget'] = $this->mailDataWidget;
        $this->vars['modelClass'] = $modelClass;
        //
        return ['#popupActionContent' => $this->makePartial('$/waka/msgraph/behaviors/outlookbehavior/_lot.htm')];
    }

    public function onLotOutlookValidation()
    {
        //trace_log(\Input::all());
        $errors = $this->CheckIndexValidation(\Input::all());
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        $lotType = post('lotType');
        $productorId = post('productorId');
        $listIds = null;
        if ($lotType == 'filtered') {
            $listIds = Session::get('lot.listId');
        } elseif ($lotType == 'checked') {
            $listIds = Session::get('lot.checkedIds');
        }
        Session::forget('lot.listId');
        Session::forget('lot.checkedIds');
        //
        $datas = [
            'listIds' => $listIds,
            'productorId' => $productorId,
            'subject' => post('mailData_array.subject'),
            'userMsId' => \BackendAuth::getUser()->msgraph_id
            
        ];
        try {
            $job = new \Waka\MsGraph\Jobs\SendOutlookEmails($datas);
            $jobManager = \App::make('Waka\Wakajob\Classes\JobManager');
            $jobManager->dispatch($job, "Envoi d'emails");
            $this->vars['jobId'] = $job->jobId;
        } catch (Exception $ex) {
                $this->controller->handleError($ex);
        }
        return ['#popupActionContent' => $this->makePartial('$/waka/wakajob/controllers/jobs/_confirm.htm')];
    }

}
