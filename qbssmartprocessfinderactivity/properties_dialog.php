<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
\Bitrix\Main\Loader::includeModule('crm');
?>
<tr>
    <td><span class="adm-required-field"><?= GetMessage("QBS.SMARTPROCESSFINDER.SELECTPROCESS") ?>:</span></td>
    <td>
        <select name="process_id">
            <?php foreach ($arCurrentValues['processes'] as $process): ?>
                <option value="<?= $process['ENTITY_TYPE_ID']; ?>"
                        <?php if ($process['ENTITY_TYPE_ID'] == $arCurrentValues['process_id']): ?>selected<?php endif; ?> ><?= $process['TITLE']; ?></option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>
<!--<tr>
    <td><span class="adm-required-field"><?= GetMessage("QBS.SMARTPROCESSFINDER.SELECTFIELD") ?>:</span></td>
    <td>
        <select name="field_id">
            <?php foreach ($arCurrentValues['fields'] as $field): ?>
                <option value="<?= $field['TITLE']; ?>" <?php if ($field['ID'] == $arCurrentValues['field_id']): ?>selected<?php endif; ?> ><?= $field['TITLE']; ?></option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>-->
<tr>
    <td><span class="adm-required-field"><?= GetMessage("QBS.SMARTPROCESSFINDER.SELECTFIELD") ?>:</span></td>
    <td>
        <select name="field_key">
            <?php foreach ($arCurrentValues['fields'] as $key => $value): ?>
                <option value="<?= $key; ?>"
                        <?php if ($key == $arCurrentValues['field_key']): ?>selected<?php endif; ?> ><?= $value['TITLE']; ?></option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>
<tr>
    <td align="right" width="40%" valign="top"><span
                class="adm-required-field"><?= GetMessage("QBS.SMARTPROCESSFINDER.VALUE") ?>:</span></td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField("field_value", 'field_value', $arCurrentValues['field_value'], array('rows' => 1, 'cols' => 50)) ?>
    </td>
</tr>
