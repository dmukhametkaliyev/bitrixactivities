<?php

use Bitrix\Main\Diag\Debug;
use Bitrix\Crm\Service;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');
Loader::includeModule('im');

class CBPQBSUserDepartmentHeadGetterActivity extends CBPActivity
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            'department_dir' => '',
            'errors' => ''

        );

        $this->SetPropertiesTypes([
            'department_dir' => [
                'Type' => \Bitrix\Bizproc\FieldType::USER,
            ],
            'errors' => [
                'Type' => \Bitrix\Bizproc\FieldType::TEXT,
            ]
        ]);
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    public function Execute()
    {
        $factory = Service\Container::getInstance()->getFactory($this->getDynamicID());
        $item = $factory->getItem($this->getObjectID());
        if ($item) {
            $responsibleID = $item->getAssignedById();
        }

        $rsUser = CUser::GetByID($responsibleID);
        $arUser = $rsUser->Fetch();

        if (count($arUser['UF_DEPARTMENT']) > 1) {
            $this->errors = 'Ошибка: Выбранный пользователь состоит в нескольких департаментах/отделах';

            //$this->writeToTrackingService('Ошибка: Выбранный пользователь состоит в нескольких департаментах/отделах');
            return CBPActivityExecutionStatus::Closed;
        }


        $headID = self::findDepartmentHead($arUser['UF_DEPARTMENT'][0], $responsibleID);

        if ($headID) {
            $this->department_dir = 'user_' . $headID;
            return CBPActivityExecutionStatus::Closed;
        } else {
            $this->errors = 'Ошибка: Не удалось найти раздел с требуемыми свойствами';
            //$this->writeToTrackingService('Ошибка: Не удалось найти раздел с требуемыми свойствами');
            return CBPActivityExecutionStatus::Closed;
        }

    }

    function findDepartmentHead($departmentID, $responsibleID)
    {
        $rsSection = CIBlockSection::GetList(array(), array('IBLOCK_ID' => 3, 'ID' => $departmentID), false, array('UF_*'));
        while ($arSection = $rsSection->Fetch()) {
            if ($arSection['UF_IS_DEPARTMENT'] == true && empty($arSection['UF_HEAD']) == false) {
                return $arSection['UF_HEAD'];
            } else {
                $parentID = $arSection['IBLOCK_SECTION_ID'];
                if ($parentID) {
                    return self::findDepartmentHead($parentID, $responsibleID);
                }
            }
        }
    }



    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
    {
        $runtime = CBPRuntime::GetRuntime();

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
        return true;
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