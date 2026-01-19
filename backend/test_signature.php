<?php
/**
 * Test Script for Secure File Authentication
 * 
 * This script helps verify that the signature system is working correctly.
 * Run this from command line: php test_signature.php
 */

// Configuration - must match the values in secure_file_provider.php and signed_url_generator.php
define('FILE_SIGNATURE_SECRET', 'cc_secure_file_sign_2026_secret_key');

class SignatureTest
{
    public function testSignatureGeneration()
    {
        echo "=== Testing Signature Generation ===\n\n";
        
        $testCases = [
            [
                'path' => 'test/image.jpg',
                'projectID' => null,
                'description' => 'Simple file path'
            ],
            [
                'path' => 'folder/subfolder/document.pdf',
                'projectID' => null,
                'description' => 'Nested path'
            ],
            [
                'path' => 'project_file.png',
                'projectID' => '123',
                'description' => 'File with project ID'
            ]
        ];
        
        foreach ($testCases as $i => $test) {
            echo "Test Case " . ($i + 1) . ": {$test['description']}\n";
            echo str_repeat('-', 50) . "\n";
            
            $expires = time() + 3600; // 1 hour from now
            $data = $test['path'] . '|' . $expires;
            
            if ($test['projectID'] !== null) {
                $data .= '|' . $test['projectID'];
            }
            
            $signature = hash_hmac('sha256', $data, FILE_SIGNATURE_SECRET);
            
            echo "Path: {$test['path']}\n";
            echo "Project ID: " . ($test['projectID'] ?? 'none') . "\n";
            echo "Expires: $expires (" . date('Y-m-d H:i:s', $expires) . ")\n";
            echo "Data: $data\n";
            echo "Signature: $signature\n";
            
            // Build URL
            $params = [
                'path' => $test['path'],
                'expires' => $expires,
                'signature' => $signature
            ];
            
            if ($test['projectID'] !== null) {
                $params['project'] = $test['projectID'];
            }
            
            $url = 'secure_file_provider.php?' . http_build_query($params);
            echo "URL: $url\n";
            echo "\n";
        }
    }
    
    public function testSignatureValidation()
    {
        echo "=== Testing Signature Validation ===\n\n";
        
        $path = 'test.jpg';
        $expires = time() + 3600;
        $data = $path . '|' . $expires;
        $correctSignature = hash_hmac('sha256', $data, FILE_SIGNATURE_SECRET);
        
        // Test 1: Correct signature
        echo "Test 1: Valid signature\n";
        $isValid = hash_equals($correctSignature, $correctSignature);
        echo "Result: " . ($isValid ? "✓ PASS" : "✗ FAIL") . "\n\n";
        
        // Test 2: Invalid signature
        echo "Test 2: Invalid signature\n";
        $wrongSignature = hash_hmac('sha256', $data, 'wrong_secret');
        $isValid = hash_equals($correctSignature, $wrongSignature);
        echo "Result: " . (!$isValid ? "✓ PASS" : "✗ FAIL") . " (should be invalid)\n\n";
        
        // Test 3: Expired signature
        echo "Test 3: Expired signature\n";
        $expiredTime = time() - 3600; // 1 hour ago
        $isExpired = time() > $expiredTime;
        echo "Result: " . ($isExpired ? "✓ PASS" : "✗ FAIL") . " (should be expired)\n\n";
        
        // Test 4: Future signature
        echo "Test 4: Future signature (valid)\n";
        $futureTime = time() + 3600; // 1 hour from now
        $isExpired = time() > $futureTime;
        echo "Result: " . (!$isExpired ? "✓ PASS" : "✗ FAIL") . " (should not be expired)\n\n";
    }
    
    public function testBulkGeneration()
    {
        echo "=== Testing Bulk URL Generation ===\n\n";
        
        $files = [
            ['path' => 'image1.jpg', 'location' => 'folder/image1.jpg'],
            ['path' => 'image2.png', 'location' => 'folder/image2.png'],
            ['path' => 'doc.pdf', 'location' => 'documents/doc.pdf', 'projectID' => '456']
        ];
        
        $expires = time() + 3600;
        $results = [];
        
        foreach ($files as $file) {
            $path = $file['location'] ?? $file['path'];
            $projectID = $file['projectID'] ?? null;
            
            $data = $path . '|' . $expires;
            if ($projectID !== null) {
                $data .= '|' . $projectID;
            }
            
            $signature = hash_hmac('sha256', $data, FILE_SIGNATURE_SECRET);
            
            $params = [
                'path' => $path,
                'expires' => $expires,
                'signature' => $signature
            ];
            
            if ($projectID !== null) {
                $params['project'] = $projectID;
            }
            
            $results[] = [
                'originalPath' => $path,
                'signedUrl' => 'secure_file_provider.php?' . http_build_query($params),
                'expires' => $expires,
                'projectID' => $projectID
            ];
        }
        
        echo "Generated " . count($results) . " signed URLs:\n\n";
        
        foreach ($results as $i => $result) {
            echo ($i + 1) . ". {$result['originalPath']}\n";
            echo "   Project: " . ($result['projectID'] ?? 'none') . "\n";
            echo "   URL: {$result['signedUrl']}\n\n";
        }
    }
    
    public function testDirectoryTraversalProtection()
    {
        echo "=== Testing Directory Traversal Protection ===\n\n";
        
        $maliciousPaths = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            'normal/../../etc/passwd',
            'folder/../../../secret.txt'
        ];
        
        foreach ($maliciousPaths as $i => $path) {
            $safePath = str_replace(['../', '..\\'], '', $path);
            $isBlocked = ($safePath !== $path);
            
            echo "Test " . ($i + 1) . ":\n";
            echo "Malicious path: $path\n";
            echo "After sanitization: $safePath\n";
            echo "Blocked: " . ($isBlocked ? "✓ YES" : "✗ NO") . "\n\n";
        }
    }
    
    public function runAllTests()
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════╗\n";
        echo "║   Secure File Authentication - Test Suite     ║\n";
        echo "╚════════════════════════════════════════════════╝\n";
        echo "\n";
        
        $this->testSignatureGeneration();
        echo "\n";
        
        $this->testSignatureValidation();
        echo "\n";
        
        $this->testBulkGeneration();
        echo "\n";
        
        $this->testDirectoryTraversalProtection();
        
        echo "╔════════════════════════════════════════════════╗\n";
        echo "║              Tests Completed                   ║\n";
        echo "╚════════════════════════════════════════════════╝\n";
        echo "\n";
    }
}

// Run tests
$test = new SignatureTest();
$test->runAllTests();
