/**
 * QR Code Generator Module
 * 
 * Module for generating QR codes and barcodes
 */

import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import qrCodeGeneratorDashboardProvider from './dashboard.provider';

// Register Dashboard Provider
dashboardRegistry.register(qrCodeGeneratorDashboardProvider);

console.log('📦 QR Code Generator Module initialized');

export default {
  name: 'qr-code-generator',
  version: '1.0.0',
  dashboardProvider: qrCodeGeneratorDashboardProvider
};
