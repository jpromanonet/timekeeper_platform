<?php
/** @var string $selectLabel */
/** @var string $deleteLabel */
?>
<div class="bulk-bar">
    <label class="check">
        <input type="checkbox" data-select-all>
        <?= e($selectLabel) ?>
    </label>
    <button class="btn btn-danger" type="submit" data-bulk-submit disabled><?= e($deleteLabel) ?></button>
</div>
