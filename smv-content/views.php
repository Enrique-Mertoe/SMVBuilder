<?php

use SMV\Render;
use SMV\Router;


Router::route("/", ["POST", "GET"], function () {
    echo "123";
    return Render::Template("home.html");
});


Router::route("/about", ["GET","POST"], function () {
    echo "iii";
    return "Hello from about";
});

Router::route("/404", ["GET"], function () {

});

Router::error(404, function () {
    return "Hello from 404";
});

Router::route("/<name>/<name1>", function ($name,$name1) {
    return "Hello from $name $name1";
});
Router::route("/api",function (){
    return Render::Json([
        "name"=>"martin"
    ]);
});