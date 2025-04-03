import axios from "axios";
import qs from "qs";

export const ToolConfigService = {
  async saveToolConfig(project: string, tool: string, config: Record<string, any>) {
    try {
      const response = await axios.post(
        "tools.php",
        qs.stringify({
          newToolConfig: "newToolConfig",
          config: JSON.stringify(config),
          project,
          tool,
        })
      );
      return response.data;
    } catch (error) {
      console.error("Failed to save tool config:", error);
      throw error;
    }
  },

  async loadToolConfig(project: string, tool: string) {
    try {
      const response = await axios.post(
        "tools.php",
        qs.stringify({
          getToolConfig: "getToolConfig",
          project,
          tool,
        })
      );
      return response.data;
    } catch (error) {
      console.error("Failed to load tool config:", error);
      throw error;
    }
  },
};
