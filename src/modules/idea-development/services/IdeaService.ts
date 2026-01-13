import axios from 'axios';

export interface Milestone {
  id: string; // uuid
  title: string;
  isCompleted: boolean;
  dueDate?: string;
}

export interface Asset {
  id: string; // uuid
  type: 'image' | 'link' | 'file';
  name: string;
  url: string;
}

export interface Idea {
  id?: number;
  title: string;
  description?: string;
  status: 'draft' | 'in_progress' | 'completed' | 'archived';
  milestones: Milestone[];
  notes: string; // Markdown
  assets: Asset[];
  created_at?: string;
  updated_at?: string;
}

const API_ENDPOINT = 'idea_development.php';

export const ideaService = {
  async getIdeas(project: string): Promise<Idea[]> {
    try {
      const response = await axios.get(`${API_ENDPOINT}?project=${project}`);
      if (response.data && Array.isArray(response.data.data)) {
        return response.data.data;
      }
      return [];
    } catch (error) {
      console.error('Error fetching ideas:', error);
      return [];
    }
  },

  async getIdea(project: string, id: number): Promise<Idea | null> {
    try {
      const response = await axios.get(`${API_ENDPOINT}?project=${project}&id=${id}`);
      return response.data.data || null;
    } catch (error) {
      console.error('Error fetching idea:', error);
      return null;
    }
  },

  async createIdea(project: string, idea: Idea): Promise<Idea | null> {
    try {
      const response = await axios.post(`${API_ENDPOINT}?project=${project}&action=create`, idea);
      return response.data.data || null;
    } catch (error) {
      console.error('Error creating idea:', error);
      throw error;
    }
  },

  async updateIdea(project: string, idea: Idea): Promise<Idea | null> {
    try {
      const response = await axios.post(`${API_ENDPOINT}?project=${project}&action=update`, idea);
      return response.data.data || null;
    } catch (error) {
      console.error('Error updating idea:', error);
      throw error;
    }
  },

  async deleteIdea(project: string, id: number): Promise<void> {
    await axios.post(`${API_ENDPOINT}?project=${project}&action=delete`, { id });
  }
};
