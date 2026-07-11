<?php

class DashboardController
{
    public function create(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));
        $projectID = query("SELECT * FROM projects WHERE link='$projectName'");
        $name = "Dashboard-" . $this->getName(7);

        if (mysqli_num_rows($projectID) == 1) {
            $projectID = fetch_assoc($projectID)['projectID'];
            $order = mysqli_num_rows(query("SELECT * FROM project_tools WHERE projectID='$projectID'")) + 1;
            $dashboardName = strtolower($name);
            $insert = query("INSERT INTO control_center_dashboards VALUES (0, '$dashboardName', '[]', '$projectName', NOW(), NOW())");
            if ($insert) {
                $link = strtolower(str_replace(" ", "-", $name));
                $q = query("INSERT INTO project_tools VALUES (0,'bar-chart-outline','$name', '$link',0,'','$projectID')");
                if ($q) {
                    $url = "project/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($projectName)) . "/dashboard/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($name));
                    query("INSERT INTO control_center_pages VALUES (0,'$url', 'true','bar-chart-outline','$name', '', 0)");
                    $response->json("success");
                } else {
                    $response->json("error 2");
                }
            } else {
                $response->json("error 3");
            }
        } else {
            $response->json("error 1");
        }
    }

    public function get(Request $request, Response $response): void
    {
        $dashboardName = escape_string($request->input('dashboard', ''));
        $projectName = escape_string($request->input('project', ''));
        $fetchJsonQuery = "SELECT dashboard_json FROM control_center_dashboards WHERE dashboard_name = '$dashboardName' AND project = '$projectName'";
        $existingJson = fetch_assoc(query($fetchJsonQuery))['dashboard_json'];
        $existingDataArray = json_decode($existingJson, true);
        $json = $existingDataArray;
        $response->json($json);
    }

    public function addChart(Request $request, Response $response): void
    {
        $dashboardName = escape_string($request->input('dashboard', ''));
        $projectName = escape_string($request->input('project', ''));
        $chartData = $request->input('json', '');

        $fetchJsonQuery = "SELECT dashboard_json FROM control_center_dashboards WHERE dashboard_name = '$dashboardName' AND project = '$projectName'";
        $existingJson = fetch_assoc(query($fetchJsonQuery))['dashboard_json'];
        $existingDataArray = json_decode($existingJson, true);

        $newDataArray = json_decode($chartData, true);
        $mergedDataArray = array_merge($existingDataArray, $newDataArray);
        $updatedJson = json_encode($mergedDataArray);

        $updateJsonQuery = "UPDATE control_center_dashboards SET dashboard_json = '$updatedJson' WHERE dashboard_name = '$dashboardName' AND project = '$projectName'";
        $updateResult = query($updateJsonQuery);

        if ($updateResult) {
            $response->json("success");
        } else {
            $response->json("error updating JSON data");
        }
    }

    public function updateCharts(Request $request, Response $response): void
    {
        $dashboardName = escape_string($request->input('dashboard', ''));
        $projectName = escape_string($request->input('project', ''));
        $chartData = $request->input('charts', '');

        $newDataArray = json_decode($chartData, true);

        if (!is_array($newDataArray)) {
            $response->json("error invalid charts data");
            return;
        }

        $updatedJson = escape_string(json_encode($newDataArray));
        $updateJsonQuery = "UPDATE control_center_dashboards SET dashboard_json = '$updatedJson' WHERE dashboard_name = '$dashboardName' AND project = '$projectName'";
        $updateResult = query($updateJsonQuery);

        if ($updateResult) {
            $response->json("success");
        } else {
            $response->json("error updating JSON data");
        }
    }

    public function deleteChart(Request $request, Response $response): void
    {
        $dashboardName = escape_string($request->input('dashboard', ''));
        $projectName = escape_string($request->input('project', ''));
        $chartIndex = (int) $request->input('chart_index', 0);

        $fetchJsonQuery = "SELECT dashboard_json FROM control_center_dashboards WHERE dashboard_name = '$dashboardName' AND project = '$projectName'";
        $existingJson = fetch_assoc(query($fetchJsonQuery))['dashboard_json'];
        $existingDataArray = json_decode($existingJson, true);

        if (array_key_exists($chartIndex, $existingDataArray)) {
            unset($existingDataArray[$chartIndex]);
            $existingDataArray = array_values($existingDataArray);
            $updatedJson = json_encode($existingDataArray);

            $updateJsonQuery = "UPDATE control_center_dashboards SET dashboard_json = '$updatedJson' WHERE dashboard_name = '$dashboardName' AND project = '$projectName'";
            $updateResult = query($updateJsonQuery);

            if ($updateResult) {
                $response->json("success");
            } else {
                $response->json("error updating JSON data");
            }
        } else {
            $response->json("Chart index not found in the JSON data");
        }
    }

    private function getName($n)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $randomString = '';

        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
        return $randomString;
    }
}
