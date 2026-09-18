<?php

namespace App\Support;

/**
 * Turns a plain-text comment body into safe HTML: escape everything first,
 * then wrap bare http(s) URLs in anchor tags. Unlike AnnouncementHtmlSanitizer
 * (which cleans real HTML produced by the Tiptap editor), comments never
 * contain markup to begin with — escaping before any tag is introduced makes
 * injection impossible regardless of what a request sends as `body`.
 * Line breaks are left as literal characters; the frontend renders them via
 * `white-space: pre-wrap` rather than converting them to <br> here.
 */
class CommentTextLinkifier
{
    public static function linkify(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        return preg_replace_callback(
            '/\bhttps?:\/\/\S+/i',
            function (array $match) {
                $full = $match[0];
                $url = rtrim($full, '.,!?;:)]}');
                $trailing = substr($full, strlen($url));

                return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'.$url.'</a>'.$trailing;
            },
            $escaped
        );
    }
}
