<?php defined('_JEXEC') or die; ?>

<table class="table">
    <thead>
        <tr>
            <th>Source</th>
            <th>Target</th>
            <th>Type</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($this->items as $item): ?>
        <tr>
            <td><?php echo $item->source; ?></td>
            <td><?php echo $item->target; ?></td>
            <td><?php echo $item->type; ?></td>
            <td><?php echo $item->created; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
