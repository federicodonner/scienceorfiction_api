<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Devuelve todas las drogas y sus medicinas
$app->get('/api/stock/{id}', function (Request $request, Response $response) {
    // El id del usuario logueado viene del middleware authentication
    $usuario_id = $request->getAttribute('usuario_id');
    try {
        // Obtiene el id del pastillero del request
        $pastillero_id = $request->getAttribute('id');

        // Verifica que el pastillero exista
        $sql = "SELECT * FROM pastillero WHERE id = $pastillero_id";

        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $pastilleros = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (count($pastilleros)==0) {
            $db = null;
            return messageResponse($response, 'El pastillero seleccionado no existe', 404);
        }

        // Verifica que el usuario tenga acceso al pastillero
        $permisos_usuario = verificarPermisosUsuarioPastillero($usuario_id, $pastillero_id);

        // Como es un GET verifica permisos de lectura
        if (!$permisos_usuario->acceso_lectura_pastillero) {
            $db = null;
            return messageResponse($response, 'No tiene permisos para editar el pastillero seleccionado', 403);
        }
        $sql = "SELECT * FROM droga WHERE pastillero_id = '$pastillero_id' ORDER BY nombre";

        $db = new db();
        $db = $db->connect();

        // Selecciona todas las drogas
        $stmt = $db->query($sql);
        $drogas = $stmt->fetchAll(PDO::FETCH_OBJ);

        //Encuentra las medicinas que tienen la droga seleccionada
        foreach ($drogas as $droga) {
            // Por cada droga, hace la query para buscar el stock
            $droga_id = $droga->id;
            $sql = "SELECT * FROM stock WHERE droga_id = $droga_id";
            $stmt = $db->query($sql);
            $stocks = $stmt->fetchAll(PDO::FETCH_OBJ);

            $droga_mg_total = 0;

            // Calcula para cuántos días hay stock
            foreach ($stocks as $stock) {
                $stockComprimido = $stock->comprimido;
                $stockCantidad = $stock->cantidad_doceavos/12;

                // droga_mg_total son los miligramos totales disponibles de la medicina
                $droga_mg_total = $droga_mg_total + $stockComprimido*$stockCantidad;
            }
            // Obtiene cuántos mg debe tomar el paciente por día
            $sql = "SELECT cantidad_mg FROM droga_x_dosis WHERE droga_id = $droga_id";
            $stmt = $db->query($sql);
            $dosis_diaria = $stmt->fetchAll(PDO::FETCH_OBJ);
            // Suma todas las dosis diarias de la droga
            $dosis_total = 0;
            foreach ($dosis_diaria as $dosis) {
                $dosis_total = $dosis_total + $dosis->cantidad_mg;
            }

            $droga->disponible_total = $droga_mg_total;
            $droga->dosis_total = $dosis_total;
            $droga->dosis_semanal = $dosis_total * 7;
            if ($dosis_total != 0) {
                $dias_disponible = floor($droga_mg_total/$dosis_total);
            } else {
                $dias_disponible = -1;
            }

            $droga->dias_disponible = $dias_disponible;

            $droga->stocks = $stocks;
        }

        // Genera un objeto para la respuesta
        $drogas_response->drogas = $drogas;

        $db = null;
        return dataResponse($response, $drogas_response, 200);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 503);
    }
})->add($authenticate);
