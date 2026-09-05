<?php
/**
 * Allow-list sanitizer for the homepage rich-text fields.
 *
 * Those fields are edited with Quill and stored as HTML, then printed unescaped on
 * the public homepage - so without this, anyone who can reach the Content page could
 * store a <script> that runs in every visitor's browser. Escaping the output instead
 * isn't an option: the whole point of the field is to keep the editor's bold/italic/
 * list/link formatting.
 *
 * The allow-list is deliberately limited to what Quill's toolbar can actually produce
 * (bold, italic, bullet list, link) plus the wrappers it emits around them. Anything
 * else is unwrapped (kept as text) or, for tags that only exist to execute or embed
 * something, dropped with its contents.
 */

/** Tags kept as-is. Anything not listed is unwrapped, preserving its text. */
const RICH_TEXT_TAGS = [
    'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [],
    'u' => [], 's' => [], 'blockquote' => [],
    'ul' => [], 'ol' => [], 'li' => ['class', 'data-list'],
    'a' => ['href', 'target', 'rel'],
    'span' => ['class'],
];

/** Tags removed along with everything inside them - unwrapping these would leak the
 *  payload back out as text (e.g. the contents of a <script>). */
const RICH_TEXT_STRIP_ENTIRELY = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'noscript', 'template', 'link', 'meta', 'base'];

/** URL schemes a link may use. Everything else (javascript:, data:, vbscript: ...) is dropped. */
const RICH_TEXT_URL_SCHEMES = ['http', 'https', 'mailto', 'tel'];

/**
 * True if a link target is safe to keep. Relative URLs and anchors are fine; anything
 * with a scheme must use one of RICH_TEXT_URL_SCHEMES.
 *
 * Control characters and entity padding are stripped first, since "java\tscript:" and
 * "java&#09;script:" both still execute in browsers.
 */
function rich_text_url_is_safe(string $url): bool
{
    $clean = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $clean = preg_replace('/[\x00-\x20\x7F]/', '', $clean) ?? '';
    if ($clean === '') return false;
    // No scheme at all: relative path, anchor or query - nothing can execute.
    if (!preg_match('~^([a-z][a-z0-9+.\-]*):~i', $clean, $m)) return true;
    return in_array(strtolower($m[1]), RICH_TEXT_URL_SCHEMES, true);
}

/** Recursively clean one node's subtree in place. */
function rich_text_clean_node(DOMNode $node): void
{
    // Walk a copy of the child list - the loop rewrites the real one as it goes.
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child instanceof DOMText) continue;

        if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
            $child->parentNode->removeChild($child);
            continue;
        }

        if (!$child instanceof DOMElement) {
            $child->parentNode->removeChild($child);
            continue;
        }

        $tag = strtolower($child->nodeName);

        if (in_array($tag, RICH_TEXT_STRIP_ENTIRELY, true)) {
            $child->parentNode->removeChild($child);
            continue;
        }

        if (!array_key_exists($tag, RICH_TEXT_TAGS)) {
            // Not allowed, but harmless in itself - keep the text, drop the tag.
            rich_text_clean_node($child);
            while ($child->firstChild) {
                $child->parentNode->insertBefore($child->firstChild, $child);
            }
            $child->parentNode->removeChild($child);
            continue;
        }

        $allowedAttrs = RICH_TEXT_TAGS[$tag];
        foreach (iterator_to_array($child->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            if (!in_array($name, $allowedAttrs, true)) {
                $child->removeAttribute($attr->nodeName);
                continue;
            }
            if ($name === 'href' && !rich_text_url_is_safe($attr->nodeValue ?? '')) {
                $child->removeAttribute($attr->nodeName);
            }
        }

        // Any link that survives opens in a new tab without handing the target page a
        // window.opener reference back to the site.
        if ($tag === 'a' && $child->hasAttribute('href')) {
            if ($child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
        }

        rich_text_clean_node($child);
    }
}

/**
 * Returns the HTML with everything outside the allow-list removed. Safe to print
 * unescaped; safe to run on already-clean content (it's idempotent).
 */
function sanitize_rich_text(?string $html): string
{
    $html = trim((string) $html);
    if ($html === '') return '';

    // libxml assumes Latin-1 for a fragment with no charset declaration, which would
    // mangle any non-ASCII character. Turning those into numeric entities first keeps
    // the input pure ASCII through the parse; they're decoded again on the way out.
    // A charset <meta> can't be used instead: with LIBXML_HTML_NOIMPLIED libxml keeps
    // only the first element as the root and silently discards everything after it.
    $encoded = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x10FFFF], 'UTF-8');

    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $doc->loadHTML(
        '<div>' . $encoded . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$loaded) return '';

    $root = $doc->documentElement;
    if (!$root) return '';

    rich_text_clean_node($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    $out = mb_decode_numericentity($out, [0x80, 0x10FFFF, 0, 0x10FFFF], 'UTF-8');
    return trim($out);
}
