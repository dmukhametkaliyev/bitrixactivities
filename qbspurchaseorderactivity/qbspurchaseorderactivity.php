<?php

use Bitrix\Crm\Workflow;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Loader;
use Bitrix\Crm\Service;
use Bitrix\Crm\Model\Dynamic\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Bizproc\WorkflowTemplateTable;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Bizproc\WorkflowInstanceTable;
use Bitrix\Bizproc\Workflow\Template;


Loader::includeModule('crm');

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CBPQBSPurchaseOrderActivity extends CBPActivity
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            "items" => '',
            //"parent_id" => '',
            "child_id" => '',
            "Result" => false
        );

    }

    public function Execute()
    {

        $factory = Service\Container::getInstance()->getFactory($this->child_id);
        $parameters = array(
            "select" => array('PARENT_ID_' . $this->getDynamicID()),
            "filter" => array(
                'PARENT_ID_' . $this->getDynamicID() => $this->getObjectID()
            )
        );

        $items = $factory->getItems($parameters);
        $parentDynamicID = 'PARENT_ID_' . $this->getDynamicID();

        foreach ($items as $item) {
            if (!empty($item[$parentDynamicID])) {
                $this->Result = true;
            }
            //$this->Result = 'Нет';

        }
        return CBPActivityExecutionStatus::Closed;

    }

    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
    {
        $runtime = CBPRuntime::GetRuntime();
        $processes = TypeTable::getList([
            'select' => ['*'],
            'order' => ["ID" => "DESC"],
        ])->fetchAll();

        /*foreach ($processes as $process) {
            $processes[] = $process['TITLE'];
        }*/
        if (!is_array($arCurrentValues)) {
            //$arCurrentValues = array("parent" => $processes, "parent_id" => "", "child" => $processes, "child_id" => "");
            $arCurrentValues = array("child" => $processes, "child_id" => "");
            $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
            if (is_array($arCurrentActivity["Properties"])) {
               // $arCurrentValues["parent"] = $processes;
                //$arCurrentValues["parent_id"] = $arCurrentActivity["Properties"]["parent_id"];
                $arCurrentValues["child"] = $processes;
                $arCurrentValues["child_id"] = $arCurrentActivity["Properties"]["child_id"];
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
        $properties = array(
            //"parent_id" => $arCurrentValues["parent_id"],
            "child_id" => $arCurrentValues["child_id"],
        );

        $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $currentActivity["Properties"] = $properties;

        return true;
    }

    public function getObjectID()
    {
        $currentDocument = self::getDocumentId();
        $currentDocumentID = $currentDocument[2];
        $separatedID = explode('_', $currentDocumentID);
        return $separatedID[2];
    }

    public function getDynamicID()
    {
        $currentDocument = self::getDocumentId();
        $currentDocumentID = $currentDocument[2];
        $separatedID = explode('_', $currentDocumentID);
        return $separatedID[1];
    }


}