<?php

require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register database PDO connection
$dbopts = parse_url(getenv('DATABASE_URL'));
$app->register(new Csanquer\Silex\PdoServiceProvider\Provider\PDOServiceProvider('pdo'),
               array(
                'pdo.server' => array(
                   'driver'   => 'pgsql',
                   'user' => $dbopts["user"],
                   'password' => $dbopts["pass"],
                   'host' => $dbopts["host"],
                   'port' => $dbopts["port"],
                   'dbname' => ltrim($dbopts["path"],'/')
                   )
               )
);

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

// Our web handlers
$app->get('/', function() use($app) {
  $app['monolog']->addDebug('logging output.');
  return $app['twig']->render('index.twig');
});

$app->get('/{number}', function($number) use($app) {
  $app['monolog']->addDebug('Looking up '.$number);

  $st = $app['pdo']->prepare('SELECT number, description, source FROM numbers WHERE active = TRUE AND magnitude >= '.(strlen($number) - 2).' AND magnitude <= '.(strlen($number)));
  $app['monolog']->addDebug('SQL '.'SELECT number, description, source FROM numbers WHERE active = TRUE AND magnitude >= '.(strlen($number) - 2).' AND magnitude <= '.(strlen($number)));
  $st->execute();

  $results = array();
  while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $app['monolog']->addDebug('Row ' . $row['number']);
    $results[] = $row;
  }

  return $app['twig']->render('result.twig', array(
    'number' => $number,
    'results' => $results
  ));
});

// MAGIC!!
$app->run();
