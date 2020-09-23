<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Agregar drogaxdosis
$app->post('/api/drogaxdosis', function (Request $request, Response $response) {
    // El id del usuario logueado viene del middleware authentication
    $usuario_id = $request->getAttribute('usuario_id');

    $droga_id = $request->getParam('droga_id');
    $dosis_id = $request->getParam('dosis_id');
    $cantidad_mg = $request->getParam('cantidad_mg');
    $notas = $request->getParam('notas');

    // Verifica que se hayan enviado los campos correctos
    if (!$droga_id || !$dosis_id || !$cantidad_mg) {
        return messageResponse($response, 'Campos incorrectos', 400);
    }
    try {
        // Verifica que la droga exista
        $sql="SELECT * FROM droga WHERE id = $droga_id";

        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $drogas = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (count($drogas)==0) {
            $db = null;
            return messageResponse($response, 'La droga seleccionada no existe', 404);
        }
        // Verifica que la dosis exista
        $sql="SELECT * FROM dosis WHERE id = $dosis_id";

        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $dosises = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (count($dosises)==0) {
            $db = null;
            return messageResponse($response, 'La toma seleccionada no existe', 404);
        }

        // Verifica que el usuario tenga acceso al pastillero
        $pastillero_id = $dosises[0]->pastillero_id;

        $permisos_usuario = verificarPermisosUsuarioPastillero($usuario_id, $pastillero_id);

        // Como es un POST verifica permisos de escritura
        if (!$permisos_usuario->acceso_edicion_pastillero) {
            $db = null;
            return messageResponse($response, 'No tiene permisos para editar el pastillero seleccionado', 403);
        }
        $sql = "INSERT INTO droga_x_dosis (droga_id,dosis_id,cantidad_mg,notas) VALUES (:droga_id,:dosis_id,:cantidad_mg,:notas)";

        $db = new db();
        $db = $db->connect();

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':droga_id', $droga_id);
        $stmt->bindParam(':dosis_id', $dosis_id);
        $stmt->bindParam(':cantidad_mg', $cantidad_mg);
        $stmt->bindParam(':notas', $notas);

        $stmt->execute();

        $db = null;
        return messageResponse($response, 'Dosis agregada exitosamente.', 201);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 500);
    }
})->add($authenticate);


// Actualizar drogaxdosis
$app->put('/api/drogaxdosis/{id}', function (Request $request, Response $response) {
    // El id del usuario logueado viene del middleware authentication
    $usuario_id = $request->getAttribute('usuario_id');
    $id = $request->getAttribute('id');

    try {
        // Verifica que la droga_x_dosis exista
        $sql="SELECT * FROM droga_x_dosis WHERE id = $id";

        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $drogaxdosises = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (count($drogaxdosises)==0) {
            $db = null;
            return messageResponse($response, 'La dosis seleccionada no existe', 404);
        }

        // Guarda los datos de la drogaxdosis anterior para los que no cambian
        $droga_id_anterior = $drogaxdosises[0]->droga_id;
        $dosis_id_anterior = $drogaxdosises[0]->dosis_id;
        $cantidad_mg_anterior = $drogaxdosises[0]->cantidad_mg;
        $notas = $drogaxdosises[0]->notas;

        // Obtiene el id de la droga para obtener el del pastillero
        // Y verificar que el usuario tenga permisos de edición
        $droga_id = $drogaxdosises[0]->droga_id;

        $sql = "SELECT * FROM droga WHERE id = $droga_id";
        $stmt = $db->query($sql);
        $drogas = $stmt->fetchAll(PDO::FETCH_OBJ);

        $pastillero_id = $drogas[0]->pastillero_id;

        $permisos_usuario = verificarPermisosUsuarioPastillero($usuario_id, $pastillero_id);

        // Como es un DELETE verifica permisos de escritura
        if (!$permisos_usuario->acceso_edicion_pastillero) {
            $db = null;
            return messageResponse($response, 'No tiene permisos para editar el pastillero seleccionado', 403);
        }
        // Variable para verificar si se especificó algún cambio
        $algun_cambio = false;
        // Si se quiere cambiar la droga, se verifica que exista
        $droga_id = $request->getParam('droga_id');
        if ($droga_id) {
            $algun_cambio = true;
            $sql="SELECT * FROM droga WHERE id = $droga_id";
            $stmt = $db->query($sql);
            $drogas = $stmt->fetchAll(PDO::FETCH_OBJ);
            if (count($drogas)==0) {
                $db = null;
                return messageResponse($response, 'Droga no encontrada', 404);
            }
        } else {
            $droga_id = $droga_id_anterior;
        }

        // Si se quiere cambiar la dosis, se verifica que exista
        $dosis_id = $request->getParam('dosis_id');
        if ($dosis_id) {
            $algun_cambio = true;
            $sql="SELECT * FROM dosis WHERE id = $dosis_id";
            $stmt = $db->query($sql);
            $dosises = $stmt->fetchAll(PDO::FETCH_OBJ);
            if (count($dosises)==0) {
                $db = null;
                return messageResponse($response, 'Dosis no encontrada', 404);
            }
        } else {
            $dosis_id = $dosis_id_anterior;
        }

        $cantidad_mg = $request->getParam('cantidad_mg');
        if ($cantidad_mg) {
            $algun_cambio = true;
        } else {
            $cantidad_mg = $cantidad_mg_anterior;
        }

        $notas = $request->getParam('notas');
        if ($notas) {
            $algun_cambio = true;
        } else {
            $notas = $notas_anterior;
        }

        if (!$algun_cambio) {
            $db = null;
            return messageResponse($response, 'Campos incorrectos', 400);
        }

        $sql = "UPDATE droga_x_dosis SET
        droga_id = :droga_id,
        dosis_id = :dosis_id,
        cantidad_mg = :cantidad_mg,
        notas = :notas
        WHERE id = $id";

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':droga_id', $droga_id);
        $stmt->bindParam(':dosis_id', $dosis_id);
        $stmt->bindParam(':cantidad_mg', $cantidad_mg);
        $stmt->bindParam(':notas', $notas);

        $stmt->execute();

        $sql="SELECT dxd.cantidad_mg, dxd.dosis_id, dxd.droga_id, dxd.id, d.nombre, dxd.notas FROM droga_x_dosis dxd LEFT JOIN droga d ON dxd.droga_id = d.id WHERE dxd.id = $id";

        $stmt = $db->query($sql);
        $drogaxdosises = $stmt->fetchAll(PDO::FETCH_OBJ);

        $drogaxdosis_response = $drogaxdosises[0];

        $db = null;
        return dataResponse($response, $drogaxdosis_response, 200);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 503);
    }
})->add($authenticate);



// Eliminar drogaxdosis
$app->delete('/api/drogaxdosis/{id}', function (Request $request, Response $response) {
    // El id del usuario logueado viene del middleware authentication
    $usuario_id = $request->getAttribute('usuario_id');
    $id = $request->getAttribute('id');
    try {
        // Verifica que la droga_x_dosis exista
        $sql = "SELECT * FROM droga_x_dosis WHERE id = $id";
        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $drogaxdosises = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (count($drogaxdosises)==0) {
            $db = null;
            return messageResponse($response, 'La toma seleccionada no existe', 404);
        }

        // Obtiene el id de la droga para obtener el del pastillero
        // Y verificar que el usuario tenga permisos de edición
        $droga_id = $drogaxdosises[0]->droga_id;

        $sql = "SELECT * FROM droga WHERE id = $droga_id";
        $stmt = $db->query($sql);
        $drogas = $stmt->fetchAll(PDO::FETCH_OBJ);

        $pastillero_id = $drogas[0]->pastillero_id;
        $permisos_usuario = verificarPermisosUsuarioPastillero($usuario_id, $pastillero_id);

        // Como es un DELETE verifica permisos de escritura
        if (!$permisos_usuario->acceso_edicion_pastillero) {
            $db = null;
            return messageResponse($response, 'No tiene permisos para editar el pastillero seleccionado', 403);
        }
        $sql = "DELETE FROM droga_x_dosis WHERE id = $id";
        $stmt = $db->prepare($sql);
        $stmt->execute();

        $db = null;
        return messageResponse($response, "Dosis eliminada exitosamente.", 200);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 503);
    }
})->add($authenticate);
