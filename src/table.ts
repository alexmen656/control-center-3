import axios from "axios";
import qs from "qs";

export function getTable(project: string, table: string) {
    console.log(project, table);
  try {
    axios
      .post(
        "/control-center/table.php",
        qs.stringify({
          get_table: "get_table",
          project: project,
          table: table,
        })
      )
      .then((res) => {
        const tableData = res.data.table;
        const result = tableData;//JSON.parse()
        console.log(result);
        return result;
      });
  } catch (error) {
    console.error("Fehler beim Abrufen der Tabelledaten:", error);
  }
}
