<?php

use App\Core\Controller\Controller;
use App\Services\CelebrityReportService;
use App\Services\ArchetypeService;
use App\Services\ShareableService;
use App\Services\DailyVibeService;
use Psr\Log\LoggerInterface;

/**
 * SitemapController
 * 
 * Refactored to use service layer architecture following the refactoring plan.
 * Now uses dependency injection instead of direct database access.
 */
class SitemapController extends Controller {
    private $celebrityReportService;
    private $archetypeService;
    private $shareableService;
    private $dailyVibeService;
    private $logger;
    
    public function __construct(LoggerInterface $logger) {
        parent::__construct();
        $this->celebrityReportService = $this->container->get(CelebrityReportService::class);
        $this->archetypeService = $this->container->get(ArchetypeService::class);
        $this->shareableService = $this->container->get(ShareableService::class);
        $this->dailyVibeService = $this->container->get(DailyVibeService::class);
        $this->logger = $logger;
    }
    
    public function generateSitemap() {
        header('Content-Type: application/xml; charset=utf-8');
        
        $baseUrl = 'https://cosmichub.online';
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Create urlset element
        $urlset = $xml->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $urlset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $urlset->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
        $xml->appendChild($urlset);
        
        // Add homepage
        $this->addUrl($xml, $urlset, $baseUrl, '1.0', 'daily', date('c'));
        
        // Add static pages
        $staticPages = [
            '/celebrity-reports' => ['priority' => '0.9', 'changefreq' => 'weekly'],
            '/compatibility' => ['priority' => '0.8', 'changefreq' => 'monthly'],
            '/archetypes' => ['priority' => '0.7', 'changefreq' => 'monthly'],
            '/daily-vibe' => ['priority' => '0.6', 'changefreq' => 'daily']
        ];
        
        foreach ($staticPages as $page => $config) {
            $this->addUrl($xml, $urlset, $baseUrl . $page, $config['priority'], $config['changefreq']);
        }
        
        // Add celebrity reports (main SEO content)
        $this->addCelebrityReports($xml, $urlset, $baseUrl);
        
        // Add archetype pages
        $this->addArchetypes($xml, $urlset, $baseUrl);
        
        // Add public shareables
        $this->addPublicShareables($xml, $urlset, $baseUrl);
        
        // Add daily vibe entries
        $this->addDailyVibes($xml, $urlset, $baseUrl);
        
        echo $xml->saveXML();
    }
    
    private function addUrl($xml, $urlset, $url, $priority = '0.5', $changefreq = 'monthly', $lastmod = null) {
        $urlElement = $xml->createElement('url');
        
        $loc = $xml->createElement('loc', htmlspecialchars($url));
        $urlElement->appendChild($loc);
        
        if ($lastmod) {
            $lastmodElement = $xml->createElement('lastmod', $lastmod);
            $urlElement->appendChild($lastmodElement);
        }
        
        $changefreqElement = $xml->createElement('changefreq', $changefreq);
        $urlElement->appendChild($changefreqElement);
        
        $priorityElement = $xml->createElement('priority', $priority);
        $urlElement->appendChild($priorityElement);
        
        $urlset->appendChild($urlElement);
    }
    
    private function addCelebrityReports($xml, $urlset, $baseUrl) {
        try {
            $celebrities = $this->celebrityReportService->getPublishedReports();
            
            foreach ($celebrities as $celebrity) {
                $url = $baseUrl . '/celebrity-reports/' . $celebrity['slug'];
                $lastmod = date('c', strtotime($celebrity['updated_at']));
                $this->addUrl($xml, $urlset, $url, '0.8', 'monthly', $lastmod);
            }
        } catch (Exception $e) {
            $this->logger->error('Error adding celebrity reports to sitemap: ' . $e->getMessage());
        }
    }
    
    private function addArchetypes($xml, $urlset, $baseUrl) {
        try {
            $archetypes = $this->archetypeService->getAllArchetypes();
            
            foreach ($archetypes as $archetype) {
                $url = $baseUrl . '/archetypes/' . $archetype['slug'];
                $lastmod = date('c', strtotime($archetype['updated_at']));
                $this->addUrl($xml, $urlset, $url, '0.6', 'monthly', $lastmod);
            }
        } catch (Exception $e) {
            $this->logger->error('Error adding archetypes to sitemap: ' . $e->getMessage());
        }
    }
    
    private function addPublicShareables($xml, $urlset, $baseUrl) {
        try {
            $shareables = $this->shareableService->getPublicShareables(1000);
            
            foreach ($shareables as $shareable) {
                $url = $baseUrl . '/shareables/view/' . $shareable['share_url'];
                $lastmod = date('c', strtotime($shareable['updated_at']));
                $this->addUrl($xml, $urlset, $url, '0.4', 'weekly', $lastmod);
            }
        } catch (Exception $e) {
            $this->logger->error('Error adding shareables to sitemap: ' . $e->getMessage());
        }
    }
    
    private function addDailyVibes($xml, $urlset, $baseUrl) {
        try {
            $vibes = $this->dailyVibeService->getRecentVibes(30);
            
            foreach ($vibes as $vibe) {
                $url = $baseUrl . '/daily-vibe/' . $vibe['vibe_date'];
                $lastmod = date('c', strtotime($vibe['updated_at']));
                $this->addUrl($xml, $urlset, $url, '0.3', 'daily', $lastmod);
            }
        } catch (Exception $e) {
            $this->logger->error('Error adding daily vibes to sitemap: ' . $e->getMessage());
        }
    }
    
    public function generateSitemapIndex() {
        header('Content-Type: application/xml; charset=utf-8');
        
        $baseUrl = 'https://cosmichub.online';
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Create sitemapindex element
        $sitemapindex = $xml->createElement('sitemapindex');
        $sitemapindex->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->appendChild($sitemapindex);
        
        // Add main sitemap
        $this->addSitemapToIndex($xml, $sitemapindex, $baseUrl . '/sitemap.xml', date('c'));
        
        // Add celebrity reports sitemap (if we have many)
        $this->addSitemapToIndex($xml, $sitemapindex, $baseUrl . '/sitemap-celebrities.xml', date('c'));
        
        echo $xml->saveXML();
    }
    
    private function addSitemapToIndex($xml, $sitemapindex, $url, $lastmod) {
        $sitemapElement = $xml->createElement('sitemap');
        
        $loc = $xml->createElement('loc', htmlspecialchars($url));
        $sitemapElement->appendChild($loc);
        
        $lastmodElement = $xml->createElement('lastmod', $lastmod);
        $sitemapElement->appendChild($lastmodElement);
        
        $sitemapindex->appendChild($sitemapElement);
    }
    
    public function generateCelebritySitemap() {
        header('Content-Type: application/xml; charset=utf-8');
        
        $baseUrl = 'https://cosmichub.online';
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Create urlset element
        $urlset = $xml->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->appendChild($urlset);
        
        // Add all celebrity reports
        $this->addCelebrityReports($xml, $urlset, $baseUrl);
        
        echo $xml->saveXML();
    }
}