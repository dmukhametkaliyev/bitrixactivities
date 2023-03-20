<?php

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Loader;
use Bitrix\Crm\Service;
use Bitrix\Crm\Model\Dynamic\TypeTable;

Loader::includeModule('crm');

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CBPQBSChildrenSmartProcessApproveActivity
    extends CBPCompositeActivity
    implements IBPEventActivity, IBPActivityExternalEventListener
{
    private $taskId = 0;
    private $arMyActivityResults = array();

    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            "Title" => "",
            "Users" => '',
            "task_name" => null,
            "task_description" => null,
            'processes' => '',
            'process_id' => '',
            'items' => '',
            'voting_result' => ''

        );
        $this->SetPropertiesTypes([
            'Users' => [
                'Type' => \Bitrix\Bizproc\FieldType::USER,
            ]
        ]);
    }

    public function Execute()
    {
        $factory = Service\Container::getInstance()->getFactory($this->process_id);
        $parameters = array(
            "select" => array('*'),
            "filter" => array('PARENT_ID_' . $this->getDynamicID() => $this->getObjectID())
        );
        $items = $factory->getItems($parameters);
        $arItems = [];
        foreach ($items as $item) {
            $arItems[] = $item->getData();
        }

        $this->items = $arItems;

        $this->Subscribe($this);

        return CBPActivityExecutionStatus::Executing;
    }

    public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
    {
        $arErrors = array();
        if (empty($arTestProperties['task_name'])) {
            $arErrors[] = ["code" => "Empty", "parameter" => "task_name", "message" => GetMessage('QBS.CHILDRENSMARTPROCESSAPPROVE.EMPTYTASKNAME')];
        }
        return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
    }


    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
    {
        $runtime = CBPRuntime::GetRuntime();
        $processes = TypeTable::getList([
            'select' => ['*'],
            'order' => ["ID" => "DESC"],
        ])->fetchAll();


        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        if (!is_array($arCurrentValues)) {
            $arCurrentValues = array(
                "processes" => $processes,
                "process_id" => "",
                "Users" => '',
                "task_name" => '',
                "task_description" => ''
            );
            //$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);

            if (is_array($arCurrentActivity["Properties"])) {
                $arCurrentValues["processes"] = $processes;
                $arCurrentValues['process_id'] = $arCurrentActivity["Properties"]["process_id"];
                $arCurrentValues['Users'] = $arCurrentActivity["Properties"]["Users"];
                $arCurrentValues['task_name'] = $arCurrentActivity["Properties"]["task_name"];
                $arCurrentValues['task_description'] = $arCurrentActivity["Properties"]["task_description"];

            }
        }
        return $runtime->ExecuteResourceFile(
            __FILE__,
            "properties_dialog.php",
            array(
                "arCurrentValues" => $arCurrentValues,
                "formName" => $formName,
            )
        );

    }

    public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors): bool
    {
        $properties = array(
            "process_id" => $arCurrentValues["process_id"],
            "Users" => $arCurrentValues["Users"],
            "task_name" => $arCurrentValues["task_name"],
            "task_description" => $arCurrentValues["task_description"]
        );

        $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $currentActivity["Properties"] = $properties;

        $errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
        if (count($errors) > 0) {

            return false;
        }

        return true;
    }

    public function Subscribe(IBPActivityExternalEventListener $eventHandler)
    {
        $arErrorsTmp = array();
        $r = CBPHelper::UsersStringToArray($this->Users, self::getDocumentId(), $arErrorsTmp);
        $users = CBPHelper::ExtractUsers($r, self::getDocumentId());

        $taskService = $this->workflow->GetService("TaskService");

        $rootActivity = $this->GetRootActivity();
        $documentId = $rootActivity->GetDocumentId();
        $runtime = CBPRuntime::GetRuntime();
        $documentService = $runtime->GetService('DocumentService');

        $this->taskId = $taskService->CreateTask(
            array(
                "USERS" => $users,
                "WORKFLOW_ID" => $this->GetWorkflowInstanceId(),
                "ACTIVITY" => "QBSChildrenSmartProcessApproveActivity",
                "ACTIVITY_NAME" => $this->name,
                "NAME" => $this->task_name,
                "DESCRIPTION" => $this->task_description,
                "PARAMETERS" => $this->buildTaskParameters(),
                'IS_INLINE' => 'N',
                'DOCUMENT_NAME' => $documentService->GetDocumentName($documentId),
            )
        );
        $this->workflow->AddEventHandler($this->name, $eventHandler);
    }

    protected function buildTaskParameters()
    {
        $params = [];

        $params['ITEMS'] = $this->items;
        $params['PROCESS_ID'] = $this->process_id;
        $params['USERS'] = explode(';',$this->Users);
        //$params['RESULTS'] = $this->arMyActivityResults;

        return $params;
    }

    public function Unsubscribe(IBPActivityExternalEventListener $eventHandler)
    {
        $taskService = $this->workflow->GetService("TaskService");
        $taskService->DeleteTask($this->taskId);
        $this->workflow->RemoveEventHandler($this->name, $eventHandler);
        $this->taskId = 0;
    }

    public function OnExternalEvent($arEventParameters = array())
    {

        if ($this->executionStatus == CBPActivityExecutionStatus::Closed)
            return;
        $taskService = $this->workflow->GetService("TaskService");
        $taskService->MarkCompleted($this->taskId, $arEventParameters["USER_ID"]);
        //$task = $taskService->getTasksInfo([$this->taskId]);
        //Debug::writeToFile($task);

        $this->arMyActivityResults[$arEventParameters["USER_ID"]] =
            $arEventParameters["VOTE"];

        $arErrorsTmp = array();
        $r = CBPHelper::UsersStringToArray($this->Users, self::getDocumentId(), $arErrorsTmp);
        $users = CBPHelper::ExtractUsers($r, self::getDocumentId());
        $allUsersVoted = true;
        //Debug::writeToFile($this->arMyActivityResults);

        foreach ($users as $u) {
            if (!isset($this->arMyActivityResults[$u])) {
                $allUsersVoted = false;
                break;
            }
        }
        if ($allUsersVoted) {

            $this->voting_result = implode(',', $this->arMyActivityResults);

            $this->Unsubscribe($this);

            $this->workflow->CloseActivity($this);
        }
    }

    public static function getTaskControls($arTask): array
    {
        Debug::writeToFile($arTask);
        return array(
            'BUTTONS' => array(
                array(
                    'TYPE' => 'submit',
                    'TARGET_USER_STATUS' => CBPTaskUserStatus::Yes,
                    'NAME' => 'approve',
                    'VALUE' => 'Y',
                    'TEXT' => GetMessage("QBS.VOTE.BUTTON")
                ),
                array(
                    'TYPE' => 'submit',
                    'TARGET_USER_STATUS' => CBPTaskUserStatus::No,
                    'NAME' => 'nonapprove',
                    'VALUE' => 'Y',
                    'TEXT' => GetMessage("QBS.REFUSAL.BUTTON")
                ),
            )
        );
    }

    public static function ShowTaskForm($arTask, $userId, $userName = ""): array
    {


        ob_start();
        ?>

        <td width="60%" valign="top">
            <?php foreach ($arTask['PARAMETERS']['ITEMS'] as $item) : ?>
                <a href="/crm/type/<?= $arTask['PARAMETERS']['PROCESS_ID']; ?>/details/<?= $item['ID'] ?>/"
                   target="_blank"><?= $item['TITLE']; ?></a><br><br>
            <?php endforeach; ?>
        </td>
        <tr>
            <td valign="top" width="40%" align="right">Проголосуйте за:</td>
            <td valign="top" width="60%">
                <select name="vote">
                    <?php foreach ($arTask['PARAMETERS']['ITEMS'] as $item) : ?>
                        <option value="<?= $item['ID']; ?>"><?= $item['TITLE']; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>

        </tr>
        <tr>
            <td valign="top" width="40%" align="right">Причина отказа:</td>
            <td><textarea rows="3" cols="50" name="task_comment"></textarea></td>
        </tr>

        <?php
        $form = ob_get_clean();

        $buttons =
            '<input type="submit" name="approve" value="' . GetMessage("QBS.VOTE.BUTTON") . '"/>';
        return array($form, $buttons);
    }

    public static function PostTaskForm($arTask, $userId, $arRequest, &$arErrors, $userName = ""): bool
    {
        $arErrors = array();

        try {

            $userId = intval($userId);
            if ($arRequest['nonapprove'] == 'Y' && empty($arRequest['task_comment'])) {
                $arErrors[] = array(
                    "code" => "EMPTY_COMMENT",
                    "message" => "Укажите причину отказа",
                );
                return false;
            }

            if ($arRequest['approve'] == 'Y') {
                $arEventParameters = array(
                    "USER_ID" => $userId,
                    "USER_NAME" => $userName,
                    "VOTE" => $arRequest["vote"],
                    "COMMENT" => $arRequest["task_comment"]
                );
            } else {
                $arEventParameters = array(
                    "USER_ID" => $userId,
                    "USER_NAME" => $userName,
                    "VOTE" => 'refused',
                    "COMMENT" => $arRequest["task_comment"]
                );
            }

            CBPRuntime::SendExternalEvent(
                $arTask["WORKFLOW_ID"],
                $arTask["ACTIVITY_NAME"],
                $arEventParameters
            );
            return true;
        } catch (Exception $e) {
            $arErrors[] = array(
                "code" => $e->getCode(),
                "message" => $e->getMessage(),
                "file" => $e->getFile() . " [" . $e->getLine() . "]",
            );
        }
        return false;

    }

    public function HandleFault(Exception $exception): int
    {
        if ($exception == null)
            throw new Exception('exception');

        $status = $this->Cancel();
        if ($status == CBPActivityExecutionStatus::Canceling)
            return CBPActivityExecutionStatus::Faulting;

        return $status;
    }

    public function Cancel(): int
    {
        if (!$this->isInEventActivityMode && $this->taskId > 0)
            $this->Unsubscribe($this);

        return CBPActivityExecutionStatus::Closed;
    }

    public function getDynamicID()
    {
        $currentDocument = self::getDocumentId();
        $currentDocumentID = $currentDocument[2];
        $separatedID = explode('_', $currentDocumentID);
        return $separatedID[1];
    }

    public function getObjectID()
    {
        $currentDocument = self::getDocumentId();
        $currentDocumentID = $currentDocument[2];
        $separatedID = explode('_', $currentDocumentID);
        return $separatedID[2];
    }

}
