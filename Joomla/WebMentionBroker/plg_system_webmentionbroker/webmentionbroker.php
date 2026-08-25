<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.webmentionbroker
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class PlgSystemWebmentionbroker extends CMSPlugin
{
    protected $app;

    /**
     * Incoming Webmention routing
     */
    public function onAfterInitialise()
    {
        if (!$this->app->isClient('site')) {
            return;
        }

        $path = trim(parse_url($this->app->input->server->getString('REQUEST_URI', ''), PHP_URL_PATH), '/');

        if ($path === 'webmention') {
            $this->handleIncoming();
            $this->app->close();
        }
    }

    /**
     * Outgoing Webmentions on article save
     */
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

        foreach (['in_reply_to', 'like_of', 'repost_of'] as $key) {
            if (!empty($fv[$key])) {
                $targets[] = trim($fv[$key]);
            }
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

    /**
     * Handle incoming Webmention POST
     */
    protected function handleIncoming()
    {
        $input = $this->app->input;

        if ($this->app->getMethod() !== 'POST') {
            return $this->respond(405, 'Webmention endpoint requires POST');
        }

        $source = $input->post->getString('source', '');
        $target = $input->post->getString('target', '');

        if (!$source || !$target) {
            return $this->respond(400, 'Missing source or target');
        }

        try {
            $html = @file_get_contents($source);
        } catch (\Exception $e) {
            return $this->respond(400, 'Unable to fetch source');
        }

        if (!$html) {
            return $this->respond(400, 'Unable to fetch source');
        }

        $type = $this->detectType($html);

        $db = Factory::getDbo();
        $obj = (object) [
            'source'  => $source,
            'target'  => $target,
            'type'    => $type,
            'created' => gmdate('Y-m-d H:i:s'),
        ];

        try {
            $db->insertObject('#__webmention_received', $obj);
        } catch (\Exception $e) {
            return $this->respond(500, 'Database error');
        }

        return $this->respond(202, 'Webmention accepted');
    }

    /**
     * Detect Webmention type
     */
    protected function detectType(string $html): string
    {
        if (strpos($html, 'u-in-reply-to') !== false) return 'reply';
        if (strpos($html, 'u-like-of') !== false) return 'like';
        if (strpos($html, 'u-repost-of') !== false) return 'repost';
        if (strpos($html, 'u-follow-of') !== false) return 'follow';
        return 'mention';
    }

    /**
     * Extract links from HTML
     */
    protected function extractLinks(string $html): array
    {
        if (preg_match_all('#https?://[^\s"<]+#i', $html, $m)) {
            return $m[0];
        }
        return [];
    }

    /**
     * Queue outgoing Webmention
     */
    protected function queueWebmention(string $source, string $target)
    {
        $db = Factory::getDbo();

        $obj = (object) [
            'source'       => $source,
            'target'       => $target,
            'status'       => 'pending',
            'created'      => gmdate('Y-m-d H:i:s'),
            'last_attempt' => null,
            'response'     => null,
        ];

        try {
            $db->insertObject('#__webmention_queue', $obj);
        } catch (\Exception $e) {
            // fail silently — do not break Joomla
        }
    }

    /**
     * JSON response helper
     */
    protected function respond(int $code, string $message)
    {
        $this->app->setHeader('Content-Type', 'application/json', true);
        $this->app->setHeader('Status', $code, true);
        $this->app->setBody(json_encode(['message' => $message]));
    }
}
