<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>
<tr>
    <td><span class="adm-required-field"><?= GetMessage("QBS.CHILDRENSMARTPROCESSAPPROVE.SELECT") ?>:</span></td>
    <td>
        <select name="process_id">
            <?php foreach ($arCurrentValues['processes'] as $process): ?>
                <option value="<?= $process['ENTITY_TYPE_ID']; ?>"
                        <?php if ($process['ENTITY_TYPE_ID'] == $arCurrentValues['process_id']): ?>selected<?php endif; ?> ><?= $process['TITLE']; ?></option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>
<tr>
    <td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("QBS.CHILDRENSMARTPROCESSAPPROVE.APPROVERS") ?>:</span></td>
    <td width="60%">
        <?=CBPDocument::ShowParameterField("user", 'Users', $arCurrentValues['Users'], Array('rows'=>'2'))?>
    </td>
</tr>
<tr>
    <td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("QBS.CHILDRENSMARTPROCESSAPPROVE.NAME") ?>:</span></td>
    <td width="60%">
        <?=CBPDocument::ShowParameterField("string", 'task_name', $arCurrentValues['task_name'], Array('size'=>'50'))?>
    </td>
</tr>
<tr>
    <td align="right" width="40%" valign="top"><?= GetMessage("QBS.CHILDRENSMARTPROCESSAPPROVE.DESCR") ?>:</td>
    <td width="60%" valign="top">
        <?=CBPDocument::ShowParameterField("text", 'task_description', $arCurrentValues['task_description'], Array('rows'=>'7'))?>
    </td>
</tr>
