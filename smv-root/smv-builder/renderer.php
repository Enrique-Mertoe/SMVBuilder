<?php

namespace SMV;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Render
{
    private static Environment $twig;

    private static function initTemplateEngine(): void
    {
        global $CONTENT_DIR;
        $loader = new FilesystemLoader("$CONTENT_DIR/themes/default/layout/");
        self::$twig = new Environment($loader);
    }

    public static function Template($name, ...$options): string
    {
        if (!isset(self::$twig)) {
            self::initTemplateEngine();
        }
        return (new Render())->builder($name,$options);
    }


    public static function Json(array $context = []): string
    {
        header('Content-Type: application/json');
        return json_encode($context);

    }

    private static function tData($cont, $type): array
    {
        return ["smv-content" => $cont, "type" => $type];
    }

    private function process_options(array $options): array
    {
        return $options + [
                "session" => $_SESSION,
                "args" => Request::$args->array(),
                "form" => Request::$form->array()
            ];
    }

    private function builder($name, $options): string
    {
        return self::$twig->render($name, $this->process_options($options));
    }

}