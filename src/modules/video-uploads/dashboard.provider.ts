/**
 * Dashboard Provider für Video Uploads Modul
 * 
 * Standardisierte Schnittstelle zum Bereitstellen von Daten für das Dashboard
 */

import axios from 'axios';
import type { ModuleDashboardProvider, DashboardWidget } from '@/types/dashboard.types';

/**
 * Video Uploads Dashboard Provider
 */
export const videoUploadsDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'video-uploads',
  moduleName: 'Video Uploads Analytics',
  moduleIcon: 'videocam-outline',
  
  widgets: [
    // Stat Widgets
    {
      id: 'video-total-uploads',
      type: 'stat',
      title: 'Gesamte Uploads',
      icon: 'cloud-upload-outline',
      category: 'stats',
      config: {
        color: 'primary',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.get('video_uploads.php', {
            params: {
              action: 'list',
              project: params?.project || ''
            }
          });
          
          const videos = response.data.videos || response.data || [];
          return {
            value: videos.length || 0,
            label: 'Gesamte Uploads'
          };
        } catch (error) {
          console.error('Error fetching total uploads:', error);
          return { value: 0, label: 'Gesamte Uploads' };
        }
      }
    },
    
    {
      id: 'video-published',
      type: 'stat',
      title: 'Veröffentlichte Videos',
      icon: 'checkmark-circle-outline',
      category: 'stats',
      config: {
        color: 'success',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.get('video_uploads.php', {
            params: {
              action: 'list',
              project: params?.project || ''
            }
          });
          
          const videos = response.data.videos || response.data || [];
          const published = videos.filter((v: any) => v.status === 'published');
          
          return {
            value: published.length || 0,
            label: 'Veröffentlichte Videos'
          };
        } catch (error) {
          console.error('Error fetching published videos:', error);
          return { value: 0, label: 'Veröffentlichte Videos' };
        }
      }
    },
    
    {
      id: 'video-total-views',
      type: 'stat',
      title: 'Gesamte Aufrufe',
      icon: 'eye-outline',
      category: 'stats',
      config: {
        color: 'info',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.get('video_uploads.php', {
            params: {
              action: 'list',
              project: params?.project || ''
            }
          });
          
          const videos = response.data.videos || response.data || [];
          const totalViews = videos.reduce((sum: number, v: any) => 
            sum + (parseInt(v.views) || 0), 0
          );
          
          return {
            value: totalViews,
            label: 'Gesamte Aufrufe'
          };
        } catch (error) {
          console.error('Error fetching total views:', error);
          return { value: 0, label: 'Gesamte Aufrufe' };
        }
      }
    },
    
    {
      id: 'video-total-likes',
      type: 'stat',
      title: 'Gesamte Likes',
      icon: 'heart-outline',
      category: 'stats',
      config: {
        color: 'danger',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.get('video_uploads.php', {
            params: {
              action: 'list',
              project: params?.project || ''
            }
          });
          
          const videos = response.data.videos || response.data || [];
          const totalLikes = videos.reduce((sum: number, v: any) => 
            sum + (parseInt(v.likes) || 0), 0
          );
          
          return {
            value: totalLikes,
            label: 'Gesamte Likes'
          };
        } catch (error) {
          console.error('Error fetching total likes:', error);
          return { value: 0, label: 'Gesamte Likes' };
        }
      }
    },
    
    {
      id: 'video-total-comments',
      type: 'stat',
      title: 'Gesamte Kommentare',
      icon: 'chatbubble-outline',
      category: 'stats',
      config: {
        color: 'warning',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.get('video_uploads.php', {
            params: {
              action: 'list',
              project: params?.project || ''
            }
          });
          
          const videos = response.data.videos || response.data || [];
          const totalComments = videos.reduce((sum: number, v: any) => 
            sum + (parseInt(v.comments) || 0), 0
          );
          
          return {
            value: totalComments,
            label: 'Gesamte Kommentare'
          };
        } catch (error) {
          console.error('Error fetching total comments:', error);
          return { value: 0, label: 'Gesamte Kommentare' };
        }
      }
    },
    
    // Chart Widgets
    {
      id: 'video-status-distribution',
      type: 'chart',
      title: 'Video Status',
      icon: 'pie-chart-outline',
      category: 'charts',
      config: {
        chartType: 'pie'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getVideos');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('video_uploads.php', formData);
          
          const videos = response.data.videos || [];
          
          // Count by status
          const statusCounts: { [key: string]: number } = {};
          videos.forEach((v: any) => {
            const status = v.status || 'unknown';
            statusCounts[status] = (statusCounts[status] || 0) + 1;
          });
          
          const labels = Object.keys(statusCounts);
          const data = Object.values(statusCounts);
          
          const colors = {
            'draft': '#6b7280',
            'scheduled': '#3b82f6',
            'published': '#10b981',
            'processing': '#f59e0b',
            'failed': '#ef4444'
          };
          
          const backgroundColor = labels.map(l => colors[l as keyof typeof colors] || '#6b7280');
          
          return {
            labels,
            datasets: [{
              label: 'Videos',
              data,
              backgroundColor,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching status distribution:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'video-platform-distribution',
      type: 'chart',
      title: 'Plattform Verteilung',
      icon: 'apps-outline',
      category: 'charts',
      config: {
        chartType: 'donut'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getVideos');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('video_uploads.php', formData);
          
          const videos = response.data.videos || [];
          
          // Count by platform
          const platformCounts: { [key: string]: number } = {};
          videos.forEach((v: any) => {
            const platform = v.platform || 'unknown';
            platformCounts[platform] = (platformCounts[platform] || 0) + 1;
          });
          
          const labels = Object.keys(platformCounts);
          const data = Object.values(platformCounts);
          
          const colors = ['#FF0000', '#E1306C', '#000000', '#4267B2', '#0077B5']; // YouTube, Instagram, TikTok, Facebook, LinkedIn
          
          return {
            labels,
            datasets: [{
              label: 'Videos',
              data,
              backgroundColor: colors,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching platform distribution:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'video-format-distribution',
      type: 'chart',
      title: 'Format Verteilung',
      icon: 'resize-outline',
      category: 'charts',
      config: {
        chartType: 'donut'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getVideos');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('video_uploads.php', formData);
          
          const videos = response.data.videos || [];
          
          // Count by format
          const formatCounts: { [key: string]: number } = {};
          videos.forEach((v: any) => {
            const format = v.video_format || 'unknown';
            formatCounts[format] = (formatCounts[format] || 0) + 1;
          });
          
          const labels = Object.keys(formatCounts).map(f => 
            f === 'short' ? 'Shorts (9:16)' : 'Videos (16:9)'
          );
          const data = Object.values(formatCounts);
          
          const colors = ['#8b5cf6', '#2563eb'];
          
          return {
            labels,
            datasets: [{
              label: 'Videos',
              data,
              backgroundColor: colors,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching format distribution:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'video-top-performing',
      type: 'chart',
      title: 'Top Videos nach Aufrufen',
      icon: 'trophy-outline',
      category: 'charts',
      config: {
        chartType: 'bar'
      },
      getData: async (params?: { project?: string; limit?: number }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getVideos');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('video_uploads.php', formData);
          
          const videos = response.data.videos || [];
          
          // Sort by views and take top N
          const sorted = videos
            .filter((v: any) => v.status === 'published')
            .sort((a: any, b: any) => (parseInt(b.views) || 0) - (parseInt(a.views) || 0))
            .slice(0, params?.limit || 10);
          
          const labels = sorted.map((v: any) => v.title?.substring(0, 30) || 'Unknown');
          const data = sorted.map((v: any) => parseInt(v.views) || 0);
          
          return {
            labels,
            datasets: [{
              label: 'Aufrufe',
              data,
              backgroundColor: '#2563eb',
              borderColor: '#1d4ed8',
              borderWidth: 1
            }]
          };
        } catch (error) {
          console.error('Error fetching top performing videos:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'video-engagement-metrics',
      type: 'chart',
      title: 'Engagement Metriken',
      icon: 'trending-up-outline',
      category: 'charts',
      config: {
        chartType: 'bar'
      },
      getData: async (params?: { project?: string; limit?: number }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getVideos');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('video_uploads.php', formData);
          
          const videos = response.data.videos || [];
          
          // Sort by engagement and take top N
          const sorted = videos
            .filter((v: any) => v.status === 'published')
            .sort((a: any, b: any) => {
              const engageA = (parseInt(a.likes) || 0) + (parseInt(a.comments) || 0);
              const engageB = (parseInt(b.likes) || 0) + (parseInt(b.comments) || 0);
              return engageB - engageA;
            })
            .slice(0, params?.limit || 10);
          
          const labels = sorted.map((v: any) => v.title?.substring(0, 30) || 'Unknown');
          const likes = sorted.map((v: any) => parseInt(v.likes) || 0);
          const comments = sorted.map((v: any) => parseInt(v.comments) || 0);
          
          return {
            labels,
            datasets: [
              {
                label: 'Likes',
                data: likes,
                backgroundColor: '#ef4444',
                borderColor: '#dc2626',
                borderWidth: 1
              },
              {
                label: 'Kommentare',
                data: comments,
                backgroundColor: '#3b82f6',
                borderColor: '#2563eb',
                borderWidth: 1
              }
            ]
          };
        } catch (error) {
          console.error('Error fetching engagement metrics:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'video-uploads-timeline',
      type: 'chart',
      title: 'Uploads im Zeitverlauf',
      icon: 'calendar-outline',
      category: 'charts',
      config: {
        chartType: 'line'
      },
      getData: async (params?: { project?: string; period?: number }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getVideos');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('video_uploads.php', formData);
          
          const videos = response.data.videos || [];
          
          // Group by date
          const dateCounts: { [key: string]: number } = {};
          videos.forEach((v: any) => {
            const date = v.created_at?.split(' ')[0] || 'unknown';
            dateCounts[date] = (dateCounts[date] || 0) + 1;
          });
          
          // Sort by date and create timeline
          const sortedDates = Object.keys(dateCounts).sort();
          const labels = sortedDates;
          const data = sortedDates.map(d => dateCounts[d]);
          
          return {
            labels,
            datasets: [{
              label: 'Uploads',
              data,
              backgroundColor: 'rgba(37, 99, 235, 0.1)',
              borderColor: '#2563eb',
              borderWidth: 2,
              tension: 0.4,
              fill: true
            }]
          };
        } catch (error) {
          console.error('Error fetching uploads timeline:', error);
          return { labels: [], datasets: [] };
        }
      }
    }
  ],
  
  getWidget(widgetId: string): DashboardWidget | undefined {
    return this.widgets.find(w => w.id === widgetId);
  }
};

// Export für einfachen Zugriff
export default videoUploadsDashboardProvider;
