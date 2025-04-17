/**
 * Service Monitor Script
 * 
 * This script runs every 5 minutes to check the status of all services 
 * and sends push notifications if a service has been down for more than 40 minutes.
 * Notifications for the same service are only sent every 30 minutes.
 * Uses the modern Firebase Admin SDK instead of the deprecated server key approach.
 */

import axios from 'axios';
import https from 'https';
import admin from 'firebase-admin';
import { readFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

// Get current file's directory for relative paths
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Initialize Firebase Admin SDK with service account
try {
  const serviceAccount = JSON.parse(
    readFileSync(join(__dirname, 'firebase-service-account.json'), 'utf8')
  );

  admin.initializeApp({
    credential: admin.credential.cert(serviceAccount),
    projectId: 'control-center-2'
  });

  console.log(`[${new Date().toISOString()}] Firebase Admin SDK initialized successfully`);
} catch (error) {
  console.error(`[${new Date().toISOString()}] Failed to initialize Firebase Admin SDK:`, error.message);
  process.exit(1);
}

// Configuration
const API_URL = 'https://alex.polan.sk/control-center/api/service_downtime_alert.php';
const NOTIFICATION_HISTORY_URL = 'https://alex.polan.sk/control-center/api/notification_history.php';
const CHECK_INTERVAL = 5 * 60 * 1000; // 5 minutes in milliseconds
const NOTIFICATION_COOLDOWN = 30 * 60 * 1000; // 30 minutes in milliseconds
const AUTH_USERNAME = 'service_monitor';
const AUTH_PASSWORD = 'Mwgs78HJg12!3sKs';

// Create axios instance with default configuration
const api = axios.create({
  timeout: 10000,
  httpsAgent: new https.Agent({
    rejectUnauthorized: false // Ignore SSL certificate issues
  }),
  headers: {
    'Authorization': 'Basic ' + Buffer.from(`${AUTH_USERNAME}:${AUTH_PASSWORD}`).toString('base64'),
    'Content-Type': 'application/json'
  }
});

/**
 * Check if a notification should be sent for a service
 * based on the last notification time
 * 
 * @param {number} serviceId - The ID of the service
 * @param {string} projectId - The ID of the project
 * @returns {Promise<boolean>} - Should a notification be sent?
 */
async function shouldSendNotification(serviceId, projectId) {
  try {
    const response = await api.get(
      `${NOTIFICATION_HISTORY_URL}?service_id=${serviceId}&project_id=${projectId}`
    );
    
    if (response.data.success && response.data.history) {
      const history = response.data.history;
      const lastNotification = new Date(history.last_notification_time);
      const now = new Date();
      const timeSinceLastNotification = now - lastNotification;
      
      // Only send a new notification if more than NOTIFICATION_COOLDOWN has passed
      return timeSinceLastNotification >= NOTIFICATION_COOLDOWN;
    }
    
    // No notification history found, so this is the first notification
    return true;
  } catch (error) {
    console.error(`[${new Date().toISOString()}] Error checking notification history:`, error.message);
    // If we can't check the history, better to send the notification
    return true;
  }
}

/**
 * Update the notification history after sending a notification
 * 
 * @param {number} serviceId - The ID of the service
 * @param {string} projectId - The ID of the project
 * @param {number} downtimeMinutes - How many minutes the service has been down
 * @returns {Promise<void>}
 */
async function updateNotificationHistory(serviceId, projectId, downtimeMinutes) {
  try {
    await api.post(NOTIFICATION_HISTORY_URL, {
      service_id: serviceId,
      project_id: projectId,
      downtime_minutes: downtimeMinutes
    });
    
    console.log(`[${new Date().toISOString()}] Updated notification history for service ID ${serviceId}`);
  } catch (error) {
    console.error(`[${new Date().toISOString()}] Error updating notification history:`, error.message);
  }
}

/**
 * Send a push notification via Firebase Cloud Messaging using Firebase Admin SDK
 * This is the modern approach that replaces the server key method
 * 
 * @param {string} token - The FCM token to send the message to
 * @param {string} title - The title of the notification
 * @param {string} body - The body text of the notification
 * @returns {Promise<Object>} - A promise that resolves to the Firebase response
 */
async function sendFirebasePushNotification(token, title, body) {
  try {
    // Create the message payload
    const message = {
      notification: {
        title: title,
        body: body
      },
      data: {
        title: title,
        body: body,
        click_action: 'OPEN_SERVICE_STATUS'
      },
      token: token,
      android: {
        priority: 'high',
        notification: {
          clickAction: 'OPEN_SERVICE_STATUS',
          sound: 'default'
        }
      },
      apns: {
        payload: {
          aps: {
            badge: 1,
            sound: 'default',
            category: 'SERVICE_STATUS'
          }
        }
      }
    };

    // Send the message
    const response = await admin.messaging().send(message);
    console.log(`[${new Date().toISOString()}] Firebase notification sent to ${token}: ${response}`);
    return response;
  } catch (error) {
    console.error(`[${new Date().toISOString()}] Error sending Firebase notification:`, error.message);
    return null;
  }
}

/**
 * Main function to check service status and send notifications
 * if a service has been down for more than 40 minutes
 * 
 * Ensures notifications for the same service are only sent every 30 minutes
 */
async function checkServiceStatus() {
  const startTime = new Date();
  console.log(`[${startTime.toISOString()}] Running service status check...`);
  
  try {
    // Call the API endpoint to check for down services
    const response = await api.get(API_URL);
    const data = response.data;
    
    if (data.success) {
      if (data.down_services && data.down_services.length > 0) {
        console.log(`[${new Date().toISOString()}] Found ${data.down_services.length} service(s) that have been down for more than 40 minutes`);
        
        // Check and process each down service
        for (const service of data.down_services) {
          const serviceId = service.service_id;
          const projectId = service.project_id;
          const serviceName = service.service_name;
          const projectName = service.project_name;
          const downtimeMinutes = service.downtime_minutes;
          
          console.log(`[${new Date().toISOString()}] Checking notification history for ${serviceName} (${serviceId}) in project ${projectName}`);
          
          // Check if we should send a notification for this service
          const shouldNotify = await shouldSendNotification(serviceId, projectId);
          
          if (shouldNotify) {
            console.log(`[${new Date().toISOString()}] Sending notification for ${serviceName} - Down for ${downtimeMinutes} minutes`);
            
            // Get all registered push notification tokens
            try {
              const tokensResponse = await api.get('https://alex.polan.sk/control-center/api/push_tokens.php');
              
              if (tokensResponse.data && tokensResponse.data.tokens && tokensResponse.data.tokens.length > 0) {
                const tokens = tokensResponse.data.tokens;
                
                // Format the downtime message
                const downtimeHours = Math.floor(downtimeMinutes / 60);
                const downtimeMins = downtimeMinutes % 60;
                
                let downtimeText = "";
                if (downtimeHours > 0) {
                  downtimeText = `${downtimeHours} hour${downtimeHours > 1 ? 's' : ''}`;
                  if (downtimeMins > 0) {
                    downtimeText += ` and ${downtimeMins} minute${downtimeMins > 1 ? 's' : ''}`;
                  }
                } else {
                  downtimeText = `${downtimeMinutes} minutes`;
                }
                
                // Prepare the notification message
                const title = "Service Down Alert";
                const message = `Service '${serviceName}' in project '${projectName}' has been down for ${downtimeText}`;
                
                // Send Firebase push notification to all tokens
                for (const token of tokens) {
                  await sendFirebasePushNotification(token, title, message);
                }
                
                // Update notification history
                await updateNotificationHistory(serviceId, projectId, downtimeMinutes);
              } else {
                console.log(`[${new Date().toISOString()}] No push notification tokens found`);
              }
            } catch (tokenError) {
              console.error(`[${new Date().toISOString()}] Error fetching tokens or sending notifications:`, tokenError.message);
            }
          } else {
            console.log(`[${new Date().toISOString()}] Skipping notification for ${serviceName} - cooldown period not elapsed yet`);
          }
        }
      } else {
        console.log(`[${new Date().toISOString()}] All services are running or have been down for less than 40 minutes`);
      }
    } else {
      console.error(`[${new Date().toISOString()}] Error: ${data.error || 'Unknown error'}`);
    }
  } catch (error) {
    console.error(`[${new Date().toISOString()}] Error checking service status:`, error.message);
    if (error.response) {
      console.error('Response status:', error.response.status);
      console.error('Response data:', error.response.data);
    }
  }
  
  console.log(`[${new Date().toISOString()}] Service status check completed in ${new Date() - startTime}ms`);
}

// Initial check on startup
checkServiceStatus();

// Schedule regular checks every CHECK_INTERVAL milliseconds (5 minutes)
setInterval(checkServiceStatus, CHECK_INTERVAL);

console.log(`[${new Date().toISOString()}] Service monitor started. Will check services every ${CHECK_INTERVAL/1000} seconds and send notifications with ${NOTIFICATION_COOLDOWN/1000} seconds cooldown.`);