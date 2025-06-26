<?php

require_once './app/Controllers/UserController.php';
require_once './app/Controllers/ProductController.php';
require_once './app/Controllers/SalesController.php';
require_once './config/database.php';

// No crear una nueva instancia de Router aquí
// $router ya está disponible desde index.php

$db = getDBConnection(); // Obtener la conexión a la base de datos
$userController = new UserController($db); // Crear instancia del controlador
$productoController = new ProductController($db);
$salesController = new SalesController($db);

$router->get('/', function () {
    echo "Welcome to the API!";
});

$router->post('/send-code', function () use ($userController) {
    $data = json_decode(file_get_contents('php://input'), true);
    echo $userController->generateCode(['body' => $data]);
});
$router->post('/verify-code', function () use ($userController) {
    $data = json_decode(file_get_contents('php://input'), true);

    echo $userController->verifyCode(['body' => $data]);
});
$router->post('/get-acoount-status', function () use ($userController) {
    $data = json_decode(file_get_contents('php://input'), true);

    echo $userController->getAccountStatus(['body' => $data]);
});