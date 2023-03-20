<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
\Bitrix\Main\Loader::includeModule('crm');
?>
<tr>
    <td><span class="adm-required-field"><?= GetMessage("QBS_CHILDSMARTPROCESSGETTER_CHILDSELECT") ?>:</span></td>
    <td>
        <select name="child_id">
            <?php foreach($arCurrentValues['child'] as $child): ?>
                <option value="<?= $child['ENTITY_TYPE_ID']; ?>" <?php if($child['ENTITY_TYPE_ID'] == $arCurrentValues['child_id'] ):?>selected<?php endif;?> ><?= $child['TITLE']; ?></option>
            <?php endforeach; ?>
        </select>

    </td>
</tr>
