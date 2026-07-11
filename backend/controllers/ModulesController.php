<?php

class ModulesController
{
    public function list(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project', ''));

        $modules = query("SELECT * FROM control_center_modules WHERE project='$project'");
        $json = [];
        $i = 0;
        while ($m = fetch_assoc($modules)) {
            $json[$i]['icon'] = $m['icon'];
            $json[$i]['name'] = $m['name'];
            $i++;
        }

        $response->json($json);
    }
}
