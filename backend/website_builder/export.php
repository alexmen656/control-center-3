<?php
include '/www/control-center/head.php';
$code = '-' . generateRandomString(12);
$export_dir = "exports/export" . $code;
mkdir(
    $export_dir,
    0777
);
mkdir(
    $export_dir . "/assets",
    0777
);
chmod(
    $export_dir,
    0777
);
chmod(
    $export_dir . "/assets",
    0777
);
$query = query("SELECT * FROM project_components WHERE projectID='nbtUl5ZTd8Wqz5jbj6Q7'");
$html = file_get_contents('/www/website-builder/main.php');
for ($i = 1; $i <= 10; $i++) {
    foreach ($query as $q) {
        //for ($ii = 1; $ii <= 10; $ii++) {
        $htmll = "";
        if ($q["type"] == "menu") {
            $nav1 = "";
            $nav2 = "";
            $par1 = "";
            $par2 = "";
            $logo = "";
            $menu = json_decode(file_get_contents("/www/website-builder/" . $q['file']), true);
            if ($menu && isset($menu["style"])) {
                $style = $menu['style'];
                if (isset($style['nav1'])) {
                    $nav1 = $style['nav1'];
                }
                if (isset($style['nav2'])) {
                    $nav2 = $style['nav2'];
                }
                if (isset($style['par1'])) {
                    $par1 = $style['par1'];
                }
                if (isset($style['par2'])) {
                    $par2 = $style['par2'];
                }
                if (isset($style['logo'])) {
                    $logo = $style['logo'];
                }
            }

            if ($menu && isset($menu["content"])) {
                $htmll .= $nav1 . $logo;

                foreach ($menu["content"] as $m) {
                    $htmll .= $par1 . $m['name'] . $par2;
                }
                $htmll .= $nav2;

                $html = str_replace("{{" . $q['code'] . "}}", $htmll, $html);
            }

        } elseif ($q['type'] == "image") {
            file_put_contents($export_dir . "/assets/" . $q['file'], file_get_contents("/www/website-builder/" . $q['file']));
            chmod(
                $export_dir . "/assets/" . $q['file'],
                0777
            );
        } else {
            $html = str_replace("{{" . $q['code'] . "}}", file_get_contents("/www/website-builder/" . $q['file']), $html);
        }

        $html = str_replace(";" . $q['code'] . ";", "./assets/" . $q['file'], $html);

        //}
    }
}

file_put_contents($export_dir . "/index.html", $html, 0777);
if (chmod($export_dir . "/index.html", 0777)) {
    $zip = new ZipArchive;

    if ($zip->open("./exports/export" . $code . ".zip", ZipArchive::CREATE) === TRUE) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("./" . $export_dir));

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getPathName();
            $relativePath = substr($filePath, strlen("./" . $export_dir) + 1);

            if (strlen($relativePath) <= 1023) {
                $zip->addFile($filePath, $relativePath);
            } else {
                echo "Skipping file due to long entry name: $relativePath\n";
            }
        }

        $zip->close();
        echo "export" . $code . ".zip";
    } else {
        echo "Failed to create zip archive.";
    }
}
?>