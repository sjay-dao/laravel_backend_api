<?php

$domains = [
    'Inventory',
    'Purchasing',
    'Warehouse',
    'Sales',
    'System',
    'Employee',
    "Reference",
    "Payroll",
    "System",
    "Sales"
];

foreach ($domains as $domain) {
    $path = app_path("Domains/{$domain}/routes.php");

    if (file_exists($path)) {
        require $path;
    }
}


