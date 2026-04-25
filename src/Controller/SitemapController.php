<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\PostTypeRepository;
use App\Repository\TaxonomyRepository;
use App\Service\FrontContext;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends AbstractController
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly PostTypeRepository $postTypeRepository,
        private readonly TaxonomyRepository $taxonomyRepository,
        private readonly FrontContext $frontContext,
    ) {}

    #[Route('/sitemap.xml', name: 'front_sitemap', priority: 11)]
    public function sitemap(): Response
    {
        $urls = [];

        foreach ($this->frontContext->activeLocales() as $locale) {
            $urls[] = $this->urlEntry(
                $this->generateUrl('front_home', ['locale' => $locale->getCode()], UrlGeneratorInterface::ABSOLUTE_URL),
            );

            foreach ($this->postTypeRepository->findAll() as $postType) {
                if (!$postType->hasArchive()) {
                    continue;
                }

                $urls[] = $this->urlEntry(
                    $this->generateUrl('front_archive', [
                        'locale' => $locale->getCode(),
                        'postTypeSlug' => $postType->getSlug(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                );
            }
        }

        foreach ($this->postRepository->findAllPublishedForSitemap() as $post) {
            if ($post->getTranslation('fr')?->isNoindex()) {
                continue;
            }

            foreach ($post->getTranslations() as $translation) {
                $slug = $translation->getSlug();
                if (null === $slug) {
                    continue;
                }

                if ('' === $slug) {
                    continue;
                }

                if ($translation->isNoindex()) {
                    continue;
                }

                if (!$this->frontContext->isLocaleActive($translation->getLocale())) {
                    continue;
                }

                $urls[] = $this->urlEntry(
                    $this->generateUrl('front_post', [
                        'locale' => $translation->getLocale(),
                        'postTypeSlug' => $post->getPostType()->getSlug(),
                        'slug' => $slug,
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                    $post->getUpdatedAt()->format(DateTimeInterface::ATOM),
                );
            }
        }

        foreach ($this->taxonomyRepository->findAll() as $taxonomy) {
            foreach ($taxonomy->getTerms() as $term) {
                foreach ($this->frontContext->activeLocales() as $locale) {
                    $translation = $term->getTranslation($locale->getCode());
                    if (null === $translation) {
                        continue;
                    }

                    if ('' === $translation->getSlug()) {
                        continue;
                    }

                    $urls[] = $this->urlEntry(
                        $this->generateUrl('front_term', [
                            'locale' => $locale->getCode(),
                            'taxonomySlug' => $taxonomy->getSlug(),
                            'termSlug' => $translation->getSlug(),
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                    );
                }
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .implode('', $urls)
            .'</urlset>';

        return new Response($xml, Response::HTTP_OK, ['Content-Type' => 'application/xml']);
    }

    #[Route('/robots.txt', name: 'front_robots', priority: 11)]
    public function robots(): Response
    {
        $siteUrl = $this->frontContext->siteUrl();
        $body = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /dev/\n\nSitemap: {$siteUrl}/sitemap.xml\n";

        return new Response($body, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }

    #[Route('/{locale}/feed.xml', name: 'front_rss', requirements: ['locale' => '[a-z]{2}'], priority: 12)]
    public function rss(string $locale): Response
    {
        if (!$this->frontContext->isLocaleActive($locale)) {
            throw $this->createNotFoundException();
        }

        $postType = $this->postTypeRepository->findOneBy(['slug' => 'article']);
        $posts = null !== $postType
            ? $this->postRepository->findPublishedByPostType($postType->getId(), 1, 20, $locale)['items']
            : [];

        $siteUrl = $this->frontContext->siteUrl();
        $homeUrl = $this->generateUrl('front_home', ['locale' => $locale], UrlGeneratorInterface::ABSOLUTE_URL);
        $siteName = htmlspecialchars($this->frontContext->siteName(), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $siteDesc = htmlspecialchars($this->frontContext->siteDescription() ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');

        $items = '';
        foreach ($posts as $post) {
            $translation = $post->getTranslation($locale);
            if (null === $translation) {
                continue;
            }

            if (null === $translation->getSlug()) {
                continue;
            }

            $link = $this->generateUrl('front_post', [
                'locale' => $locale,
                'postTypeSlug' => $post->getPostType()->getSlug(),
                'slug' => $translation->getSlug(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $title = htmlspecialchars((string) $translation->getTitle(), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $description = htmlspecialchars((string) ($translation->getMetaDescription() ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $pubDate = ($post->getPublishedAt() ?? $post->getCreatedAt())->format(DateTimeInterface::RSS);

            $items .= <<<XML
                <item>
                    <title>{$title}</title>
                    <link>{$link}</link>
                    <guid isPermaLink="true">{$link}</guid>
                    <description>{$description}</description>
                    <pubDate>{$pubDate}</pubDate>
                </item>
                XML;
        }

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
                <channel>
                    <title>{$siteName}</title>
                    <link>{$homeUrl}</link>
                    <description>{$siteDesc}</description>
                    <language>{$locale}</language>
                    <atom:link xmlns:atom="http://www.w3.org/2005/Atom" href="{$siteUrl}/{$locale}/feed.xml" rel="self" type="application/rss+xml" />
                    {$items}
                </channel>
            </rss>
            XML;

        return new Response($xml, Response::HTTP_OK, ['Content-Type' => 'application/rss+xml']);
    }

    private function urlEntry(string $url, ?string $lastmod = null): string
    {
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $entry = sprintf('<url><loc>%s</loc>', $safeUrl);
        if (null !== $lastmod) {
            $entry .= sprintf('<lastmod>%s</lastmod>', $lastmod);
        }

        return $entry.'</url>';
    }
}
