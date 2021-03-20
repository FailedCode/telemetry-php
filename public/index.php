<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {

    $output = '';
    $output .= "<html><head></head><body>";
    $output .= "<ul>";
    foreach (getMessages() as $line) {
        $output .= "<li><pre>{$line}</pre></li>";
    }
    $output .= "</ul>";
    $output .= "</body></html>";

    $response->getBody()->write($output);
    return $response;
});

$app->post('/', function (Request $request, Response $response, $args) {
    $post = (array)$request->getParsedBody();

    $hmac = $post['hmac'] ?? null;
    if (is_null($hmac)) {
        $response->getBody()->write("hmac missing");
        return $response->withStatus(401);
    }

    $apiSecret = "this is just an example you should replace it !!!";
    $message = $post['data'] ?? '';
    $serverHmac = hash_hmac('sha1', $message, $apiSecret);
    if (!hash_equals($serverHmac, $hmac)) {
        $response->getBody()->write("hmac invalid");
        return $response->withStatus(401);
    }
   
    $event = json_decode($message, true);
    logMessage($event['name'] . ' - ' . $message);

    $response->getBody()->write("ok");
    return $response;
});

function getMessages() {
    $file = 'logfile.log';
    if (!file_exists($file)) {
        return ['no log yet!'];
    }
    $lines = explode("\n", file_get_contents($file));
    return array_slice($lines, -20);
}

function logMessage($message = '')
{
    $file = 'logfile.log';
    if (!file_exists($file)) {
        touch($file);
    }

    if (is_array($message)) {
        $message = print_r($message, true);
    }
    $message .= "\n";

    file_put_contents($file, $message, FILE_APPEND);

}

$app->run();
