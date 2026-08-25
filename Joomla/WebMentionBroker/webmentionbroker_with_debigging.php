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
use Joomla\CMS\Http\HttpFactory;

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
            $this->app->getLogger()->info('webmentionbroker: queue outgoing; source=' . $url . ' target=' . $target);
            $this->queueWebmention($url, $target);
        }
    }

    /**
     * Handle incoming Webmention POST
     */
    protected function handleIncoming()
    {
        $input = $this->app->input;

        // Determine HTTP method safely
        $method = null;
        if (method_exists($input, 'getMethod')) {
            $method = $input->getMethod();
        } elseif (!empty($_SERVER['REQUEST_METHOD'])) {
            $method = $_SERVER['REQUEST_METHOD'];
        } else {
            $method = 'GET';
        }

        $this->app->getLogger()->info('webmentionbroker: handleIncoming invoked; method=' . $method);

        if (strtoupper($method) !== 'POST') {
            return $this->respond(405, 'Webmention endpoint requires POST');
        }

        $source = $input->post->getString('source', '');
        $target = $input->post->getString('target', '');

        if (!$source || !$target) {
            return $this->respond(400, 'Missing source or target');
        }

        // Try to fetch the source using Joomla HTTP client; do not fail the request if fetch fails
        $html = '';
        try {
            $http = HttpFactory::getHttp();
            $response = $http->get($source, ['User-Agent' => 'aftertaf-webmention/1.0'], 10);
            $html = $response->body ?? '';
        } catch (\Throwable $e) {
            $this->app->getLogger()->info('webmentionbroker: fetch deferred; will verify async: ' . $e->getMessage());
            $html = '';
        }

        $type = $html ? $this->detectType($html) : 'mention';

        $db = Factory::getDbo();
        $obj = (object) [
            'source'  => $source,
            'target'  => $target,
            'type'    => $type,
            'status'  => ($html ? 'verified' : 'pending'),
            'created' => gmdate('Y-m-d H:i:s'),
        ];

        try {
            $db->insertObject('#__webmention_received', $obj);
            $id = $db->insertid();
            $this->app->getLogger()->info('webmentionbroker: webmention queued; id=' . $id . ' status=' . $obj->status);
        } catch (\Throwable $e) {
            $this->app->getLogger()->error('webmentionbroker: insert failed: ' . $e->getMessage());
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
            $db->insertObject('#__webmention_received', $obj);
            $id = $db->insertid();
            $this->app->getLogger()->info('webmentionbroker: queued outgoing webmention; id=' . $id);
        } catch (\Throwable $e) {
            $this->app->getLogger()->error('webmentionbroker: queue insert failed: ' . $e->getMessage());
        }
    }

    /**
     * JSON response helper
     */
    protected function respond(int $code, string $message)
    {
        $body = json_encode(['message' => $message]);
        http_response_code($code);
        $this->app->setHeader('Content-Type', 'application/json', true);
        $this->app->setBody($body);
        $this->app->getLogger()->info('webmentionbroker: respond called; code=' . $code);
        $this->app->close();
    }
}
