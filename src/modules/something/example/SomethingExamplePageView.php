<?php

/**
 * @modern
 */
class SomethingExamplePageView {

    public function show(): string {
        $str = '
        <!DOCTYPE html>
        <html>
        <head>
        <title>Page Title</title>
        </head>
        <body>
        
        <h1>My First Heading</h1>
        <p>My first paragraph.</p>
        
        </body>
        </html>
        ';

        return $str;
    }

    public static function getUrl(): string {
        return self::class;
    }
}
