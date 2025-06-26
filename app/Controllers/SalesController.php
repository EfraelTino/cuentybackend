<?php


require_once './app/Models/SalesModel.php';
require_once './config/Utils.php';
require_once './app/Models/User.php';
class   SalesController
{
    private $salesModel;
    private $utils;
    private $userModel;
    public function __construct($db)
    {
        $this->salesModel = new  SalesModel($db);
        $this->utils = new Utils();
        $this->userModel = new User($db);
    }

    // Registrar venta
    public function procesarVenta($request)
    {

        $data = $request['body'];

        // Validar datos mínimos requeridos
        //primero buscamos al usuario
        if (!ctype_digit($data['userdata']['dni']) || strlen($data['userdata']['dni']) >= 11) {
            return $this->utils->jsonResponse(200, [
                'message' => 'El DNI debe ser numérico y tener menos de 11 dígitos',
                'success' => false
            ]);
        }
        $findCustomerByDocument = $this->userModel->findUserByDocument($data['userdata']['dni']);
        if (!$findCustomerByDocument) {
            $createCustomer = $this->userModel->createCustomer($data['userdata']);
            if ($createCustomer) {
                $data['client_id'] = $createCustomer;
                $findCustomerByDocument = $createCustomer;
                $newSale = $this->registrarSale($data);
                if (!$newSale['success']) {
                    return $this->utils->jsonResponse(200, [
                        'message' => $newSale['message'],
                        'success' => false
                    ]);
                }
                return $this->utils->jsonResponse(200, [
                    'message' => $newSale['message'],
                    'success' => true
                ]);
            }
        }
        $data['client_id'] = $findCustomerByDocument;
        $venta = $this->registrarSale($data);
        if (!$venta['success']) {
            return $this->utils->jsonResponse(200, [
                'message' => $venta['message'],
                'success' =>  false
            ]);
        }
        return $this->utils->jsonResponse(200, [
            'message' => $venta,
            'success' => true
        ]);
    }
    //registrar venta
    public function registrarSale($data)
    {
        try {
            if (
                empty($data['cart']) || !is_array($data['cart']) ||
                empty($data['total']) || empty($data['paymentMethodSelected']) || empty($data['client_id'])
            ) {
                return [
                    'message' => 'Faltan datos obligatorios para registrar la venta' . json_encode($data),
                    'success' => false
                ];
            }

            // Verificar que hay productos
            if (count($data['cart']) === 0) {
                return [
                    'message' => 'No hay productos en la venta',
                    'success' => false
                ];
            }
            $newSale = $this->salesModel->createSale($data);
            if (!$newSale) {
                return [
                    'message' => 'Error al registrar la venta' . $newSale,
                    'success' => false
                ];
            }
            $newSaleDetails = $this->salesModel->createSaleDetails($newSale, $data);
            if (!$newSaleDetails) {
                return [
                    'message' => 'Error al registrar los detalles de la venta',
                    'success' => false
                ];
            }
            $discountQuantityproduct = $this->salesModel->discountQuantityproduct($data);
            if (!$discountQuantityproduct) {
                return [
                    'message' => 'Error al registrar el descuento de stock',
                    'success' => false
                ];
            }
            $insertCustomer = $this->salesModel->insertDataCustomer($newSale, $data['client_id']);
            if (!$insertCustomer) {
                return [
                    'message' => 'Error al registrar el cliente',
                    'success' => false
                ];
            }

            //REGISTRAR MOVIMIENTO DE CAJA
            $sendData['monto'] = $data['total'];
            $sendData['tipo'] = 'Ingreso';
            $sendData['usuario_id'] = $data['userid'];
            $sendData['descripcion'] = 'Venta de productos ';
            $sendData['venta_id'] = $newSale;
            $registerCashMovement = $this->registrarMovimientoCaja($sendData);
            if (!$registerCashMovement['success']) {
                return [
                    'message' => $registerCashMovement['message'],
                    'success' => $registerCashMovement['success']
                ];
            }
            return [
                'message' => $registerCashMovement['message'],
                'success' => $registerCashMovement['success']
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'Error al registrar la venta: ' . $th->getMessage(),
                'success' => false
            ];
        }
    }
    public function registrarEgreso($request)
    {
        $data = $request['body'];
        $sendData['monto'] = $data['monto'];
        $sendData['tipo'] = $data['tipo'];
        $sendData['usuario_id'] = $data['usuario_id'];
        $sendData['descripcion'] = $data['descripcion'];
        $sendData['venta_id'] = null;
        $registerDischarge = $this->registrarMovimientoCaja($sendData);
        if (!$registerDischarge['success']) {
            return $this->utils->jsonResponse(200, [
                'message' => $registerDischarge['message'],
                'success' => $registerDischarge['success']
            ]);
        }
        return $this->utils->jsonResponse(200, [
            'message' => $registerDischarge['message'],
            'success' => $registerDischarge['success']
        ]);
    }
    // Obtener detalle de venta
    public function obtenerDetalleVenta($request)
    {
        $data = $request['body'];
        $ventaId = $data['id'];

        if (!is_numeric($ventaId)) {
            return $this->utils->jsonResponse(200, [
                'message' => 'ID de venta inválido',
                'success' => false
            ]);
        }

        $detalle = $this->salesModel->obtenerDetalleVenta($ventaId);

        if (!$detalle) {
            return $this->utils->jsonResponse(200, [
                'message' => 'Venta no encontrada',
                'success' => false
            ]);
        }

        return $this->utils->jsonResponse(200, [
            'data' => $detalle,
            'success' => true
        ]);
    }
    //amount day
    public function amountDay($request)
    {
        $data = $request['body'];
        try {
            $date_get = $data['fechaObtener'];
            $getAmounts = $this->salesModel->getAmounts($date_get);
            if ($getAmounts) {
                return $this->utils->jsonResponse(200, [
                    'message' => $getAmounts,
                    'success' => true
                ]);
            }
            return $this->utils->jsonResponse(200, [
                'message' => 'No hay ventas para la fecha: ' . $date_get,
                'success' => false
            ]);
        } catch (\Throwable $th) {
            return $this->utils->jsonResponse(200, [
                'message' => 'Error al obtener las ventas: ' . $th->getMessage(),
                'success' => false
            ]);
        }
    }


    public function listarVentas($request = null)
    {
        try {
            $limite = 20;
            $pagina = 1;
            $fecha = date('Y-m-d'); // Fecha por defecto

            if ($request && isset($request['body'])) {
                $data = $request['body'];
                $fecha = $data['fecha'] ?? $fecha;
                $limite = $data['limite'] ?? 20;
                $pagina = $data['pagina'] ?? 1;
            }

            $ventas = $this->salesModel->listarVentas($limite, $pagina, $fecha);

            return $this->utils->jsonResponse(200, [
                'data' => $ventas,
                'paginacion' => [
                    'limite' => $limite,
                    'pagina' => $pagina
                ],
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return $this->utils->jsonResponse(500, [
                'message' => 'Error al listar ventas: ' . $th->getMessage(),
                'success' => false
            ]);
        }
    }


    // Registrar movimiento de caja
    public function registrarMovimientoCaja($data)
    {
        try {

            // Validar datos mínimos
            if (empty($data['monto']) || empty($data['tipo']) || empty($data['usuario_id']) || empty($data['descripcion'])) {
                return [
                    'message' => 'Faltan datos obligatorios para el movimiento de caja',
                    'success' => false
                ];
            }

            // Validar tipo de movimiento
            if (!in_array($data['tipo'], ['Ingreso', 'Egreso'])) {
                return [
                    'message' => 'Tipo de movimiento inválido. Use "Ingreso" o "Egreso"',
                    'success' => false
                ];
            }

            $movimientoId = $this->salesModel->registrarMovimientoCaja($data);

            return [
                'message' => 'Movimiento de caja registrado correctamente',
                'movimiento_id' => $movimientoId,
                'success' => true
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'Error al registrar movimiento: ' . $th->getMessage(),
                'success' => false
            ];
        }
    }

    // Abrir caja
    public function abrirCaja($request)
    {
        try {
            $data = $request['body'];

            // Validar datos mínimos
            if (empty($data['usuario_id']) || !isset($data['monto_inicial'])) {
                return $this->utils->jsonResponse(200, [
                    'message' => 'Faltan datos obligatorios para abrir caja',
                    'success' => false
                ]);
            }

            // Verificar si ya hay una caja abierta
            $cajaActual = $this->salesModel->obtenerEstadoCaja();
            if ($cajaActual) {
                return $this->utils->jsonResponse(200, [
                    'message' => 'Ya existe una caja abierta',
                    'caja' => $cajaActual,
                    'success' => false
                ]);
            }

            $cajaId = $this->salesModel->abrirCaja($data);

            return $this->utils->jsonResponse(200, [
                'message' => 'Caja abierta correctamente',
                'caja_id' => $cajaId,
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return $this->utils->jsonResponse(200, [
                'message' => 'Error al abrir caja: ' . $th->getMessage(),
                'success' => false
            ]);
        }
    }
    //verificar si la caja esta abierta
    public function verificarCaja($request)
    {
        $data = $request['body'];
        $cajaActual = $this->salesModel->obtenerEstadoCaja();
        if ($cajaActual) {
            return $this->utils->jsonResponse(200, [
                'message' => 'La caja esta abierta',
                'success' => true
            ]);
        }
        return $this->utils->jsonResponse(200, [
            'message' => 'No hay ninguna caja abierta',
            'success' => false
        ]);
    }
    //listar cajas
    public function listarCajas()
    {

        $cajas = $this->salesModel->listarCajas();
        if ($cajas) {
            return $this->utils->jsonResponse(200, [
                'message' => $cajas,
                'success' => true
            ]);
        }
        return $this->utils->jsonResponse(200, [
            'message' => 'No hay ninguna caja abierta',
            'success' => false
        ]);
    }
    // Cerrar caja
    public function cerrarCaja($request)
    {
        try {
            $data = $request['body'];

            // Validar datos mínimos
        
            if (empty($data['caja_id']) || !isset($data['monto_final'])) {
                return $this->utils->jsonResponse(200, [
                    'message' => 'Faltan datos obligatorios para cerrar caja',
                    'success' => false
                ]);
            }
            
            $result = $this->salesModel->cerrarCaja($data);

            if (!$result) {
                return $this->utils->jsonResponse(200, [
                    'message' => 'No se pudo cerrar la caja. Verifique el ID y que esté abierta',
                    'success' => false
                ]);
            }

            return $this->utils->jsonResponse(200, [
                'message' => 'Caja cerrada correctamente',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return $this->utils->jsonResponse(200, [
                'message' => 'Error al cerrar caja: ' . $th->getMessage(),
                'success' => false
            ]);
        }
    }

    // Obtener movimientos de caja
    public function obtenerMovimientosCaja()
    {
        try {
            /*$data = $request['body'] ?? [];

            $cajaId = $data['caja_id'] ?? null;
            $fecha = $data['fecha'] ?? null;

            $movimientos = $this->salesModel->obtenerMovimientosCaja($cajaId, $fecha);
*/
            $movimientos = $this->salesModel->obtenerMovimientosCaja();
            return $this->utils->jsonResponse(200, [
                'data' => $movimientos,
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return $this->utils->jsonResponse(200, [
                'message' => 'Error al obtener movimientos: ' . $th->getMessage(),
                'success' => false
            ]);
        }
    }

    // Obtener estado actual de la caja
    public function obtenerEstadoCaja($request = null)
    {
        try {
            $usuarioId = null;

            if ($request && isset($request['body']) && isset($request['body']['usuario_id'])) {
                $usuarioId = $request['body']['usuario_id'];
            }

            $estadoCaja = $this->salesModel->obtenerEstadoCaja($usuarioId);

            if (!$estadoCaja) {
                return $this->utils->jsonResponse(200, [
                    'message' => 'No hay ninguna caja abierta',
                    'success' => false
                ]);
            }

            return $this->utils->jsonResponse(200, [
                'data' => $estadoCaja,
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return $this->utils->jsonResponse(200, [
                'message' => 'Error al obtener estado de caja: ' . $th->getMessage(),
                'success' => false
            ]);
        }
    }
    //listar entradas
    public function listarEntradas()
    {
        try {
            $entradas = $this->salesModel->listarEntradas();
            if ($entradas) {
                return $this->utils->jsonResponse(200, [
                    'message' => $entradas,
                    'success' => true
                ]);
            }
            return $this->utils->jsonResponse(200, [
                'message' => 'No hay entradas',
                'success' => false
            ]);
        } catch (\Throwable $th) {
            return $this->utils->jsonResponse(200, [
                'message' => 'Error al listar entradas: ' . $th->getMessage(),
                'success' => false
            ]);
        }
    }
    //crear juego
    public function crearJuego($request)
    {
        $data = $request['body'];
        $juego = $this->salesModel->crearJuego($data);
        if ($juego) {
            return $this->utils->jsonResponse(200, [
                'message' => 'Juego creado correctamente',
                'success' => true
            ]);
        }
        return $this->utils->jsonResponse(200, [
            'message' => 'Error al crear el juego',
            'success' => false
        ]);
    }
    //editar juego
    public function editarJuego($request)
    {
        $data = $request['body'];
        $juego = $this->salesModel->editarJuego($data);
        if ($juego) {
            return $this->utils->jsonResponse(200, [
                'message' => 'Juego editado correctamente',
                'success' => true
            ]);
        }
        return $this->utils->jsonResponse(200, [
            'message' => 'Error al editar el juego',
            'success' => false
        ]);
    }
    
    //comprar entradas
    public function comprarEntradas($request)
    {
        $data = $request['body'];

        //first insert venta and after insert points play
        //get id user
        //get id play
        //get quantity points
        
        //update points user
         foreach ($data['entradas'] as $entrada) {
            $this->salesModel->updatePointsUser($entrada, $data);
        }
        $data['monto'] = $data['total'];
        $data['tipo'] = 'Ingreso';
        $data['descripcion'] = 'Venta de entradas';
        $insert = $this->salesModel->registrarMovimientoCaja($data);
        if ($insert) {
            return $this->utils->jsonResponse(200, [
                'message' => 'Venta registrada correctamente',
                'success' => true
            ]);
        }
        return $this->utils->jsonResponse(200, [
            'message' => 'Error al insertar la venta',
            'success' => false
        ]);
    }
}
