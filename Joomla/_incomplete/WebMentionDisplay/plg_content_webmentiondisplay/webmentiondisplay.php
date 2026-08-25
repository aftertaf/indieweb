<?php

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

class PlgContentWebmentiondisplay extends CMSPlugin
{
    public function onContentAfterDisplay($context, $article, $params)
    {
        if ($context !== 'com_content.article') return '';

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__webmention_received')
            ->where('target LIKE ' . $db->quote('%id=' . (int) $article->id))
            ->order('created DESC');

        $db->setQuery($query);
        $items = $db->loadObjectList();

        if (!$items) return '';

        ob_start();
        ?>
        <div class="webmentions">
            <h3>Mentions</h3>
            <ul>
                <?php foreach ($items as $item): ?>
                    <li>
                        <strong><?php echo $item->type; ?></strong> —
                        <a href="<?php echo $item->source; ?>"><?php echo $item->source; ?></a>
                        (<?php echo $item->created; ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
}
