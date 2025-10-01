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
                'timeout' => 30
            ]
        ];
        
        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);
        $http_response_header = isset($http_response_header) ? $http_response_header : [];
        $statusCode = null;
        foreach ($http_response_header as $header) {
            if (preg_match('#^HTTP/\d+\.\d+\s+(\d+)#', $header, $matches)) {
                $statusCode = (int)$matches[1];
                break;
            }
        }
        if ($statusCode === 404) {
            // Log 404 but do not throw, just return false so the day is skipped
            error_log('App Store Connect API returned 404 Not Found for URL: ' . $url);
            return false;
        }
        if ($response === false || ($statusCode !== null && $statusCode >= 400)) {
            throw new Exception('App Store Connect API error (HTTP ' . ($statusCode ?? 'unknown') . ') for URL: ' . $url);
        }
        return $response;
    }
    
    private function unzipAndParseTSV($response) {
        $tmpFile = tempnam(sys_get_temp_dir(), 'appstore_report_');
        file_put_contents($tmpFile, $response);
        
        $unzipped = gzopen($tmpFile, 'r');
        $tsv = '';
        
        if ($unzipped) {
            while (!gzeof($unzipped)) {
                $tsv .= gzread($unzipped, 4096);
            }
            gzclose($unzipped);
        }
        
        unlink($tmpFile);
        return $tsv;
    }
    
    private function parseTSVData($tsv) {
        $lines = explode("\n", trim($tsv));
        $downloads = [];
        $header = [];
        
        foreach ($lines as $i => $line) {
            $fields = explode("\t", $line);
            
            if ($i === 0) {
                $header = $fields;
                continue;
            }
            
            if (count($fields) < 5) continue; // skip empty/invalid lines
            
            // Map header fields to indices
            $dateIdx = array_search('Begin Date', $header);
            $countIdx = array_search('Units', $header);
            $versionIdx = array_search('App Version', $header);
            $countryIdx = array_search('Country Code', $header);
            $platformIdx = array_search('Platform', $header);
            $productIdx = array_search('Product Type Identifier', $header);
            $priceIdx = array_search('Customer Price', $header);
            $currencyIdx = array_search('Customer Currency', $header);
            
            if ($dateIdx === false || $countIdx === false) continue;
            
            // Only include app downloads, not in-app purchases
            $productType = $productIdx !== false ? $fields[$productIdx] : '';
            if ($productType && !in_array($productType, ['1F', '1', 'F1'])) continue;
            
            // Get values with proper fallbacks
            $version = $versionIdx !== false && !empty($fields[$versionIdx]) ? $fields[$versionIdx] : '';
            $platform = $platformIdx !== false && !empty($fields[$platformIdx]) ? $this->mapPlatform($fields[$platformIdx]) : '';
            $country = $countryIdx !== false && !empty($fields[$countryIdx]) ? $fields[$countryIdx] : 'XX';
            
            // Skip entries with missing critical data
            if (empty($version) || empty($platform)) continue;
            
            $downloads[] = [
                'date' => $fields[$dateIdx],
                'count' => (int)$fields[$countIdx],
                'version' => $version,
                'country' => $country,
                'platform' => $platform,
                'price' => $priceIdx !== false ? (float)$fields[$priceIdx] : 0,
                'currency' => $currencyIdx !== false ? $fields[$currencyIdx] : 'USD'
            ];
        }
        
        return $downloads;
    }
    
    private function mapPlatform($platform) {
        $platformMap = [
            'iPhone' => 'iOS',
            'iPad' => 'iOS',
            'iPod touch' => 'iOS',
            'Apple TV' => 'tvOS',
            'Apple Watch' => 'watchOS',
            'Mac' => 'macOS',
            'Desktop' => 'macOS'
        ];
        
        // Return mapped platform or original if not found
        return isset($platformMap[$platform]) ? $platformMap[$platform] : $platform;
    }
    
    public function getDownloads($period = 30) {
        $downloads = [];
        $endDate = new DateTime();
        $startDate = new DateTime();
        $startDate->sub(new DateInterval("P{$period}D"));
        
        // Fetch data for each day in the period
        $currentDate = clone $startDate;
        $failedDates = 0;
        $maxFailures = 5; // Stop if too many consecutive failures
        
        while ($currentDate <= $endDate && $failedDates < $maxFailures) {
            $reportDate = $currentDate->format('Y-m-d');
            
            try {
                $url = 'https://api.appstoreconnect.apple.com/v1/salesReports?' . 
                       'filter[reportType]=SALES&' .
                       'filter[reportSubType]=SUMMARY&' .
                       'filter[frequency]=DAILY&' .
                       'filter[vendorNumber]=' . $this->vendor_number . '&' .
                       'filter[reportDate]=' . $reportDate;
                       
                $response = $this->makeRequest($url);
                
                if ($response === false) {
                    // 404 - data not available for this date, skip silently
                    $currentDate->add(new DateInterval('P1D'));
                    $failedDates = 0; // Reset failure counter on 404
                    continue;
                }
                
                $tsv = $this->unzipAndParseTSV($response);
                $dayDownloads = $this->parseTSVData($tsv);
                
                if (!empty($dayDownloads)) {
                    $downloads = array_merge($downloads, $dayDownloads);
                    $failedDates = 0; // Reset failure counter on success
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
    $period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
    $period = max(1, min(90, $period)); // Limit between 1 and 90 days
    
    $api = new AppStoreConnectAPI($private_key, $key_id, $issuer_id, $vendor_number);
    
    // Check if we have cached data
    $cacheFile = "cache/downloads_cache_{$period}.json";
    $cacheTime = 3600; // 1 hour cache
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        // Use cached data
        $result = json_decode(file_get_contents($cacheFile), true);
    } else {
        // Fetch fresh data
        $downloads = $api->getDownloads($period);
        
        // Calculate advanced statistics
        $analytics = new DownloadAnalytics($downloads);
        $stats = $analytics->calculateAdvancedStats();
        
        $result = [
            'downloads' => $downloads,
            'stats' => $stats,
            'period' => $period,
            'last_updated' => date('Y-m-d H:i:s'),
            'total_records' => count($downloads)
        ];
        
        // Cache the result
        if (!is_dir('cache')) {
            mkdir('cache', 0755, true);
        }
        file_put_contents($cacheFile, json_encode($result));
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'downloads' => [],
        'stats' => (new DownloadAnalytics([]))->calculateAdvancedStats()
    ]);
}
?>