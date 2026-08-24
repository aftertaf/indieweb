<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.webmentionsender
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Uri\Uri;

class PlgContentWebmentionsender extends CMSPlugin
{
    protected $app;

    public function onContentAfterSave($context, $article, $isNew)
    {
        if ($context !== 'com_content.article') return;
        if (!$this->app->isClient('site')) return;
        if ($article->state != 1) return;

        $url = Uri::root() . 'index.php?option=com_content&view=article&id=' . (int) $article->id;

        $fields = FieldsHelper::getFields('com_content.article', $article, true) ?? [];
        $fv = [];
        foreach ($fields as $field) {
            $fv[$field->name] = $field->value ?? '';
        }

        $targets = [];

        if (!empty($fv['in_reply_to'])) {
            $targets[] = trim($fv['in_reply_to']);
        }
        if (!empty($fv['like_of'])) {
            $targets[] = trim($fv['like_of']);
        }
        if (!empty($fv['repost_of'])) {
            $targets[] = trim($fv['repost_of']);
        }

        if (!empty($fv['syndication'])) {
            foreach (explode(',', $fv['syndication']) as $t) {
                $t = trim($t);
                if ($t) $targets[] = $t;
            }
        }

        if (!empty($fv['photo'])) {
            foreach (explode(',', $fv['photo']) as $t) {
                $t = trim($t);
                if ($t) $targets[] = $t;
            }
        }

        if (!empty($fv['mf_category'])) {
            foreach (explode(',', $fv['mf_category']) as $t) {
                $t = trim($t);
                if ($t) $targets[] = $t;
            }
        }

        $bodyLinks = $this->extractLinks($article->introtext . ' ' . $article->fulltext);
        $targets = array_merge($targets, $bodyLinks);
        $targets = array_unique(array_filter($targets));

        foreach ($targets as $target) {
            $this->queueWebmention($url, $target);
        }
    }

    protected function extractLinks(string $html): array
    {
        $links = [];
        if (preg_match_all('#https?://[^\s"<]+#i', $html, $m)) {
            $links = $m[0];
        }
        return $links;
    }

    protected function queueWebmention(string $source, string $target)
    {
        $db = Factory::getDbo();

        $obj = (object) [
            'source'      => $source,
            'target'      => $target,
            'status'      => 'pending',
            'created'     => gmdate('Y-m-d H:i:s'),
            'last_attempt'=> null,
            'response'    => null,
        ];

        $db->insertObject('#__webmention_queue', $obj);
    }
}
