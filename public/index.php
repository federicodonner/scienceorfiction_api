<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require '../src/config/db.php';
require '../src/auxiliares/funciones.php';

$app = new \Slim\App;
// require '../src/middleware/authentication.php';

$container = $app->getContainer();
$container['upload_directory'] = __DIR__ . '/images';

// Customer routes
require '../src/routes/rogue.php';
require '../src/routes/episode.php';
require '../src/routes/game.php';
require '../src/routes/response.php';
require '../src/routes/cors.php';


$app->run();
