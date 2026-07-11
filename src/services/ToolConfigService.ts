import axios from "axios";

export const ToolConfigService = {
  async saveToolConfig(project: string, tool: string, config: Record<string, any>) {
    try {
      const response = await axios.post(
        "v2/tools/config",
        {
          config: JSON.stringify(config),
          project,
          tool,
        }
      );
      return response.data;
    } catch (error) {
      console.error("Failed to save tool config:", error);
      throw error;
    }
  },

  async loadToolConfig(project: string, tool: string) {
    try {
      const response = await axios.get(
        "v2/tools/config",
        {
          params: {
            project,
            tool,
          },
        }
      );
      return response.data;
    } catch (error) {
      console.error("Failed to load tool config:", error);
      throw error;
    }
  },
};
