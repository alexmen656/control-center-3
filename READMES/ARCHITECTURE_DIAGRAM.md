# Secure File Authentication - Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         Frontend (Vue.js)                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────┐              ┌──────────────────────┐    │
│  │  FileSystem.vue  │              │ ProjectFileSystem.vue│    │
│  └────────┬─────────┘              └──────────┬───────────┘    │
│           │                                    │                 │
│           └────────────┬───────────────────────┘                │
│                        │                                         │
│                   ┌────▼────┐                                   │
│                   │ Axios   │                                   │
│                   └────┬────┘                                   │
└────────────────────────┼────────────────────────────────────────┘
                         │
                         │ HTTP POST/GET
                         │
┌────────────────────────▼────────────────────────────────────────┐
│                      Backend (PHP)                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────┐         ┌──────────────────────┐    │
│  │signed_url_generator  │         │ secure_file_provider  │    │
│  │       .php           │         │       .php            │    │
│  └──────────┬───────────┘         └──────────▲───────────┘    │
│             │                                  │                 │
│             │ Generates URLs                   │ Validates      │
│             │ with signatures                  │ & serves files │
│             │                                  │                 │
└─────────────┼──────────────────────────────────┼────────────────┘
              │                                  │
              │                                  │
┌─────────────▼──────────────────────────────────▼────────────────┐
│                     File Storage                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  /data/filesystem/                                              │
│  ├── folder1/                                                   │
│  │   ├── image1.jpg                                            │
│  │   └── image2.png                                            │
│  └── folder2/                                                   │
│      └── document.pdf                                           │
│                                                                  │
│  /data/project_filesystems/                                     │
│  ├── project_123/                                               │
│  │   └── files/                                                 │
│  └── project_456/                                               │
│      └── images/                                                │
└─────────────────────────────────────────────────────────────────┘
```

## Request Flow

### 1. Initial Page Load

```
User opens FileSystem
         │
         ▼
FileSystem.vue mounted()
         │
         ▼
fetchFileSystemData()
         │
         ├─────► GET filesystem.php ────► Returns file structure
         │                                          │
         ▼                                          │
loadSignedUrlsForImages() ◄─────────────────────────┘
         │
         ├─────► POST signed_url_generator.php
         │       (bulk request with all image paths)
         │
         ▼
Backend generates signatures
         │
         ▼
Returns signed URLs
         │
         ▼
Store in signedUrls cache
         │
         ▼
Images rendered with signed URLs
```

### 2. Image Display

```
<img :src="getSignedImageUrl(location)">
         │
         ▼
Lookup in signedUrls cache
         │
         ├─────► Found: Return cached URL
         │
         └─────► Not found: Return empty string
                 (fallback to icon display)
```

### 3. Image Preview

```
User double-clicks image
         │
         ▼
previewImage(file) called
         │
         ▼
Open modal (loading state)
         │
         ▼
generateSignedUrl(file.location)
         │
         ├─────► POST signed_url_generator.php
         │       { path: "...", validitySeconds: 3600 }
         │
         ▼
Backend generates signature
         │
         ▼
Return signed URL
         │
         ▼
Set previewImageUrl
         │
         ▼
Modal displays image
```

### 4. File Access

```
Browser requests image
         │
         ├─────► GET secure_file_provider.php?
         │       path=...&expires=...&signature=...
         │
         ▼
Validate signature
         │
         ├──► Invalid? ──► Return 403 Forbidden
         │
         ├──► Expired? ──► Return 403 Forbidden
         │
         ├──► Path traversal? ──► Return 400 Bad Request
         │
         ├──► File not found? ──► Return 404 Not Found
         │
         └──► Valid ──► Serve file with correct headers
```

## Security Layers

```
┌─────────────────────────────────────────────────────────────┐
│ Layer 1: Signature Validation                               │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ • HMAC-SHA256 signature                                 │ │
│ │ • Secret key verification                               │ │
│ │ • Timing-safe comparison                                │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ Layer 2: Time-based Expiration                              │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ • Timestamp in signature                                │ │
│ │ • Default: 1 hour validity                              │ │
│ │ • Automatic expiration                                  │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ Layer 3: Path Validation                                    │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ • Directory traversal protection                        │ │
│ │ • Strip ../ and ..\ sequences                           │ │
│ │ • Validate path format                                  │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ Layer 4: File Type Validation                               │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ • MIME type checking                                    │ │
│ │ • Whitelist of allowed types                            │ │
│ │ • No executable files                                   │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│ Layer 5: File System Validation                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ • File existence check                                  │ │
│ │ • Is-file validation                                    │ │
│ │ • Permission checks                                     │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
                  ┌──────────────┐
                  │  Serve File  │
                  └──────────────┘
```

## Signature Algorithm

```
Input:
  • path:      "folder/image.jpg"
  • expires:   1737324000
  • projectID: "123" (optional)
  • secret:    "cc_secure_file_sign_2026_secret_key"

Step 1: Concatenate data
  data = path + "|" + expires [+ "|" + projectID]
  → "folder/image.jpg|1737324000|123"

Step 2: Generate HMAC
  signature = HMAC-SHA256(data, secret)
  → "a1b2c3d4e5f6..."

Step 3: Build URL
  url = "secure_file_provider.php?" +
        "path=" + path +
        "&expires=" + expires +
        "&signature=" + signature +
        ["&project=" + projectID]
```

## Performance Optimization

```
Traditional Approach (N requests):
  For each image:
    Request signed URL
    Wait for response
    Display image
  Total time: N × request_time

Optimized Approach (1 + 1 requests):
  1. Request file structure
  2. Bulk request ALL signed URLs
  3. Cache URLs in frontend
  4. Display ALL images
  Total time: 2 × request_time

Speed improvement: ~(N/2)× faster for N images
```

## Cache Strategy

```
Frontend Memory Cache:
┌──────────────────────────────────────────┐
│ signedUrls: {                            │
│   "folder/img1.jpg": "https://...?sig=", │
│   "folder/img2.png": "https://...?sig=", │
│   "docs/file.pdf":   "https://...?sig="  │
│ }                                         │
└──────────────────────────────────────────┘
          │
          ├──► Hit: Return cached URL (instant)
          │
          └──► Miss: Generate new URL (async)

Cache Duration: Until page refresh or URL expires
```

## Error Handling

```
signed_url_generator.php errors:
  • 400: Missing parameters
  • 500: Server error

secure_file_provider.php errors:
  • 400: Invalid path / Missing params
  • 403: Invalid or expired signature
  • 404: File not found

Frontend handling:
  • Console error logging
  • Fallback to icon display
  • Error state in preview modal
```
