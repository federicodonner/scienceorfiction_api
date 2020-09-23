<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Devuevle un solo usuario
$app->get('/api/usuario', function (Request $request, Response $response) {
    // El id del usuario logueado viene del middleware authentication
    $usuario_id = $request->getAttribute('usuario_id');
    $sql = "SELECT * FROM usuario WHERE id = $usuario_id";

    try {
        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Add the users array inside an object
        if (!empty($usuarios)) {
            // Delete the password hash for the response
            unset($usuarios[0]->pass_hash);
            unset($usuarios[0]->pendiente_cambio_pass);

            $usuario = $usuarios[0];

            $sql = "SELECT p.*, u.nombre AS paciente_nombre, u.apellido AS paciente_apellido FROM usuario_x_pastillero uxp LEFT JOIN pastillero p ON uxp.pastillero_id = p.id LEFT JOIN usuario u ON p.paciente_id = u.id WHERE usuario_id = $usuario_id AND activo = 1";

            $stmt = $db->query($sql);
            $pastilleros = $stmt->fetchAll(PDO::FETCH_OBJ);

            $usuario->pastilleros = $pastilleros;

            $db = null;
            return dataResponse($response, $usuario, 200);
        } else {
            return messageResponse($response, 'Usuario incorrecto', 401);
        }
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 500);
    }
})->add($authenticate);



// Agrega un usuario
$app->post('/api/usuario', function (Request $request, Response $response) {
    // Get the user's details from the request body
    $nombre = $request->getParam('nombre');
    $apellido = $request->getParam('apellido');
    $email = strtolower($request->getParam('email'));
    $password = $request->getParam('password');

    // Verify that the information is present
    if ($nombre && $apellido && $email && $password) {
        // Verify that the email has an email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Check that there is no other users's with the same username
            $sql = "SELECT email FROM usuario where email = '$email'";

            try {
                // Get db object
                $db = new db();
                // Connect
                $db = $db->connect();

                $stmt = $db->query($sql);
                $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);

                if (empty($usuarios)) {

                    // If it is, create the hash for storage
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $false = 0;

                    // Store the information in the database
                    $sql = "INSERT INTO usuario (nombre, apellido, email, pass_hash, pendiente_cambio_pass) VALUES (:nombre,:apellido,:email,:password,:pendiente_cambio_pass)";

                    $stmt = $db->prepare($sql);
                    $stmt->bindparam(':nombre', $nombre);
                    $stmt->bindparam(':apellido', $apellido);
                    $stmt->bindparam(':password', $password_hash);
                    $stmt->bindparam(':email', $email);
                    $stmt->bindparam(':pendiente_cambio_pass', $false);

                    $stmt->execute();

                    $sql="SELECT * FROM usuario WHERE id = LAST_INSERT_ID()";
                    $stmt = $db->query($sql);
                    $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);

                    unset($usuarios[0]->password);
                    unset($usuarios[0]->pass_hash);
                    unset($usuarios[0]->pendiente_cambio_pass);

                    $usuario = $usuarios[0];

                    // Si se crea el profesor correctamente, lo loguea
                    // Store the user token in the database
                    // Prepare viarables
                    $access_token = random_str(32);
                    $now = time();
                    $user_id = $usuario->id;

                    // SQL statement
                    $sql = "INSERT INTO login (usuario_id,token,login_dttm) VALUES (:user_id,:token,:now)";

                    $stmt = $db->prepare($sql);

                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':token', $access_token);
                    $stmt->bindParam(':now', $now);

                    $stmt->execute();

                    $usuario->token = $access_token;
                    $usuario->grant_type = "password";

                    $db = null;

                    return dataResponse($response, $usuario, 201);
                } else { // if (empty($user)) {
                    return messageResponse($response, 'El usuario ya existe', 400);
                }
            } catch (PDOException $e) {
                $db = null;
                return messageResponse($response, $e->getMessage(), 500);
            }
        } else { // if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return messageResponse($response, 'Formato de email incorrecto', 400);
        }
    } else { // if ($name && $username && $password && $email) {
        return messageResponse($response, 'Campos incorrectos', 400);
    }
});




// Agrega un usuario
  $app->put('/api/usuario', function (Request $request, Response $response) {
      // El id del usuario logueado viene del middleware authentication
      $usuario_id = $request->getAttribute('usuario_id');
      // Verifica que se haya querido cambiar algún campo
      $nombre_request = $request->getParam('nombre');
      $apellido_request = $request->getParam('apellido');
      $email_request = strtolower($request->getParam('email'));
      $password_request = $request->getParam('password');

      if (!$nombre_request && !$apellido_request && !$email_request && !$password_request) {
          return messageResponse($response, 'Campos incorrectos, debe indicar el cambio de al menos un dato.', 400);
      }

      // Si hay un email, verifica que tenga el formato correcto
      if ($email_request) {
          // Verify that the email has an email format
          if (!filter_var($email_request, FILTER_VALIDATE_EMAIL)) {
              return messageResponse($response, 'Formato de email incorrecto.', 400);
          }
      }

      // Obtiene los datos actuales del usuario de la base de datos
      try {
          $sql="SELECT * FROM usuario WHERE id=$usuario_id";

          $db = new db();
          $db = $db->connect();

          $stmt = $db->query($sql);
          $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);

          $usuario_actual = $usuarios[0];

          $sql = "UPDATE usuario SET
          nombre = :nombre,
          apellido = :apellido,
          email = :email,
          pass_hash = :pass_hash
          WHERE id = $usuario_id";

          $stmt = $db->prepare($sql);
          // Por cada campo, sustituye el actual por el enviado si lo recibió
          if ($nombre_request) {
              $stmt->bindParam(':nombre', $nombre_request);
          } else {
              $stmt->bindParam(':nombre', $usuario_actual->nombre);
          }

          if ($apellido_request) {
              $stmt->bindParam(':apellido', $apellido_request);
          } else {
              $stmt->bindParam(':apellido', $usuario_actual->apellido);
          }

          if ($email_request) {
              $stmt->bindParam(':email', $email_request);
          } else {
              $stmt->bindParam(':email', $usuario_actual->email);
          }

          if ($password_request) {
              $password_hash = password_hash($password_request, PASSWORD_BCRYPT);
              $stmt->bindParam(':pass_hash', $password_hash);
          } else {
              $stmt->bindParam(':pass_hash', $usuario_actual->pass_hash);
          }

          $stmt->execute();

          $db = null;
          return messageResponse($response, 'Usuario actualizado exitosamente.', 200);
      } catch (PDOException $e) {
          $db = null;
          return messageResponse($response, $e->getMessage(), 500);
      }
  })->add($authenticate);
