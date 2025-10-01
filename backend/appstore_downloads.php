<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once 'config.php';
include_once 'head.php';
require 'ECSign.php';

class AppStoreConnectAPI {
    private $private_key;
    private $key_id;
    private $issuer_id;
    private $vendor_number;
    
    public function __construct($private_key, $key_id, $issuer_id, $vendor_number) {
        $this->private_key = $private_key;
        $this->key_id = $key_id;
        $this->issuer_id = $issuer_id;
        $this->vendor_number = $vendor_number;
    }
    
    private function generateJWT() {
        $header = [
            'alg' => 'ES256',
            'kid' => $this->key_id,
            'typ' => 'JWT',
        ];
        
        $payload = [
            'iss' => $this->issuer_id,
            'exp' => time() + 1200, // 20 minutes
            'aud' => 'appstoreconnect-v1'
        ];
        
        return ECSign::sign($payload, $header, $this->private_key);
    }
    
    private function makeRequest($url) {
        $token = $this->generateJWT();
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer $token\r\nAccept: application/a-gzip\r\n",
                'timeout' => 30,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        
        // Parse response headers
        $statusCode = null;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('#^HTTP/\d+\.\d+\s+(\d+)#', $header, $matches)) {
                    $statusCode = (int)$matches[1];
                    break;
                }
            }
        }
        
        // Log detailed response info for debugging
        error_log("App Store API Response - Status: " . ($statusCode ?? 'unknown') . ", Response length: " . strlen($response ?? ''));
        
        if ($statusCode === 404) {
            error_log('App Store Connect API returned 404 Not Found for URL: ' . $url);
            return false;
        }
        
        if ($response === false) {
            error_log('App Store Connect API request failed for URL: ' . $url);
            throw new Exception('App Store Connect API request failed');
        }
        
        if ($statusCode !== null && $statusCode >= 400) {
            error_log('App Store Connect API error (HTTP ' . $statusCode . ') for URL: ' . $url . ' - Response: ' . substr($response, 0, 500));
            throw new Exception('App Store Connect API error (HTTP ' . $statusCode . ')');
        }
        
        // Check if response is empty
        if (empty($response)) {
            error_log('App Store Connect API returned empty response for URL: ' . $url);
            return false;
        }
        
        return $response;
    }
    
    private function unzipAndParseTSV($response) {
        if (empty($response)) {
            error_log('Cannot unzip empty response');
            return '';
        }
        
        $tmpFile = tempnam(sys_get_temp_dir(), 'appstore_report_');
        file_put_contents($tmpFile, $response);
        
        // Try to decompress
        $unzipped = @gzopen($tmpFile, 'r');
        $tsv = '';
        
        if ($unzipped) {
            while (!gzeof($unzipped)) {
                $tsv .= gzread($unzipped, 4096);
            }
            gzclose($unzipped);
        } else {
            // If gzip fails, maybe it's already decompressed?
            error_log('Failed to open as gzip, trying as plain text');
            $tsv = file_get_contents($tmpFile);
            print_r($tsv);
        }
        
        unlink($tmpFile);
        
        error_log('TSV data length: ' . strlen($tsv) . ', first 200 chars: ' . substr($tsv, 0, 200));
        
        return $tsv;
    }
    
    private function parseTSVData($tsv) {
        if (empty($tsv)) {
            error_log('Empty TSV data to parse');
            return [];
        }
        
        $lines = explode("\n", trim($tsv));
        $downloads = [];
        $header = [];
        
        error_log('Parsing TSV with ' . count($lines) . ' lines');
        
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $fields = explode("\t", $line);
            
            if ($i === 0) {
                $header = $fields;
                error_log('TSV Header: ' . implode(', ', $header));
                continue;
            }
            
            if (count($fields) < 5) {
                error_log('Line ' . $i . ' has only ' . count($fields) . ' fields, skipping');
                continue;
            }
            
            // Map header fields to indices
            $dateIdx = array_search('Begin Date', $header);
            $countIdx = array_search('Units', $header);
            $versionIdx = array_search('Version', $header);
            $countryIdx = array_search('Country Code', $header);
            $deviceIdx = array_search('Device', $header);
            $platformIdx = array_search('Supported Platforms', $header);
            $productIdx = array_search('Product Type Identifier', $header);
            $priceIdx = array_search('Customer Price', $header);
            $currencyIdx = array_search('Customer Currency', $header);
            $skuIdx = array_search('SKU', $header);
            $titleIdx = array_search('Title', $header);
            
            if ($dateIdx === false || $countIdx === false) {
                error_log('Required columns not found in header. Date idx: ' . var_export($dateIdx, true) . ', Count idx: ' . var_export($countIdx, true));
                continue;
            }
            
            // Filter product types - exclude in-app purchases and subscriptions
            $productType = $productIdx !== false && isset($fields[$productIdx]) ? trim($fields[$productIdx]) : '';
            
            // Exclude: IA* (in-app), IAY (subscription), FI1, etc.
            if (!empty($productType) && (preg_match('/^IA/', $productType) || $productType === 'FI1')) {
                continue;
            }
            
            // Get values with proper fallbacks
            $version = $versionIdx !== false && isset($fields[$versionIdx]) && !empty(trim($fields[$versionIdx])) ? trim($fields[$versionIdx]) : 'Unknown';
            
            // Get device (iPhone, iPad, Desktop)
            $device = $deviceIdx !== false && isset($fields[$deviceIdx]) && !empty(trim($fields[$deviceIdx])) ? trim($fields[$deviceIdx]) : '';
            
            // Get platform (iOS, macOS, etc.) 
            $platformRaw = $platformIdx !== false && isset($fields[$platformIdx]) && !empty(trim($fields[$platformIdx])) ? trim($fields[$platformIdx]) : '';
            
            // Map platform based on device and platform column
            $platform = $this->mapPlatform($device, $platformRaw);
            
            $country = $countryIdx !== false && isset($fields[$countryIdx]) && !empty(trim($fields[$countryIdx])) ? trim($fields[$countryIdx]) : 'XX';
            
            // Get SKU and Title for app identification
            $sku = $skuIdx !== false && isset($fields[$skuIdx]) ? trim($fields[$skuIdx]) : '';
            $title = $titleIdx !== false && isset($fields[$titleIdx]) ? trim($fields[$titleIdx]) : '';
            
            $downloads[] = [
                'date' => isset($fields[$dateIdx]) ? $fields[$dateIdx] : '',
                'count' => isset($fields[$countIdx]) ? (int)$fields[$countIdx] : 0,
                'version' => $version,
                'country' => $country,
                'platform' => $platform,
                'price' => $priceIdx !== false && isset($fields[$priceIdx]) ? (float)$fields[$priceIdx] : 0,
                'currency' => $currencyIdx !== false && isset($fields[$currencyIdx]) ? $fields[$currencyIdx] : 'USD',
                'sku' => $sku,
                'title' => $title,
                'device' => $device
            ];
        }
        
        error_log('Parsed ' . count($downloads) . ' download entries from TSV');
        
        return $downloads;
    }
    
    private function mapPlatform($device, $platformRaw) {
        // Priority: Use Supported Platforms column if available
        if (!empty($platformRaw)) {
            $platformRaw = trim($platformRaw);
            if ($platformRaw === 'iOS') return 'iOS';
            if ($platformRaw === 'macOS') return 'macOS';
            if ($platformRaw === 'tvOS') return 'tvOS';
            if ($platformRaw === 'watchOS') return 'watchOS';
        }
        
        // Fallback: Map based on device
        if (!empty($device)) {
            $device = trim($device);
            $deviceMap = [
                'iPhone' => 'iOS',
                'iPad' => 'iOS',
                'iPod touch' => 'iOS',
                'Apple TV' => 'tvOS',
                'Apple Watch' => 'watchOS',
                'Mac' => 'macOS',
                'Desktop' => 'macOS'
            ];
            
            if (isset($deviceMap[$device])) {
                return $deviceMap[$device];
            }
        }
        
        // Final fallback
        return 'Unknown';
    }
    
    public function getDownloads($period = 30) {
        $downloads = [];
        $endDate = new DateTime();
        $startDate = new DateTime();
        $startDate->sub(new DateInterval("P{$period}D"));
        
        error_log('Fetching downloads from ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'));
        
        // Fetch data for each day in the period
        $currentDate = clone $startDate;
        $failedDates = 0;
        $maxFailures = 10; // Increase max failures tolerance
        $totalAttempts = 0;
        $successfulDays = 0;
        
        while ($currentDate <= $endDate && $failedDates < $maxFailures) {
            $reportDate = $currentDate->format('Y-m-d');
            $totalAttempts++;
            
            try {
                $url = 'https://api.appstoreconnect.apple.com/v1/salesReports?' . 
                       'filter[reportType]=SALES&' .
                       'filter[reportSubType]=SUMMARY&' .
                       'filter[frequency]=DAILY&' .
                       'filter[vendorNumber]=' . $this->vendor_number . '&' .
                       'filter[reportDate]=' . $reportDate;
                
                error_log("Attempting to fetch data for $reportDate");
                       
                $response = $this->makeRequest($url);
                
                if ($response === false) {
                    // 404 - data not available for this date, skip silently
                    error_log("No data available for $reportDate (404 or empty)");
                    $currentDate->add(new DateInterval('P1D'));
                    $failedDates = 0; // Reset failure counter on 404
                    continue;
                }
                
                $tsv = $this->unzipAndParseTSV($response);
                
                if (empty($tsv)) {
                    error_log("Empty TSV for $reportDate");
                    $currentDate->add(new DateInterval('P1D'));
                    continue;
                }
                
                $dayDownloads = $this->parseTSVData($tsv);
                
                if (!empty($dayDownloads)) {
                    error_log("Successfully parsed " . count($dayDownloads) . " entries for $reportDate");
                    $downloads = array_merge($downloads, $dayDownloads);
                    $failedDates = 0; // Reset failure counter on success
                    $successfulDays++;
                } else {
                    error_log("No downloads parsed for $reportDate despite having TSV data");
                }
                
                // Only add delay for shorter periods to speed up loading
                if ($period < 90) {
                    usleep(50000); // 0.05 seconds
                }
            } catch (Exception $e) {
                error_log("Failed to fetch data for $reportDate: " . $e->getMessage());
                $failedDates++;
                // Continue with next date even on error
            }
            
            $currentDate->add(new DateInterval('P1D'));
        }
        
        error_log("Download fetch complete: $successfulDays successful days out of $totalAttempts attempts, total entries: " . count($downloads));
        
        return $downloads;
    }
    
    public function getFinancialReports($period = 30) {
        // This would fetch financial reports if needed
        // Similar implementation to getDownloads but with different filters
        return [];
    }
}

// Enhanced statistics calculation
class DownloadAnalytics {
    private $downloads;
    
    public function __construct($downloads) {
        $this->downloads = $downloads;
    }
    
    public function calculateAdvancedStats() {
        if (empty($this->downloads)) {
            return $this->getEmptyStats();
        }
        
        $total = array_sum(array_column($this->downloads, 'count'));
        $dailyAverage = $this->calculateDailyAverage();
        $countryStats = $this->getCountryStats();
        $versionStats = $this->getVersionStats();
        $platformStats = $this->getPlatformStats();
        $trends = $this->calculateTrends();
        $weekdayStats = $this->getWeekdayStats();
        $growthRate = $this->calculateGrowthRate();
        
        return [
            'total' => $total,
            'dailyAverage' => round($dailyAverage),
            'topCountry' => $countryStats['top']['country'] ?? 'Unknown',
            'topCountryDownloads' => $countryStats['top']['downloads'] ?? 0,
            'activeVersions' => count($versionStats),
            'latestVersion' => $this->getLatestVersion(),
            'totalTrend' => $trends['total'],
            'avgTrend' => $trends['average'],
            'weekdayDistribution' => $weekdayStats,
            'countryDistribution' => $countryStats['all'],
            'versionDistribution' => $versionStats,
            'platformDistribution' => $platformStats,
            'growthRate' => $growthRate,
            'peakDay' => $this->getPeakDay(),
            'revenue' => $this->calculateRevenue()
        ];
    }
    
    private function calculateDailyAverage() {
        $dateGroups = [];
        foreach ($this->downloads as $download) {
            $date = $download['date'];
            if (!isset($dateGroups[$date])) {
                $dateGroups[$date] = 0;
            }
            $dateGroups[$date] += $download['count'];
        }
        
        return count($dateGroups) > 0 ? array_sum($dateGroups) / count($dateGroups) : 0;
    }
    
    private function getCountryStats() {
        $countryData = [];
        foreach ($this->downloads as $download) {
            $country = $download['country'];
            if (!isset($countryData[$country])) {
                $countryData[$country] = 0;
            }
            $countryData[$country] += $download['count'];
        }
        
        arsort($countryData);
        
        $top = null;
        if (!empty($countryData)) {
            $topCountry = key($countryData);
            $top = [
                'country' => $topCountry,
                'downloads' => $countryData[$topCountry]
            ];
        }
        
        return [
            'top' => $top,
            'all' => $countryData
        ];
    }
    
    private function getVersionStats() {
        $versionData = [];
        foreach ($this->downloads as $download) {
            $version = $download['version'];
            if (!isset($versionData[$version])) {
                $versionData[$version] = 0;
            }
            $versionData[$version] += $download['count'];
        }
        
        return $versionData;
    }
    
    private function getPlatformStats() {
        $platformData = [];
        foreach ($this->downloads as $download) {
            $platform = $download['platform'];
            if (!isset($platformData[$platform])) {
                $platformData[$platform] = 0;
            }
            $platformData[$platform] += $download['count'];
        }
        
        return $platformData;
    }
    
    private function getWeekdayStats() {
        $weekdayData = array_fill(0, 7, 0);
        
        foreach ($this->downloads as $download) {
            $date = new DateTime($download['date']);
            $weekday = (int)$date->format('N') - 1; // Monday = 0
            $weekdayData[$weekday] += $download['count'];
        }
        
        return $weekdayData;
    }
    
    private function calculateTrends() {
        // Calculate trends by comparing first half vs second half of period
        $dates = array_unique(array_column($this->downloads, 'date'));
        sort($dates);
        
        if (count($dates) < 2) {
            return ['total' => 0, 'average' => 0];
        }
        
        $midpoint = floor(count($dates) / 2);
        $firstHalf = array_slice($dates, 0, $midpoint);
        $secondHalf = array_slice($dates, $midpoint);
        
        $firstTotal = 0;
        $secondTotal = 0;
        
        foreach ($this->downloads as $download) {
            if (in_array($download['date'], $firstHalf)) {
                $firstTotal += $download['count'];
            } elseif (in_array($download['date'], $secondHalf)) {
                $secondTotal += $download['count'];
            }
        }
        
        $firstAvg = count($firstHalf) > 0 ? $firstTotal / count($firstHalf) : 0;
        $secondAvg = count($secondHalf) > 0 ? $secondTotal / count($secondHalf) : 0;
        
        $totalTrend = $firstTotal > 0 ? round((($secondTotal - $firstTotal) / $firstTotal) * 100) : 0;
        $avgTrend = $firstAvg > 0 ? round((($secondAvg - $firstAvg) / $firstAvg) * 100) : 0;
        
        return [
            'total' => $totalTrend,
            'average' => $avgTrend
        ];
    }
    
    private function calculateGrowthRate() {
        $dates = array_unique(array_column($this->downloads, 'date'));
        sort($dates);
        
        if (count($dates) < 7) return 0;
        
        // Compare last 7 days with previous 7 days
        $recent = array_slice($dates, -7);
        $previous = array_slice($dates, -14, 7);
        
        $recentTotal = 0;
        $previousTotal = 0;
        
        foreach ($this->downloads as $download) {
            if (in_array($download['date'], $recent)) {
                $recentTotal += $download['count'];
            } elseif (in_array($download['date'], $previous)) {
                $previousTotal += $download['count'];
            }
        }
        
        return $previousTotal > 0 ? round((($recentTotal - $previousTotal) / $previousTotal) * 100, 1) : 0;
    }
    
    private function getPeakDay() {
        $dailyTotals = [];
        foreach ($this->downloads as $download) {
            $date = $download['date'];
            if (!isset($dailyTotals[$date])) {
                $dailyTotals[$date] = 0;
            }
            $dailyTotals[$date] += $download['count'];
        }
        
        if (empty($dailyTotals)) return null;
        
        $maxDownloads = max($dailyTotals);
        $peakDate = array_search($maxDownloads, $dailyTotals);
        
        return [
            'date' => $peakDate,
            'downloads' => $maxDownloads
        ];
    }
    
    private function calculateRevenue() {
        $revenue = 0;
        foreach ($this->downloads as $download) {
            $revenue += $download['price'] * $download['count'];
        }
        
        return round($revenue, 2);
    }
    
    private function getLatestVersion() {
        $versions = array_unique(array_column($this->downloads, 'version'));
        if (empty($versions)) return 'Unknown';
        
        // Sort versions (simple string sort, could be improved for semantic versioning)
        usort($versions, 'version_compare');
        return end($versions);
    }
    
    private function getEmptyStats() {
        return [
            'total' => 0,
            'dailyAverage' => 0,
            'topCountry' => '',
            'topCountryDownloads' => 0,
            'activeVersions' => 0,
            'latestVersion' => '',
            'totalTrend' => 0,
            'avgTrend' => 0,
            'weekdayDistribution' => array_fill(0, 7, 0),
            'countryDistribution' => [],
            'versionDistribution' => [],
            'platformDistribution' => [],
            'growthRate' => 0,
            'peakDay' => null,
            'revenue' => 0
        ];
    }
}

// Main execution
try {
    // Get available apps - OPTIMIZED: Use cached data from a shorter period
    if (isset($_GET['get_apps']) && $_GET['get_apps'] === 'true') {
        // Use cache for apps list
        $appsCacheFile = "cache/apps_list_cache.json";
        $appsCacheTime = 3600 * 6; // 6 hours cache for apps list
        
        if (file_exists($appsCacheFile) && (time() - filemtime($appsCacheFile)) < $appsCacheTime) {
            // Return cached apps list
            $cachedApps = json_decode(file_get_contents($appsCacheFile), true);
            $cachedApps['from_cache'] = true;
            $cachedApps['cached_at'] = date('Y-m-d H:i:s', filemtime($appsCacheFile));
            echo json_encode($cachedApps);
            return;
        }
        
        // Fetch from API with shorter period for faster loading
        $period = 7; // Only 7 days needed to get app list
        
        $api = new AppStoreConnectAPI($private_key, $key_id, $issuer_id, $vendor_number);
        $downloads = $api->getDownloads($period);
        
        // Extract unique apps
        $apps = [];
        $appMap = [];
        
        foreach ($downloads as $download) {
            $sku = $download['sku'];
            $title = $download['title'];
            
            if (!empty($sku) && !isset($appMap[$sku])) {
                $appMap[$sku] = true;
                $apps[] = [
                    'sku' => $sku,
                    'title' => $title
                ];
            }
        }
        
        // Sort by title
        usort($apps, function($a, $b) {
            return strcmp($a['title'], $b['title']);
        });
        
        $result = [
            'apps' => $apps,
            'count' => count($apps),
            'from_cache' => false
        ];
        
        // Cache the apps list
        if (!is_dir('cache')) {
            mkdir('cache', 0755, true);
        }
        file_put_contents($appsCacheFile, json_encode($result));
        
        echo json_encode($result);
        return;
    }
    
    $period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
    $period = max(1, min(90, $period)); // Limit between 1 and 90 days
    
    // Get app filter
    $filterApp = isset($_GET['app']) ? trim($_GET['app']) : '';
    
    // Allow cache bypass
    $skipCache = isset($_GET['nocache']) || isset($_GET['refresh']);
    
    error_log("Starting App Store downloads fetch - Period: $period days, Skip cache: " . ($skipCache ? 'yes' : 'no') . ", App filter: " . ($filterApp ?: 'none'));
    
    $api = new AppStoreConnectAPI($private_key, $key_id, $issuer_id, $vendor_number);
    
    // Cache key includes app filter
    $cacheKey = $filterApp ? "{$period}_{$filterApp}" : $period;
    $cacheFile = "cache/downloads_cache_{$cacheKey}.json";
    $cacheTime = 3600; // 1 hour cache
    
    if (!$skipCache && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        // Use cached data
        error_log("Using cached data from: " . date('Y-m-d H:i:s', filemtime($cacheFile)));
        $result = json_decode(file_get_contents($cacheFile), true);
        $result['from_cache'] = true;
    } else {
        // Fetch fresh data
        error_log("Fetching fresh data from App Store Connect API");
        $downloads = $api->getDownloads($period);
        
        error_log("Total downloads fetched before filtering: " . count($downloads));
        
        // Filter by app if specified
        if (!empty($filterApp)) {
            $downloads = array_filter($downloads, function($download) use ($filterApp) {
                return $download['sku'] === $filterApp;
            });
            $downloads = array_values($downloads); // Re-index array
            error_log("Downloads after app filter: " . count($downloads));
        }
        
        // Calculate advanced statistics
        $analytics = new DownloadAnalytics($downloads);
        $stats = $analytics->calculateAdvancedStats();
        
        $result = [
            'downloads' => $downloads,
            'stats' => $stats,
            'period' => $period,
            'filter_app' => $filterApp,
            'last_updated' => date('Y-m-d H:i:s'),
            'total_records' => count($downloads),
            'from_cache' => false,
            'debug_info' => [
                'vendor_number' => $vendor_number,
                'key_id' => substr($key_id, 0, 4) . '...',
                'issuer_id' => substr($issuer_id, 0, 8) . '...'
            ]
        ];
        
        // Cache the result
        if (!is_dir('cache')) {
            mkdir('cache', 0755, true);
        }
        
        $cacheWritten = file_put_contents($cacheFile, json_encode($result));
        error_log("Cache written: " . ($cacheWritten ? 'success' : 'failed'));
    }
    
    error_log("Returning response with " . count($result['downloads']) . " downloads");
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Exception in main execution: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'error_trace' => $e->getTraceAsString(),
        'downloads' => [],
        'stats' => (new DownloadAnalytics([]))->calculateAdvancedStats(),
        'debug_mode' => true
    ]);
}
?>