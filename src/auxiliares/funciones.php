<?php

use \Psr\Http\Message\ResponseInterface as Response;

// Responde un texto
 function messageResponse(Response $response, String $text, Int $status)
 {
     $responseBody = array('detail' => $text);
     $newResponse = $response
 ->withStatus($status)
 ->withJson($responseBody);
     return $newResponse;
 };

// Responde un objeto
function dataResponse(Response $response, object $data, Int $status)
{
    $newResponse = $response
->withStatus($status)
->withJson($data);
    return $newResponse;
}


// Devuelve un string aleatorio de largo especificado
 function random_str($length, $keyspace = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ')
 {
     $pieces = [];
     $max = mb_strlen($keyspace, '8bit') - 1;
     for ($i = 0; $i < $length; ++$i) {
         $pieces []= $keyspace[random_int(0, $max)];
     }
     return implode('', $pieces);
 };

 // Devuelve el usuario del login en base al token
  function verifyToken(String $access_token)
  {
      if (!empty($access_token)) {
          $sql = "SELECT * FROM login WHERE token = '$access_token'";
          try {
              $db = new db();
              $db = $db->connect();
              $stmt = $db->query($sql);
              $users = $stmt->fetchAll(PDO::FETCH_OBJ);
              $db = null;
              return $users;
          } catch (PDOException $e) {
              echo '{"error":{"text": '.$e->getMessage().'}}';
          }
      } else {
          return [];
      }
  };


function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8));
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}
