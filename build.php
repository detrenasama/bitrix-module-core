<?php

$moduleDir = __DIR__ . "/skel/vendor.modulename";
$installerFileTemplate = __DIR__ . "/skel/installer.php";
$buildFile = __DIR__ . "/bin/module.create.php";

echo "Reading files\n";

$files = [];

$files = getFiles($moduleDir);
$templates = [];
foreach ($files as $file) {
    if (is_file($file))
        $templates[substr($file, strlen($moduleDir))] = file_get_contents($file);
}

echo "Replacing namespaces\n";

foreach ($templates as $path => &$text) {
    $text = str_replace([
        "Vendor\\ModuleName",
        "Vendor\\\\ModuleName",
        "vendor_modulename",
        "V_MN",
        "ModuleName",
        "ModuleDescription",
        "VendorName",
        "VendorSite",
    ], [
        "{{ module.namespace }}",
        "{{ module.namespace | slashed }}",
        "{{ module.class }}",
        "{{ lang.prefix }}",
        "{{ module.name }}",
        "{{ module.description }}",
        "{{ vendor.name }}",
        "{{ vendor.site }}",
    ], $text);
}

echo "Encoding templates";

foreach ($templates as &$template) {
    $template = base64_encode($template);
}

echo "Merging installer with templates\n";

$installerTemplate = file_get_contents($installerFileTemplate);

$installerTemplate = str_replace("{{templates}}", var_export($templates, true), $installerTemplate);

echo "Creating bin file\n";

file_put_contents($buildFile, $installerTemplate);

echo "Done\n";



function getFiles($dir) {
    $files = array_map(function ($e) use ($dir) {
        return "{$dir}/{$e}";
    }, array_filter(scandir($dir), function ($e) {
        return !in_array($e, [".", ".."]);
    }));

    $subfiles = [];
    foreach ($files as $file) {
        if (is_dir($file)) {
            $subfiles = array_merge($subfiles, getFiles($file));
        }
    }
    $files = array_merge($files, $subfiles);

    return $files;
    
}