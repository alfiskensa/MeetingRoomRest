<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require_once '../includes/DbOperation.php';

//Creating a new app with the config to show errors
$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true
    ]
]);


//registering a new user
$app->post('/register', function (Request $request, Response $response) {
    if (isTheseParametersAvailable(array('name', 'email', 'password', 'jabatan'))) {
        $requestData = $request->getParsedBody();
        $name = $requestData['name'];
        $email = $requestData['email'];
        $password = $requestData['password'];
        $jabatan = $requestData['jabatan'];
        $db = new DbOperation();
        $responseData = array();

        $result = $db->registerUser($name, $email, $password, $jabatan);

        if ($result == USER_CREATED) {
            $responseData['error'] = false;
            $responseData['message'] = 'Registered successfully';
            $responseData['user'] = $db->getUserByEmail($email);
        } elseif ($result == USER_CREATION_FAILED) {
            $responseData['error'] = true;
            $responseData['message'] = 'Some error occurred';
        } elseif ($result == USER_EXIST) {
            $responseData['error'] = true;
            $responseData['message'] = 'This email already exist, please login';
        }

        $response->getBody()->write(json_encode($responseData));
    }
});


//user login route
$app->post('/login', function (Request $request, Response $response) {
    if (isTheseParametersAvailable(array('email', 'password'))) {
        $requestData = $request->getParsedBody();
        $email = $requestData['email'];
        $password = $requestData['password'];

        $db = new DbOperation();

        $responseData = array();

        if ($db->userLogin($email, $password)) {
            $responseData['error'] = false;
            $responseData['user'] = $db->getUserByEmail($email);
        } else {
            $responseData['error'] = true;
            $responseData['message'] = 'Invalid email or password';
        }

        $response->getBody()->write(json_encode($responseData));
    }
});

//getting all rooms
$app->get('/rooms', function (Request $request, Response $response) {
    $db = new DbOperation();
    $rooms = $db->getAllRooms();
    $response->getBody()->write(json_encode(array("rooms" => $rooms)));
});

//getting messages for a user
$app->get('/messages/{id}', function (Request $request, Response $response) {
    $userid = $request->getAttribute('id');
    $db = new DbOperation();
    $messages = $db->getMessages($userid);
    $response->getBody()->write(json_encode(array("messages" => $messages)));
});

//updating a user
$app->post('/update/{id}', function (Request $request, Response $response) {
    if (isTheseParametersAvailable(array('name', 'email', 'jabatan'))) {
        $id = $request->getAttribute('id');

        $requestData = $request->getParsedBody();

        $name = $requestData['name'];
        $email = $requestData['email'];
        $gender = $requestData['gender'];


        $db = new DbOperation();

        $responseData = array();

        if ($db->updateProfile($id, $name, $email, $gender)) {
            $responseData['error'] = false;
            $responseData['message'] = 'Updated successfully';
            $responseData['user'] = $db->getUserByEmail($email);
        } else {
            $responseData['error'] = true;
            $responseData['message'] = 'Not updated';
        }

        $response->getBody()->write(json_encode($responseData));
    }
});

//updating a password user
$app->post('/update_password/{id}', function (Request $request, Response $response) {
    if (isTheseParametersAvailable(array('oldpassword, newpassword'))) {
        $id = $request->getAttribute('id');

        $requestData = $request->getParsedBody();

        $OldPassword = $requestData['oldpass'];
        $NewPassword = $requestData['newpass'];


        $db = new DbOperation();

        $responseData = array();

        if($db->isPassword($OldPassword)){
			if ($db->updateProfile($id, $name, $email, $password, $gender)) {
				$responseData['error'] = false;
				$responseData['message'] = 'Updated successfully';
				$responseData['user'] = $db->getUserByEmail($email);
			} else {
				$responseData['error'] = true;
				$responseData['message'] = 'Not updated';
			}
		}else{
			$responseData['error'] = true;
			$responseData['message'] = 'Wrong Old Password, is that you?';
		}
        $response->getBody()->write(json_encode($responseData));
    }
});


//sending message to user
$app->post('/sendmessage', function (Request $request, Response $response) {
    if (isTheseParametersAvailable(array('id_user', 'kd_ruangan', 'tgl_penggunaan', 'ket'))) {
        $requestData = $request->getParsedBody();
        $id = $requestData['id_user'];
        $room = $requestData['kd_ruangan'];
        $date_used = $requestData['tgl_penggunaan'];
        $ket = $requestData['ket'];

        $db = new DbOperation();

        $responseData = array();
		
		$result = $db->sendMessage($id, $room, $date_used, $ket);

        if ($result == true) {
            $responseData['error'] = false;
            $responseData['message'] = 'Booking room successfully';
        } elseif ($result == false) {
            $responseData['error'] = true;
            $responseData['message'] = 'Could not book room';
        } elseif ($result == ROOM_NOT_AVAILABLE) {
            $responseData['error'] = true;
            $responseData['message'] = 'The room is not available, please changes the date of use';
        }

        $response->getBody()->write(json_encode($responseData));
    }
});

//function to check parameters
function isTheseParametersAvailable($required_fields)
{
    $error = false;
    $error_fields = "";
    $request_params = $_REQUEST;

    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        $response = array();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echo json_encode($response);
        return false;
    }
    return true;
}


$app->run();