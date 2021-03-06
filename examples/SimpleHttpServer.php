<?php
/**
 * User: York <lianyupeng1988@126.com>
 * Date: 2018-01-21 0:37
 */

require '../vendor/autoload.php';

use York8\POA\Application;
use York8\POA\Context;
use York8\POA\Middleware\ProfileMiddleware;
use York8\POA\Middleware\RouterMiddleware;

// 1. create the Application
$app = new Application();

// 2. define the route rules
$router = new RouterMiddleware(function (Context $context) {
    $context->statusCode(404)->send('Not Found');
});
$router->get(
    '/foo/bar$',
    function (Context $context) {
        $context->send('Hello, ' . $context->getRequest()->getUri());
    }
)->get(
    '/foo/exception',
    function () {
        throw new Exception('I throw an exception just for fun, haha!');
    }
);

// 3. use middlewares what you need
$app->use(new ProfileMiddleware())
    // simple error handle middleware
    ->use(function (Context $context) {
        try {
            yield;
        } catch (Exception $e) {
            fwrite(STDERR, 'EXP: ' . $e->getMessage() . "\n");
            $context->statusCode(500)->send('Exp: ' . $e->getMessage());
        }
    })
    ->use(function (Context $context) {
        $uri = $context->getRequest()->getUri();
        if ($uri->getPath() === '/bar') {
            $context->send('you fire.');
            return false;
        }
        return null;
    })
    ->use($router)
    // global error handle middleware
    ->useErrorMiddleware(function (Throwable $throwable, Context $context) {
        // handle global exception
        $msg = $throwable->getMessage();
        fwrite(STDERR, 'Global Exp: ' . $msg . "\n");
        if ($context) {
            $context->statusCode(500)->send('Oh, No! ' . $msg);
        }
    });

// 4. listen and start the server
$app->listen(8088);
