<?php

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Loader;

Loader::includeModule('tasks');

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CBPQBSTaskCompleteActivity extends CBPActivity
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            'task_id' => '',
            'user_id' => ''
        );
        $this->SetPropertiesTypes([
            'user_id' => [
                'Type' => \Bitrix\Bizproc\FieldType::USER,
            ]
        ]);

    }

    /**
     * @throws \Bitrix\Main\SystemException
     */
    public function Execute()
    {
        $arErrorsTmp = array();
        $r = CBPHelper::UsersStringToArray($this->user_id, self::getDocumentId(), $arErrorsTmp);

        $closedBy = CBPHelper::ExtractUsers($r, self::getDocumentId());

        $TaskItem = CTaskItem::getInstance($this->task_id, $closedBy[0]);
        $TaskItem->complete();
        Debug::writeToFile($this->arProperties);


        return CBPActivityExecutionStatus::Closed;
    }
    public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
    {
        $arErrors = array();
        if (empty($arTestProperties['task_id']))
        {
            $arErrors[] = ["code" => "NotSelected", "parameter" => "task_id", "message" => GetMessage('QBS.TASKCOMPLETEACTIVITY.EMPTYTASK')];
        }
        if (empty($arTestProperties['user_id']))
        {
            $arErrors[] = ["code" => "NotSelected", "parameter" => "user_id", "message" => GetMessage('QBS.TASKCOMPLETEACTIVITY.EMPTYUSER')];
        }


        return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
    }

    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
    {
        $runtime = CBPRuntime::GetRuntime();
        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);

        if (!is_array($arCurrentValues)) {
            $arCurrentValues = array('task_id' => '', 'user_id' => '');

            if (is_array($arCurrentActivity["Properties"])) {
                $arCurrentValues["task_id"] = $arCurrentActivity["Properties"]['task_id'];
                $arCurrentValues["user_id"] = $arCurrentActivity["Properties"]['user_id'];
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


    public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
    {
        $errors = [];
        $properties = array(
            "task_id" => $arCurrentValues["task_id"],
            "user_id" => $arCurrentValues["user_id"]
        );

        $errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
        if (count($errors) > 0)
        {
            return false;
        }
        $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $currentActivity["Properties"] = $properties;

        return true;

    }

}




