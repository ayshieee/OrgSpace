<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * A narrow, allowlist-based sanitizer for the specific set of tags the
 * Tiptap composer can ever produce. Deliberately not a general-purpose HTML
 * purifier (no new Composer dependency for it) — the editor's own schema is
 * already this constrained, so the backend only needs to enforce that same
 * constraint against a request that bypasses the editor entirely.
 */
class AnnouncementHtmlSanitizer
{
    protected const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'blockquote', 'mark', 'a'];

    public static function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        // Wrapped so DOMDocument doesn't invent a <html><body> around a
        // fragment; the wrapper is stripped back off below.
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="__root__">'.$html.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $root = $dom->getElementById('__root__');

        if (! $root) {
            return '';
        }

        self::cleanNode($dom, $root);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    protected static function cleanNode(DOMDocument $dom, DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // script/style content is discarded outright (never keep
                // their text); any other disallowed wrapper is unwrapped
                // but its inner (already-sanitized-below) content is kept.
                if (in_array($tag, ['script', 'style'], true)) {
                    $node->removeChild($child);

                    continue;
                }

                self::cleanNode($dom, $child);

                $fragment = $dom->createDocumentFragment();
                foreach (iterator_to_array($child->childNodes) as $inner) {
                    $fragment->appendChild($inner);
                }
                $node->replaceChild($fragment, $child);

                continue;
            }

            self::cleanAttributes($child, $tag);
            self::cleanNode($dom, $child);
        }
    }

    /**
     * Captures the one attribute each allowed tag legitimately needs
     * *before* stripping everything, then re-applies only a validated
     * version of it — every other attribute (onerror, style on non-<mark>
     * tags, class, id, etc.) is gone unconditionally.
     */
    protected static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $safeHref = $tag === 'a' ? self::sanitizeUrl($el->getAttribute('href')) : null;
        $safeHighlight = $tag === 'mark' ? self::sanitizeHighlightColor($el->getAttribute('data-color')) : null;

        foreach (iterator_to_array($el->attributes) as $attr) {
            $el->removeAttribute($attr->name);
        }

        if ($tag === 'a') {
            if ($safeHref === null) {
                return;
            }

            $el->setAttribute('href', $safeHref);
            $el->setAttribute('target', '_blank');
            $el->setAttribute('rel', 'noopener noreferrer');
        }

        if ($tag === 'mark' && $safeHighlight !== null) {
            $el->setAttribute('style', $safeHighlight);
        }
    }

    protected static function sanitizeUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $url = trim($url);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        // Relative URLs (no scheme) are rejected too — announcements only
        // ever link out, never to internal app routes via this field.
        if ($scheme === null || ! in_array(strtolower($scheme), ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }

    /**
     * Reads the hex color from `data-color` (set by Tiptap's Highlight
     * extension from the exact palette value we applied) rather than the
     * `style` attribute — the browser re-serializes `style` to
     * `rgb(...); color: inherit;` on read, which never matches a strict hex
     * pattern. We reconstruct `style` ourselves from the validated hex, so
     * nothing from the input is ever trusted into the output CSS.
     */
    protected static function sanitizeHighlightColor(?string $color): ?string
    {
        if (! $color) {
            return null;
        }

        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', trim($color))) {
            return 'background-color: '.trim($color).';';
        }

        return null;
    }
}
