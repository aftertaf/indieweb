<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.webmentionsender
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class PlgContentWebmentionsender extends CMSPlugin
{
    protected $app;

    public function onContentAfterSave($context, $article, $isNew)
    {
        if ($context !== 'com_content.article') return;

        $content = $article->introtext . $article->fulltext;
        preg_match_all('/https?:\/\/[^\s"\'<>]+/', $content, $matches);

        $links = array_unique($matches[0]);
        $source = Uri::root() . 'index.php?option=com_content&view=article&id=' . $article->id;

        foreach ($links as $target) {
            $this->sendWebmention($source, $target);
        }
    }

    protected function sendWebmention($source, $target)
    {
        $endpoint = $this->discoverEndpoint($target);
        if (!$endpoint) return;

        $data = http_build_query(['source' => $source, 'target' => $target]);

        @file_get_contents($endpoint, false, stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $data,
                'timeout' => 5,
            ]
        ]));
    }

    protected function discoverEndpoint($url)
    {
        $html = @file_get_contents($url);
        if (!$html) return null;

        if (preg_match('/rel=["\']webmention["\'].*?href=["\']([^"\']+)/i', $html, $m)) {
            return $m[1];
        }

        if (preg_match('/href=["\']([^"\']+)["\'].*?rel=["\']webmention["\']/i', $html, $m)) {
            return $m[1];
        }

        return null;
    }
}