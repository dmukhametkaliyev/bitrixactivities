<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
\Bitrix\Main\Loader::includeModule('crm');
?>
<tr>
    <td align="right" width="40%" valign="top"><span
            class="adm-required-field"><?= GetMessage("QBS.TASKCOMPLETEACTIVITY.TASKID") ?>:</span></td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField("task_id", 'task_id', $arCurrentValues['task_id'], array('rows' => 1, 'cols' => 50)) ?>
    </td>
</tr>
<tr>
    <td align="right" width="40%" valign="top"><span
                class="adm-required-field"><?= GetMessage("QBS.TASKCOMPLETEACTIVITY.USERID") ?>:</span></td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField("user", 'user_id', $arCurrentValues['user_id'], array('rows' => 1, 'cols' => 50)) ?>
    </td>
</tr>

