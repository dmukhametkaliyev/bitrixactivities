<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
\Bitrix\Main\Loader::includeModule('iblock');
?>
<tr>
    <td><span class="adm-required-field"><?= GetMessage("BPCAL_PD_SELECT") ?>:</span></td>
    <td>
        <select name="process_id">
            <?php foreach($arCurrentValues['processes'] as $process): ?>
                <option value="<?= $process['ID']; ?>" <?php if($process['ID'] == $arCurrentValues['process_id'] ):?>selected<?php endif;?> ><?= $process['NAME']; ?></option>
            <?php endforeach; ?>
        </select>
    </td>   
</tr>
<tr>
    <td><span class="adm-required-field"><?= GetMessage("BPCAL_PD_ACTIONSELECT") ?>:</span></td>
    <td>
        <select name="action_id">
            <?php foreach($arCurrentValues['actions'] as $action): ?>
                <option value="<?= $action['ID']; ?>" <?php if($action['ID'] == $arCurrentValues['action_id'] ):?>selected<?php endif;?> ><?= $action['NAME']; ?></option>
            <?php endforeach; ?>
        </select>

    </td>
</tr>