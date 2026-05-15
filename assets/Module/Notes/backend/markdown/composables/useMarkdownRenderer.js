import { Marked } from 'marked';
import DOMPurify from 'dompurify';
import { createWikiLinkExtension, applyWikiLinksToHtml } from './markedExtensions/markedWikiLinks.js';
import { createCalloutExtension } from './markedExtensions/markedCallouts.js';
import { createCheckboxRenderer, resetCheckboxCounter } from './markedExtensions/markedCheckboxes.js';

/**
 * Builds a per-instance Marked parser with Aurora's note-specific
 * extensions (wiki-links, callouts, interactive checkboxes). DOMPurify
 * sanitizes the final HTML — we allow our custom `data-*` attributes so
 * click handlers can intercept wiki-links and checkboxes.
 *
 * Stateless: returns a `render(markdown)` function. Wiki-link and
 * checkbox extensions don't need closure state beyond the per-render
 * checkbox counter reset.
 */
export function useMarkdownRenderer() {
    const marked = new Marked({
        gfm: true,
        breaks: false,
    });
    marked.use({ extensions: [createWikiLinkExtension(), createCalloutExtension()] });
    marked.use({ renderer: createCheckboxRenderer() });

    function render(markdown) {
        if (!markdown) return '';
        resetCheckboxCounter();
        const rawHtml = marked.parse(markdown);
        const withWikiLinks = applyWikiLinksToHtml(rawHtml);
        return DOMPurify.sanitize(withWikiLinks, {
            ADD_ATTR: ['data-note-title', 'data-heading', 'data-checkbox-index', 'data-icon'],
        });
    }

    return { render };
}
