<?php

use Bitrix\Main\Loader;

Loader::includeModule("highloadblock");

use Bitrix\Main\Diag\Debug;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;
use Bitrix\Bizproc;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CBPQBSLogActivity
    extends CBPActivity
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            "Title" => "",
            //"SetVariable" => false,
            "Report" => "",
            "process_id" => "",
            "action_id" => "",
            "hoursCount" => "",
            "timeType" => "",
            "timeOperation" => ""
        );

        $this->SetPropertiesTypes(array(
            'hoursCount' => array(
                'Type' => Bizproc\FieldType::STRING
            ),
            'timeType' => array(
                'Type' => Bizproc\FieldType::STRING,
            ),
            'timeOperation' => array(
                'Type' => Bizproc\FieldType::TEXT,
            ),
        ));
    }

    public function Execute()
    {
        $block = HLBT::getList([
            'filter' => ['=NAME' => 'TimeLimit']
        ])->fetch();

        $hlblock = HLBT::getById($block['ID'])->fetch();
        $entity = HLBT::compileEntity($hlblock);
        $entityDataClass = $entity->getDataClass();

        $result = $entityDataClass::getList(array(
            "select" => array("*"),
            "order" => array("ID" => "DESC"),
            "filter" => array("UF_ACTION" => $this->action_id),
        ));

        $arRow = $result->Fetch();
        $this->hoursCount = $arRow['UF_HOURS_COUNT'];
        $this->timeType = CBPQBSLogActivity::userFieldValue($arRow['UF_TIME_TYPE']);

        switch ($this->timeType) {
            case 'рабочие часы':
                $this->timeOperation = '{{=workdateadd({=System:Now},' . $this->hoursCount . '&"h")}}';
                break;
            case 'календарные часы':
                $this->timeOperation = '{{=dateadd({=System:Now},' . $this->hoursCount . '&"h")}}';
                break;
        }

        $this->WriteToTrackingService($this->timeType, 0, CBPTrackingType::Report);
        $this->WriteToTrackingService($this->hoursCount, 0, CBPTrackingType::Report);
        return CBPActivityExecutionStatus::Closed;
    }

    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
    {
        $runtime = CBPRuntime::GetRuntime();

        $ibProcesses = self::getIblockFromCode('PROCESSES');
        $ibActions = self::getIblockFromCode('TIMELIMIT_ACTIONS');

        $processes = CBPQBSLogActivity::takeElementsFromIB($ibProcesses['ID']);
        $actions = CBPQBSLogActivity::takeElementsFromIB($ibActions['ID']);

        if (!is_array($arCurrentValues)) {
            $arCurrentValues = array("processes" => $processes, "process_id" => "", "actions" => $actions, "action_id" => "");

            $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
            if (is_array($arCurrentActivity["Properties"])) {
                $arCurrentValues["processes"] = $processes;
                $arCurrentValues["process_id"] = $arCurrentActivity["Properties"]["process_id"];
                $arCurrentValues["actions"] = $actions;
                $arCurrentValues["action_id"] = $arCurrentActivity["Properties"]["action_id"];
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
            "process_id" => $arCurrentValues["process_id"],
            "action_id" => $arCurrentValues["action_id"],
        );

        $errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
        if (count($errors) > 0) {
            return false;
        }

        $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $currentActivity["Properties"] = $properties;

        return true;
    }

    public static function takeElementsFromIB(int $idIBlock): array
    {
        $elements = [];
        $arFilter = array('IBLOCK_ID' => $idIBlock, 'ACTIVE' => 'Y');
        $arIBlockNames = CIBlockElement::GetList(array(), $arFilter, false, false, $someParams = array('ID', 'NAME', 'ACTIVE'));
        while ($element = $arIBlockNames->GetNext()) {
            $elements[] = array(
                'ID' => $element['ID'],
                'NAME' => $element['NAME']
            );
        }
        return $elements;
    }

    public static function userFieldValue($id)
    {
        $UserField = CUserFieldEnum::GetList(array(), array("ID" => $id));
        if ($UserFieldAr = $UserField->GetNext()) {
            return $UserFieldAr["VALUE"];
        } else return false;
    }

    public static function getIblockFromCode(string $code)
    {
        $res = CIBlock::GetList(
            array(),
            array(
                'CODE' => $code,
            ), true
        );
        $ar_res = $res->Fetch();

        return $ar_res;
    }
}

