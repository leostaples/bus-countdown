<?php
/* Bootstrapping */
require_once 'phar://'.__DIR__.'/../vendor/silex/silex.phar/autoload.php';

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use BusCountdown\Countdown;

$app = new Silex\Application();

$app['autoloader']->registerNamespaces(array('BusCountdown' => __DIR__,));
$app['countdown'] = function() {
    return new Countdown();
};

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'       => __DIR__.'/views',
    'twig.class_path' => __DIR__.'/../vendor/twig/lib',
    'twig.options' => array('cache' => __DIR__.'/../cache')
));


$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => __DIR__.'/../cache/',
));

/* App definition */

/* Routes */

//Specified stop(s)
$app->get('/stop/{stop}', function ($stop) use ($app){     
   return new Response(
       $app['twig']->render('index.html', 
            array('stops' => $app['countdown']->getStopInfo($stop))
       ),
      200,        
      array(
      	//'Cache-Control' => 's-maxage=600'
        'Cache-Control' => 's-maxage=2592000'
      )  
   );
})
->assert('stop', '[\d,]+');

//Nearest stops
$app->get('/', function () use ($app){     
   return new Response(
       $app['twig']->render('index.html', array()),
      200 
   );
});

//Countdown data
$app->get('/countdown/{stopId}', function ($stopId) use ($app){     
   return new Response(
      $app['countdown']->getCountdownJson($stopId), 
      200,
      array(
        'Content-Type' => 'application/json'
      )  
    );
})
->assert('stopId', '\d+');

//Nearest stops data
$app->get('/nearest/{lat}/{lng}', function ($lat, $lng) use ($app){     
   return new Response(
      $app['countdown']->getNearestStops($lat, $lng), 
      200,
      array(
        'Content-Type' => 'application/json'
      )
    );
});

//Error handling
$app->error(function (\Exception $e) {
    if ($e instanceof NotFoundHttpException) {
        return new Response('The requested page could not be found.', 404);
    }

    $code = ($e instanceof HttpException) ? $e->getStatusCode() : 500;
    return new Response('We are sorry, but something went terribly wrong.', $code);
});

return $app;
?>

