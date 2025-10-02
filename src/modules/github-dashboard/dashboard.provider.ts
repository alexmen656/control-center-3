/**
 * Dashboard Provider für GitHub Dashboard Modul
 * 
 * Standardisierte Schnittstelle zum Bereitstellen von Daten für das Dashboard
 */

import axios from 'axios';
import type { ModuleDashboardProvider, DashboardWidget } from '@/types/dashboard.types';

/**
 * GitHub Dashboard Provider
 */
export const githubDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'github-dashboard',
  moduleName: 'GitHub Analytics',
  moduleIcon: 'logo-github',
  
  widgets: [
    // Stat Widgets
    {
      id: 'github-total-repos',
      type: 'stat',
      title: 'Repositories',
      icon: 'folder-outline',
      category: 'stats',
      config: {
        color: 'primary',
        format: 'number'
      },
      getData: async (params?: { userId?: string }) => {
        try {
          const response = await axios.get(`github_repos.php?user_id=${params?.userId || ''}`);
          
          const repos = response.data || [];
          return {
            value: repos.length || 0,
            label: 'Repositories'
          };
        } catch (error) {
          console.error('Error fetching total repos:', error);
          return { value: 0, label: 'Repositories' };
        }
      }
    },
    
    {
      id: 'github-total-commits',
      type: 'stat',
      title: 'Commits',
      icon: 'git-commit-outline',
      category: 'stats',
      config: {
        color: 'success',
        format: 'number'
      },
      getData: async (params?: { project?: string; repo?: string }) => {
        try {
          const token = localStorage.getItem('controlCenter_auth_token');
          const response = await axios.get('github_api.php', {
            params: {
              action: 'getCommits',
              project: params?.project
            },
            headers: {
              'Authorization': token
            }
          });
          
          const commits = response.data || [];
          return {
            value: commits.length || 0,
            label: 'Recent Commits'
          };
        } catch (error) {
          console.error('Error fetching total commits:', error);
          return { value: 0, label: 'Commits' };
        }
      }
    },
    
    {
      id: 'github-open-prs',
      type: 'stat',
      title: 'Offene Pull Requests',
      icon: 'git-pull-request-outline',
      category: 'stats',
      config: {
        color: 'info',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const token = localStorage.getItem('controlCenter_auth_token');
          const response = await axios.get('github_api.php', {
            params: {
              action: 'getPullRequests',
              project: params?.project,
              state: 'open'
            },
            headers: {
              'Authorization': token
            }
          });
          
          const prs = response.data || [];
          return {
            value: prs.length || 0,
            label: 'Offene PRs'
          };
        } catch (error) {
          console.error('Error fetching open PRs:', error);
          return { value: 0, label: 'Offene PRs' };
        }
      }
    },
    
    {
      id: 'github-open-issues',
      type: 'stat',
      title: 'Offene Issues',
      icon: 'alert-circle-outline',
      category: 'stats',
      config: {
        color: 'warning',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const token = localStorage.getItem('controlCenter_auth_token');
          const response = await axios.get('github_api.php', {
            params: {
              action: 'getIssues',
              project: params?.project,
              state: 'open'
            },
            headers: {
              'Authorization': token
            }
          });
          
          const issues = response.data || [];
          return {
            value: issues.length || 0,
            label: 'Offene Issues'
          };
        } catch (error) {
          console.error('Error fetching open issues:', error);
          return { value: 0, label: 'Offene Issues' };
        }
      }
    },
    
    // Chart Widgets
    {
      id: 'github-commits-timeline',
      type: 'chart',
      title: 'Commit Aktivität',
      icon: 'trending-up-outline',
      category: 'charts',
      config: {
        chartType: 'line',
        color: '#2563eb'
      },
      getData: async (params?: { project?: string; period?: number }) => {
        try {
          const token = localStorage.getItem('controlCenter_auth_token');
          const response = await axios.get('github_api.php', {
            params: {
              action: 'getCommits',
              project: params?.project,
              per_page: 100
            },
            headers: {
              'Authorization': token
            }
          });
          
          const commits = response.data || [];
          
          // Group by date
          const dateCounts: { [key: string]: number } = {};
          commits.forEach((commit: any) => {
            const date = commit.commit?.author?.date?.split('T')[0] || 'unknown';
            dateCounts[date] = (dateCounts[date] || 0) + 1;
          });
          
          // Sort by date
          const sortedDates = Object.keys(dateCounts).sort();
          const labels = sortedDates;
          const data = sortedDates.map(d => dateCounts[d]);
          
          return {
            labels,
            datasets: [{
              label: 'Commits',
              data,
              backgroundColor: 'rgba(37, 99, 235, 0.1)',
              borderColor: '#2563eb',
              borderWidth: 2,
              tension: 0.4,
              fill: true
            }]
          };
        } catch (error) {
          console.error('Error fetching commits timeline:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'github-contributors',
      type: 'chart',
      title: 'Top Contributors',
      icon: 'people-outline',
      category: 'charts',
      config: {
        chartType: 'bar'
      },
      getData: async (params?: { project?: string; limit?: number }) => {
        try {
          const token = localStorage.getItem('controlCenter_auth_token');
          const response = await axios.get('github_api.php', {
            params: {
              action: 'getCommits',
              project: params?.project,
              per_page: 100
            },
            headers: {
              'Authorization': token
            }
          });
          
          const commits = response.data || [];
          
          // Count commits per author
          const authorCounts: { [key: string]: number } = {};
          commits.forEach((commit: any) => {
            const author = commit.commit?.author?.name || 'Unknown';
            authorCounts[author] = (authorCounts[author] || 0) + 1;
          });
          
          // Sort and take top N
          const sorted = Object.entries(authorCounts)
            .sort(([, a], [, b]) => b - a)
            .slice(0, params?.limit || 10);
          
          const labels = sorted.map(([author]) => author);
          const data = sorted.map(([, count]) => count);
          
          return {
            labels,
            datasets: [{
              label: 'Commits',
              data,
              backgroundColor: '#2563eb',
              borderColor: '#1d4ed8',
              borderWidth: 1
            }]
          };
        } catch (error) {
          console.error('Error fetching contributors:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'github-repo-languages',
      type: 'chart',
      title: 'Repository Sprachen',
      icon: 'code-outline',
      category: 'charts',
      config: {
        chartType: 'pie'
      },
      getData: async (params?: { userId?: string }) => {
        try {
          const response = await axios.get(`github_repos.php?user_id=${params?.userId || ''}`);
          
          const repos = response.data || [];
          
          // Count by language
          const languageCounts: { [key: string]: number } = {};
          repos.forEach((repo: any) => {
            const language = repo.language || 'Unknown';
            languageCounts[language] = (languageCounts[language] || 0) + 1;
          });
          
          const labels = Object.keys(languageCounts);
          const data = Object.values(languageCounts);
          
          const colors = [
            '#2563eb', '#059669', '#d97706', '#dc2626', '#8b5cf6',
            '#0891b2', '#f59e0b', '#10b981', '#ef4444', '#6366f1'
          ];
          
          return {
            labels,
            datasets: [{
              label: 'Repositories',
              data,
              backgroundColor: colors,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching repo languages:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'github-pr-status',
      type: 'chart',
      title: 'Pull Request Status',
      icon: 'git-merge-outline',
      category: 'charts',
      config: {
        chartType: 'donut'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const token = localStorage.getItem('controlCenter_auth_token');
          const response = await axios.get('github_api.php', {
            params: {
              action: 'getPullRequests',
              project: params?.project,
              state: 'all'
            },
            headers: {
              'Authorization': token
            }
          });
          
          const prs = response.data || [];
          
          // Count by state
          const stateCounts: { [key: string]: number } = {
            'open': 0,
            'closed': 0,
            'merged': 0
          };
          
          prs.forEach((pr: any) => {
            if (pr.merged_at) {
              stateCounts['merged']++;
            } else if (pr.state === 'open') {
              stateCounts['open']++;
            } else {
              stateCounts['closed']++;
            }
          });
          
          const labels = ['Open', 'Closed', 'Merged'];
          const data = [stateCounts['open'], stateCounts['closed'], stateCounts['merged']];
          
          const colors = ['#3b82f6', '#6b7280', '#10b981'];
          
          return {
            labels,
            datasets: [{
              label: 'Pull Requests',
              data,
              backgroundColor: colors,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching PR status:', error);
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
export default githubDashboardProvider;
