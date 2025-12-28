# QR Code Generator Module

## Overview
This module provides QR code and barcode generation functionality for projects.

## Features
- Generate QR codes from form entries
- Generate barcodes from form entries
- Print-optimized output
- Configurable form data source

## Configuration
In the config view, you can:
- Select which form to use as the data source
- Select which field to display as the label

## Routes
- `/project/:project/qr-code-generator` - Main generator view
- `/project/:project/qr-code-generator/config` - Configuration view

## Dependencies
- Uses TEC-IT API for barcode and QR code generation
