<?php defined('_JEXEC') or die; ?>

<table class="table">
    <thead>
        <tr>
            <th>Source</th>
            <th>Target</th>
            <th>Status</th>
            <th>Created</th>
            <th>Last Attempt</th>
            <th>Response</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($this->items as $item): ?>
        <tr>
            <td><?php echo $item->source; ?></td>
            <td><?php echo $item->target; ?></td>
            <td><?php echo $item->status; ?></td>
            <td><?php echo $item->created; ?></td>
            <td><?php echo $item->last_attempt; ?></td>
            <td><?php echo $item->response; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
