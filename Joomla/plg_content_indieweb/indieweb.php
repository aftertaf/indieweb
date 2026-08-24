<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.indieweb
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Table\Table;

class PlgContentIndieweb extends CMSPlugin
{
    protected $app;

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->app = Factory::getApplication();
    }

    /* --------------------------------------------------------------
     *  MICROPUB → CUSTOM FIELDS MAPPING
     * -------------------------------------------------------------- */

    public function onContentAfterSave($context, $article, $isNew)
    {
        if ($context !== 'com_content.article') {
            return;
        }

        $input = $this->app->input;

        // Micropub properties
        $mpContent  = $input->post->getString('content', '');
        $mpName     = $input->post->getString('name', '');
        $mpReplyTo  = $input->post->getString('in-reply-to', '');
        $mpLikeOf   = $input->post->getString('like-of', '');
        $mpRepostOf = $input->post->getString('repost-of', '');
        $mpPhoto    = $input->post->get('photo', [], 'array');
        $mpCategory = $input->post->get('category', [], 'array');
        $mpSynd     = $input->post->get('mp-syndicate-to', [], 'array');

        // Detect post type
        $postType = 'article';
        if ($mpReplyTo)  $postType = 'reply';
        if ($mpLikeOf)   $postType = 'like';
        if ($mpRepostOf) $postType = 'repost';

        // Store custom fields
        $id = (int) $article->id;

        $this->setField($id, 'post_type', $postType);
        $this->setField($id, 'in_reply_to', $mpReplyTo);
        $this->setField($id, 'like_of', $mpLikeOf);
        $this->setField($id, 'repost_of', $mpRepostOf);

        if (!empty($mpPhoto)) {
            $this->setField($id, 'photo', implode(',', $mpPhoto));
        }

        if (!empty($mpCategory)) {
            $this->setField($id, 'mf_category', implode(',', $mpCategory));
        }

        if (!empty($mpSynd)) {
            $this->setField($id, 'syndication', implode(',', $mpSynd));
        }
    }

    protected function setField(int $itemId, string $fieldName, string $value)
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->update('#__fields_values')
            ->set('value = ' . $db->quote($value))
            ->where('item_id = ' . (int) $itemId)
            ->where('field_id = (SELECT id FROM #__fields WHERE name = ' . $db->quote($fieldName) . ' LIMIT 1)');

        $db->setQuery($query)->execute();
    }

    /* --------------------------------------------------------------
     *  ARTICLE MICROFORMATS (your original logic)
     * -------------------------------------------------------------- */

    public function onContentBeforeDisplay($context, &$article, &$params, $limit = 0)
    {
        if ($limit !== 0) return '';
        if (!$this->app->isClient('site')) return '';
        if ($context !== 'com_content.article') return '';

        $fields = FieldsHelper::getFields('com_content.article', $article, true) ?? [];
        $fv = [];

        foreach ($fields as $field) {
            $fv[$field->name] = $field->value ?? '';
        }

        $postType  = $fv['post_type'] ?? 'article';
        $replyTo   = $fv['in_reply_to'] ?? '';
        $likeOf    = $fv['like_of'] ?? '';
        $repostOf  = $fv['repost_of'] ?? '';
        $photos    = array_filter(array_map('trim', explode(',', $fv['photo'] ?? '')));
        $cats      = array_filter(array_map('trim', explode(',', $fv['mf_category'] ?? '')));
        $syn       = array_filter(array_map('trim', explode(',', $fv['syndication'] ?? '')));

        $title = htmlspecialchars($article->title ?? '', ENT_QUOTES, 'UTF-8');
        $url   = Uri::current();

        $published = !empty($article->publish_up)
            ? HTMLHelper::_('date', $article->publish_up, 'c')
            : '';

        $updated = !empty($article->modified)
            ? HTMLHelper::_('date', $article->modified, 'c')
            : '';

        // Author h-card
        $authorUser = Factory::getUser($article->created_by ?: 0);
        $authorName = htmlspecialchars($authorUser->name ?? 'Unknown', ENT_QUOTES, 'UTF-8');
        $authorUrl  = Uri::base();

        $hcard = '<span class="p-author h-card">'
               . '<span class="p-name">' . $authorName . '</span>'
               . '<a class="u-url" href="' . htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8') . '"></a>'
               . '</span>';

        $out  = '<article class="h-entry">';

        if ($postType === 'note') {
            $out .= '<h1 class="p-name" style="display:none;">' . $title . '</h1>';
        } else {
            $out .= '<h1 class="p-name">' . $title . '</h1>';
        }

        $out .= '<a class="u-url" href="' . $url . '"></a>';

        if ($published) {
            $out .= '<time class="dt-published" datetime="' . $published . '"></time>';
        }

        if ($updated && $updated !== $published) {
            $out .= '<time class="dt-updated" datetime="' . $updated . '"></time>';
        }

        $out .= $hcard;

        if ($postType === 'reply' && $replyTo) {
            $out .= '<a class="u-in-reply-to" href="' . htmlspecialchars($replyTo, ENT_QUOTES, 'UTF-8') . '"></a>';
        }

        if ($postType === 'like' && $likeOf) {
            $out .= '<a class="u-like-of" href="' . htmlspecialchars($likeOf, ENT_QUOTES, 'UTF-8') . '"></a>';
        }

        if ($postType === 'repost' && $repostOf) {
            $out .= '<a class="u-repost-of" href="' . htmlspecialchars($repostOf, ENT_QUOTES, 'UTF-8') . '"></a>';
        }

        foreach ($photos as $p) {
            $out .= '<img class="u-photo" src="' . htmlspecialchars($p, ENT_QUOTES, 'UTF-8') . '" alt="">';
        }

        foreach ($cats as $c) {
            $out .= '<a class="u-category" href="#" rel="tag">' . htmlspecialchars($c, ENT_QUOTES, 'UTF-8') . '</a>';
        }

        foreach ($syn as $s) {
            $out .= '<a class="u-syndication" href="' . htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . '"></a>';
        }

        $out .= '<div class="e-content">';

        return $out;
    }

    public function onContentAfterDisplay($context, &$article, &$params, $limit = 0)
    {
        if ($limit !== 0) return '';
        if (!$this->app->isClient('site')) return '';
        if ($context !== 'com_content.article') return '';

        return '</div><a class="u-bridgy-fed" href="https://fed.brid.gy"></a></article>';
    }

    /* --------------------------------------------------------------
     *  HEAD METADATA (rel=me, IndieAuth, WebFinger, Webmention, Micropub)
     * -------------------------------------------------------------- */

    public function onBeforeCompileHead()
    {
        if (!$this->app->isClient('site')) return;

        $doc = $this->app->getDocument();
        $url = Uri::current();

        // ActivityPub discovery
        $doc->addHeadLink(
            'https://fed.brid.gy/r/' . $url,
            'alternate',
            'rel',
            ['type' => 'application/activity+json']
        );

        // rel=me links
        $relme = trim($this->params->get('relme', ''));
        if ($relme) {
            foreach (explode("\n", $relme) as $link) {
                $link = trim($link);
                if ($link) {
                    $doc->addHeadLink($link, 'me', 'rel');
                }
            }
        }

        // WebFinger LRDD
        $lrdd = trim($this->params->get('webfinger_url', ''));
        if ($lrdd) {
            $doc->addHeadLink(
                $lrdd,
                'lrdd',
                'rel',
                ['type' => 'application/xrd+xml']
            );
        }

        // IndieAuth
        $auth = trim($this->params->get('authorization_endpoint', ''));
        $token = trim($this->params->get('token_endpoint', ''));

        if ($auth)  $doc->addHeadLink($auth, 'authorization_endpoint', 'rel');
        if ($token) $doc->addHeadLink($token, 'token_endpoint', 'rel');

        // Webmention endpoint
        $doc->addHeadLink(Uri::root() . 'webmention', 'webmention', 'rel');

        // Micropub endpoint
        $doc->addHeadLink(Uri::root() . 'micropub', 'micropub', 'rel');
    }

    /* --------------------------------------------------------------
     *  GLOBAL HOMEPAGE h-card + Bridgy Fed trigger
     * -------------------------------------------------------------- */

    public function onAfterRender()
    {
        if (!$this->app->isClient('site')) return;

        $body = $this->app->getBody();

        $name  = htmlspecialchars($this->params->get('hcard_name', 'example-user'), ENT_QUOTES, 'UTF-8');
        $url   = htmlspecialchars($this->params->get('hcard_url', 'https://example.com'), ENT_QUOTES, 'UTF-8');
        $photo = htmlspecialchars($this->params->get('hcard_photo', ''), ENT_QUOTES, 'UTF-8');
        $note  = htmlspecialchars($this->params->get('hcard_note', ''), ENT_QUOTES, 'UTF-8');

        $hcard = '<div class="h-card" style="display:none;">';

        if ($photo) {
            $hcard .= '<img class="u-photo" src="' . $photo . '" alt="' . $name . '">';
        }

        $hcard .= '<a class="p-name u-url u-uid" rel="me" href="' . $url . '">' . $name . '</a>';

        if ($note) {
            $hcard .= '<p class="p-note">' . $note . '</p>';
        }

        $hcard .= '</div>';
        $hcard .= '<a class="u-bridgy-fed" href="https://fed.brid.gy"></a>';

        $body = str_replace('</body>', $hcard . '</body>', $body);
        $this->app->setBody($body);
    }
}
