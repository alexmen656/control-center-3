# QR Code Scanner Module

## Overview
This module provides QR code and barcode scanning functionality for projects.

## Features
- Real-time QR code and barcode scanning using device camera
- Audio feedback on successful scan
- Modal display of scanned item details
- Configurable form data source

## Configuration
In the config view, you can:
- Select which form to use as the data source
- Select which field to display as the label when an item is found

## Routes
- `/project/:project/qr-code-scanner` - Main scanner view
- `/project/:project/qr-code-scanner/config` - Configuration view

## Dependencies
- `vue-barcode-reader` - For barcode/QR code scanning
