<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
\Bitrix\Main\Loader::includeModule('crm');
?>
<!--<tr>
    <td><span class="adm-required-field"><?= GetMessage("QBS_POACTIVITY_PARENTSELECT") ?>:</span></td>
    <td>
        <select name="parent_id">
            <?php foreach($arCurrentValues['parent'] as $parent): ?>
                <option value="<?= $parent['ID']; ?>" <?php if($parent['ID'] == $arCurrentValues['parent_id'] ):?>selected<?php endif;?> ><?= $parent['TITLE']; ?></option>
            <?php endforeach; ?>
        </select>
    </td>
</tr> -->
<tr>
    <td><span class="adm-required-field"><?= GetMessage("QBS_POACTIVITY_CHILDSELECT") ?>:</span></td>
    <td>
        <select name="child_id">
            <?php foreach($arCurrentValues['child'] as $child): ?>
                <option value="<?= $child['ENTITY_TYPE_ID']; ?>" <?php if($child['ENTITY_TYPE_ID'] == $arCurrentValues['child_id'] ):?>selected<?php endif;?> ><?= $child['TITLE']; ?></option>
            <?php endforeach; ?>
        </select>

    </td>
</tr>
