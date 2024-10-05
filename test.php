<?php

use SMVTemplating\Environment;
use SMVTemplating\Loader\FilesystemLoader;

require_once __DIR__ . "/smv-root/smv_template_engine/init.php";

$engine= new Environment(new FilesystemLoader(__DIR__."/smv-content/themes/default/layout"));

$r = $engine->render("home.html",["name"=>[
    "fname"=>"poll",
    "lname"=>"walker",
    "details" => [
        "age" => 30,
        "address" => [
            "city" => "Nairobi",
            "country" => "Kenya"
        ]
    ]
]]);

//print_r($r);
//
//$p =[];
//$p[] = "if";
//$p[] = "if";
//$p[] = "if";
//$p[] = "if";
//print_r($p);