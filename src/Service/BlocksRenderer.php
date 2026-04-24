<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Render Editor.js blocks to HTML for public front-end output.
 * Mirrors the minimal behaviour of the admin-side blocksRenderer.js but
 * runs server-side for SEO-friendly static HTML.
 */
final readonly class BlocksRenderer
{
    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    public function render(array $blocks): string
    {
        $output = '';
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            $output .= $this->renderBlock($block);
        }

        return $output;
    }

    private function renderBlock(array $block): string
    {
        $type = (string) ($block['type'] ?? '');
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        return match ($type) {
            'header', 'heading' => $this->renderHeader($data),
            'paragraph' => $this->renderParagraph($data),
            'list' => $this->renderList($data),
            'checklist' => $this->renderChecklist($data),
            'quote' => $this->renderQuote($data),
            'code' => $this->renderCode($data),
            'delimiter' => '<hr class="my-8 border-line">',
            'image' => $this->renderImage($data),
            'embed' => $this->renderEmbed($data),
            'table' => $this->renderTable($data),
            'callout' => $this->renderCallout($data),
            'twoColumn' => $this->renderTwoColumn($data),
            'mediaText' => $this->renderMediaText($data),
            default => '',
        };
    }

    private function renderHeader(array $data): string
    {
        $level = (int) ($data['level'] ?? 2);
        $level = max(1, min(6, $level));
        $text = $this->safeHtml($data['text'] ?? '');

        return sprintf('<h%d>%s</h%d>', $level, $text, $level);
    }

    private function renderParagraph(array $data): string
    {
        return sprintf('<p>%s</p>', $this->safeHtml($data['text'] ?? ''));
    }

    private function renderList(array $data): string
    {
        $style = 'ordered' === ($data['style'] ?? 'unordered') ? 'ol' : 'ul';
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $itemsHtml = '';
        foreach ($items as $item) {
            if (is_string($item)) {
                $itemsHtml .= sprintf('<li>%s</li>', $this->safeHtml($item));
            } elseif (is_array($item) && isset($item['content'])) {
                $itemsHtml .= sprintf('<li>%s</li>', $this->safeHtml((string) $item['content']));
            }
        }

        return sprintf('<%s>%s</%s>', $style, $itemsHtml, $style);
    }

    private function renderChecklist(array $data): string
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $html = '<ul class="checklist">';
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $text = $this->safeHtml($item['text'] ?? '');
            $checked = ($item['checked'] ?? false) ? 'checked' : '';
            $html .= sprintf('<li><input type="checkbox" disabled %s> %s</li>', $checked, $text);
        }

        return $html.'</ul>';
    }

    private function renderQuote(array $data): string
    {
        $text = $this->safeHtml($data['text'] ?? '');
        $caption = $this->safeHtml($data['caption'] ?? '');

        return sprintf(
            '<blockquote><p>%s</p>%s</blockquote>',
            $text,
            '' !== $caption ? sprintf('<cite>%s</cite>', $caption) : '',
        );
    }

    private function renderCode(array $data): string
    {
        $code = htmlspecialchars((string) ($data['code'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return sprintf('<pre><code>%s</code></pre>', $code);
    }

    private function renderImage(array $data): string
    {
        $file = is_array($data['file'] ?? null) ? $data['file'] : [];
        $url = htmlspecialchars((string) ($file['url'] ?? ''), ENT_QUOTES, 'UTF-8');
        if ('' === $url) {
            return '';
        }
        $alt = htmlspecialchars((string) ($data['caption'] ?? ''), ENT_QUOTES, 'UTF-8');
        $caption = $this->safeHtml($data['caption'] ?? '');

        return sprintf(
            '<figure><img src="%s" alt="%s" loading="lazy">%s</figure>',
            $url,
            $alt,
            '' !== $caption ? sprintf('<figcaption>%s</figcaption>', $caption) : '',
        );
    }

    private function renderEmbed(array $data): string
    {
        $embedUrl = htmlspecialchars((string) ($data['embed'] ?? ''), ENT_QUOTES, 'UTF-8');
        if ('' === $embedUrl) {
            return '';
        }

        return sprintf(
            '<div class="embed"><iframe src="%s" frameborder="0" allowfullscreen loading="lazy"></iframe></div>',
            $embedUrl,
        );
    }

    private function renderTable(array $data): string
    {
        $rows = is_array($data['content'] ?? null) ? $data['content'] : [];
        $withHeadings = (bool) ($data['withHeadings'] ?? false);
        $html = '<table>';
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $tag = ($withHeadings && 0 === $index) ? 'th' : 'td';
            $cells = '';
            foreach ($row as $cell) {
                $cells .= sprintf('<%s>%s</%s>', $tag, $this->safeHtml((string) $cell), $tag);
            }
            $html .= '<tr>'.$cells.'</tr>';
        }

        return $html.'</table>';
    }

    private function renderCallout(array $data): string
    {
        $type = (string) ($data['type'] ?? 'info');
        $text = $this->safeHtml($data['text'] ?? '');

        return sprintf('<aside class="callout callout-%s">%s</aside>', htmlspecialchars($type, ENT_QUOTES, 'UTF-8'), $text);
    }

    private function renderTwoColumn(array $data): string
    {
        $left = is_array($data['left'] ?? null) ? $this->render($data['left']) : '';
        $right = is_array($data['right'] ?? null) ? $this->render($data['right']) : '';

        return sprintf('<div class="two-column"><div>%s</div><div>%s</div></div>', $left, $right);
    }

    private function renderMediaText(array $data): string
    {
        $image = is_array($data['image'] ?? null) ? $data['image'] : [];
        $url = htmlspecialchars((string) ($image['url'] ?? ''), ENT_QUOTES, 'UTF-8');
        $text = $this->safeHtml($data['text'] ?? '');

        return sprintf(
            '<div class="media-text">%s<div>%s</div></div>',
            '' !== $url ? sprintf('<figure><img src="%s" alt="" loading="lazy"></figure>', $url) : '',
            $text,
        );
    }

    /**
     * Editor.js lets users input light HTML (b, i, a, code, etc.) in text
     * fields. We allow a minimal safe subset and escape everything else.
     */
    private function safeHtml(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        // Remove script/style tags entirely
        $value = preg_replace('#<(script|style|iframe)[^>]*>.*?</\1>#is', '', $value) ?? '';

        // Strip all tags except a short whitelist
        $allowed = '<a><b><strong><i><em><u><s><br><code><mark>';

        return strip_tags($value, $allowed);
    }
}
