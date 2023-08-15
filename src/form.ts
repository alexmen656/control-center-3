import axios from "axios";
import qs from "qs";

export function getForm(project: string, form: string) {
    console.log(project, form);
  try {
    axios
      .post(
        "/control-center/form.php",
        qs.stringify({
          get_form: "get_form",
          project: project,
          form: form,
        })
      )
      .then((res) => {
        const formData = res.data.form;
        const form = formData;//JSON.parse()
        console.log(form);
        return form;
      });
  } catch (error) {
    console.error("Fehler beim Abrufen der Formulardaten:", error);
  }
}
