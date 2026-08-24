<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.webmentionreceiver
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

class PlgSystemWebmentionreceiver extends CMSPlugin
{
    protected $app;
    protected $db;

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->app = Factory::getApplication();
        $this->db  = Factory::getContainer()->get(DatabaseInterface::class);
    }

    public function onAfterRoute()
    {
        if (!$this->app->isClient('site')) {
            return;
        }

        $path = trim(parse_url(Uri::current(), PHP_URL_PATH), '/');
        if ($path === 'webmention') {
            $this->handleWebmention();
            $this->app->close();
        }
    }

    protected function handleWebmention()
    {
        if ($this->app->getMethod() !== 'POST') {
            return $this->respond(405, 'Webmention endpoint requires POST');
        }

        $input  = $this->app->input;
        $source = trim($input->post->getString('source', ''));
        $target = trim($input->post->getString('target', ''));

        if (!$source || !$target) {
            return $this->respond(400, 'Missing source or target');
        }

        $base = rtrim(Uri::root(), '/');
        if (strpos($target, $base) !== 0) {
            return $this->respond(400, 'Target is not on this site');
        }

        $mf = $this->fetchAndParseSource($source, $target);
        if ($mf === null) {
            return $this->respond(400, 'Source does not link to target or could not be parsed');
        }

        $this->storeMention($source, $target, $mf);
        return $this->respond(202, 'Webmention accepted');
    }

    protected function fetchAndParseSource(string $source, string $target): ?array
    {
        $html = @file_get_contents($source);
        if (!$html || strpos($html, $target) === false) {
            return null;
        }

        $mf = [
            'author'   => null,
            'content'  => null,
            'published'=> null,
            'type'     => 'mention',
        ];

        if (preg_match('/class=["\']p-author.*?>(.*?)<\/[^>]+>/is', $html, $m)) {
            $mf['author'] = strip_tags($m[1]);
        }

        if (preg_match('/class=["\']e-content.*?>(.*?)<\/[^>]+>/is', $html, $m)) {
            $mf['content'] = trim(strip_tags($m[1]));
        }

        if (preg_match('/class=["\']dt-published.*?datetime=["\']([^"\']+)/i', $html, $m)) {
            $mf['published'] = $m[1];
        }

        if (strpos($html, 'u-in-reply-to') !== false) $mf['type'] = 'reply';
        if (strpos($html, 'u-like-of')     !== false) $mf['type'] = 'like';
        if (strpos($html, 'u-repost-of')   !== false) $mf['type'] = 'repost';

        return $mf;
    }

    protected function storeMention(string $source, string $target, array $mf): void
    {
        $query = $this->db->getQuery(true)
            ->insert('#__webmentions')
            ->columns(['source','target','author','content','published','type','created'])
            ->values(implode(',', [
                $this->db->quote($source),
                $this->db->quote($target),
                $this->db->quote($mf['author'] ?? ''),
                $this->db->quote($mf['content'] ?? ''),
                $this->db->quote($mf['published'] ?? ''),
                $this->db->quote($mf['type'] ?? 'mention'),
                $this->db->quote(gmdate('Y-m-d H:i:s')),
            ]));

        $this->db->setQuery($query)->execute();
    }

    protected function respond(int $code, string $message)
    {
        $this->app->setHeader('Content-Type', 'text/plain', true);
        $this->app->setHeader('Status', $code . ' ' . $message, true);
        $this->app->setBody($message);
    }
}