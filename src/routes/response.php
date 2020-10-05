<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Crete a new rogue's response for an existing game
$app->post('/api/response', function (Request $request, Response $response) {

  // Verifies if the request has game information
    $rogueId = $request->getParam('rogueId');
    $responseOrder = $request->getParam('responseOrder');
    $items = $request->getParam('items');

    // Verify that the information is present
    if (!$rogueId || !$responseOrder || !$items) {
        return messageResponse($response, 'Missing fields', 400);
    }

    try {

        // Verify that the items are correct
        // Verifies that at least two items were submitted
        if (count($items)<2) {
            $db = null;
            return messageResponse($response, 'Only one item was submitted, all the game items need to be submitted. Please try again.', 400);
        }

        // Variables for verifying items
        $allHaveItemId = true;
        $haveOneScience = false;
        $haveOneFiction = false;

        foreach ($items as $item) {
            // create object for processing
            $itemObject = json_decode($item);

            if ($itemObject->fiction == 1) {
                $haveOneFiction = true;
            }

            if ($itemObject->fiction == 0) {
                $haveOneScience = true;
            }

            $itemId = $itemObject->itemId;
            if (!$itemId) {
                $allHaveItemId = false;
            }
        }

        if (!$allHaveItemId || !$haveOneFiction || !$haveOneScience) {
            $db = null;
            return messageResponse($response, 'There was a problem with the items, please verify them and try again.', 400);
        }

        // Verifies that the host exists
        $sql = "SELECT * FROM rogue WHERE id = $rogueId";

        $db = new db();
        $db = $db->connect();
        $stmt = $db->query($sql);
        $hosts = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (count($hosts)==0) {
            $db = null;
            return messageResponse($response, 'Rogue not found, please verify the information and try again.', 400);
        }


        // If everything is ok, insert the response and the items
        $sql="INSERT INTO response (rogueId, responseOrder) VALUES (:rogueId, :responseOrder)";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':rogueId', $rogueId);
        $stmt->bindParam(':responseOrder', $responseOrder);
        $stmt->execute();


        $sql="SELECT * FROM response WHERE id = LAST_INSERT_ID()";
        $stmt = $db->query($sql);
        $rogueResponses = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Finds the id of the inserted game
        $rogueResponse = $rogueResponses[0];

        $responseId = $rogueResponse->id;

        foreach ($items as $item) {
            // create object for processing
            $itemObject = json_decode($item);
            $itemId = $itemObject->itemId;
            $fiction = $itemObject->fiction;

            $sql="INSERT INTO response_x_item (responseId, itemId, fiction) VALUES (:responseId, :itemId, :fiction)";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':responseId', $responseId);
            $stmt->bindParam(':itemId', $itemId);
            $stmt->bindParam(':fiction', $fiction);
            $stmt->execute();
        }

        return messageResponse($response, 'Rogue response recorded. Thanks!', 201);
    } catch (PDOException $e) {
        $db = null;
        return messageResponse($response, $e->getMessage(), 500);
    }
});
