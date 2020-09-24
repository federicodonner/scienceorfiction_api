<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// // Returns all rogues
  $app->get('/api/rogue', function (Request $request, Response $response) {
      try {
          $sql = "SELECT * FROM rogue";

          $db = new db();
          $db = $db->connect();

          // Select all rogues
          $stmt = $db->query($sql);
          $rogues = $stmt->fetchAll(PDO::FETCH_OBJ);

          // Create object for response
          $roguesResponse->rogues = $rogues;
          $db = null;
          return dataResponse($response, $roguesResponse, 200);
      } catch (PDOException $e) {
          $db = null;
          return messageResponse($response, $e->getMessage(), 503);
      }
  });


// Returns a specific rogue
$app->get('/api/rogue/{id}', function (Request $request, Response $response) {
    $rogueId = $request->getAttribute('id');
    try {
        $sql = "SELECT * FROM rogue WHERE id = $rogueId";

        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $rogues = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (count($rogues)==0) {
            $db = null;
            return messageResponse($response, 'Rogue not found', 404);
        }

        $db = null;
        return dataResponse($response, $rogues[0], 200);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 503);
    }
});



// Crete a new rogue
$app->post('/api/rogue', function (Request $request, Response $response) {
    // Get the data from the request
    $firstName = $request->getParam('firstName');
    $lastName = $request->getParam('lastName');
    $uploadedFiles = $request->getUploadedFiles();

    // Verify that the information is present

    if (!$firstName || !$lastName) {
        return messageResponse($response, 'Missing fields', 400);
    }

    // If there is an image present, process it and upload it
    if (count($uploadedFiles) != 0) {
        $directory = $this->get('upload_directory');
        $uploadedFile = $uploadedFiles['picture'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
            $basename = bin2hex(random_bytes(8));
            $filename = sprintf('%s.%0.8s', $basename, $extension);
            $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        }
    }

    try {
        // Insert information into the db
        $sql="INSERT INTO rogue (firstName, lastName, picture) VALUES (:firstName, :lastName, :picture)";

        $db = new db();
        $db = $db->connect();

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':firstName', $firstName);
        $stmt->bindParam(':lastName', $lastName);
        $stmt->bindParam(':picture', $filename);
        $stmt->execute();

        $sql="SELECT * FROM rogue WHERE id = LAST_INSERT_ID()";
        $stmt = $db->query($sql);
        $rogues= $stmt->fetchAll(PDO::FETCH_OBJ);

        return dataResponse($response, $rogues[0], 201);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 500);
    }
});
