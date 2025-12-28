/**
 * QR Code Scanner Module
 * 
 * Module for scanning QR codes and barcodes
 */

import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import qrCodeScannerDashboardProvider from './dashboard.provider';

// Register Dashboard Provider
dashboardRegistry.register(qrCodeScannerDashboardProvider);

console.log('📦 QR Code Scanner Module initialized');

export default {
  name: 'qr-code-scanner',
  version: '1.0.0',
  dashboardProvider: qrCodeScannerDashboardProvider
};
