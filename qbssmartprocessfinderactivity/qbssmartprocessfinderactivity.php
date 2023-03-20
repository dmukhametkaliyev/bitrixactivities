<?php

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Loader;
use Bitrix\Crm\Service;
use Bitrix\Crm\Model\Dynamic\TypeTable;


Loader::includeModule('crm');

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CBPQBSSmartprocessFinderActivity extends CBPActivity
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            'fields' => '',
            'field_key' => '',
            'processes' => '',
            'process_id' => '',
            'Field_value' => '',
            'Result' => ''
        );
    }

    public function Execute()
    {
        $factory = Service\Container::getInstance()->getFactory($this->process_id);
        $parameters = array(
            //'select' => ['*'],
            'filter' => [$this->field_key => $this->Field_value]
        );
        $items = $factory->getItems($parameters);

        $ids = array_map(function($item) {
            return $item['ID'];
        }, $items);

        $stringIds = implode(',', $ids);

        Debug::writeToFile($stringIds);
        $this->Result = $stringIds;

        //Debug::writeToFile($items);
        //Debug::writeToFile($this->field_key);
        //Debug::writeToFile($this->Field_value);

        return CBPActivityExecutionStatus::Closed;
    }

    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
    {
        $runtime = CBPRuntime::GetRuntime();
        $processes = TypeTable::getList([
            'select' => ['*'],
            'order' => ["ID" => "DESC"],
        ])->fetchAll();

        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        //Debug::writeToFile($arCurrentActivity["Properties"]["process_id"]);
        if (empty($arCurrentActivity["Properties"]["process_id"])) {
            $fields = [];
        } else {
            $factory = Service\Container::getInstance()->getFactory($arCurrentActivity["Properties"]["process_id"]);
            $fields = $factory->getFieldsInfo();
        }
        //Debug::writeToFile($fields);
        if (!is_array($arCurrentValues)) {
            $arCurrentValues = array("processes" => $processes, "process_id" => "", "fields" => $fields, "field_key" => "", "field_value" => "");
            //$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);

            if (is_array($arCurrentActivity["Properties"])) {
                $arCurrentValues["processes"] = $processes;
                $arCurrentValues['process_id'] = $arCurrentActivity["Properties"]["process_id"];
                $arCurrentValues["fields"] = $fields;
                $arCurrentValues["field_key"] = $arCurrentActivity["Properties"]["field_key"];
                $arCurrentValues["field_value"] = $arCurrentActivity["Properties"]['Field_value'];
            }
            //Debug::writeToFile($arCurrentValues['field_value']);

            return $runtime->ExecuteResourceFile(
                __FILE__,
                "properties_dialog.php",
                array(
                    "arCurrentValues" => $arCurrentValues,
                    "formName" => $formName,
                )
            );
        }
    }


    public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
    {
        $properties = array(
            "field_key" => $arCurrentValues["field_key"],
            "process_id" => $arCurrentValues["process_id"],
            "Field_value" => $arCurrentValues["field_value"]
        );

        $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $currentActivity["Properties"] = $properties;
        //Debug::writeToFile($properties);

        return true;

    }

}
