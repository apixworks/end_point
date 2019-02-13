<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require __DIR__ . '/../vendor/autoload.php';
include 'config.php';
// Create and configure Slim app
$app = new \Slim\App(["settings" => $config]);

//function getConnection() {
//    $dbhost="localhost";
//    $dbuser="root";
//    $dbpass="";
//    $dbname="angular";
//    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
//    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//    return $dbh;
//}

//Handle Dependencies
$container = $app->getContainer();

$container['db'] = function ($c) {

    try {
        $db = $c['settings']['db'];
        $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        );
        $pdo = new PDO("mysql:host=" . $db['servername'] . ";dbname=" . $db['dbname'],
            $db['username'], $db['password'], $options);
        return $pdo;
    } catch (\Exception $ex) {
        return $ex->getMessage();
    }

};

$app->get('/employees', function ($request, $response) {
    try {
        $con = $this->db;
        $sql = "SELECT * FROM employees";
        $result = null;
        foreach ($con->query($sql) as $row) {
            $result[] = $row;
        }
        if ($result) {
            return $response->withJson(array('status' => 'true', 'result' => $result), 200);
        } else {
            return $response->withJson(array('status' => 'Users Not Found'), 422);
        }

    } catch (\Exception $ex) {
        return $response->withJson(array('error' => $ex->getMessage()), 422);
    }

});

$app->post('/employees', function ($request, $response) {
    try {
        $con = $this->db;
        $sql = "INSERT INTO employees(name, password, mobile, position) VALUES (:name,:password,:mobile, :position)";
        $pre = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $values = array(
            ':name' => $request->getParam('name'),
            //Using hash for password encryption
            'password' => password_hash($request->getParam('password'), PASSWORD_DEFAULT),
            ':mobile' => $request->getParam('mobile'),
            ':position' => $request->getParam('position')
        );
        $result = $pre->execute($values);
        return $response->withJson(array('status' => 'User Created'), 200);

    } catch (\Exception $ex) {
        return $response->withJson(array('error' => $ex->getMessage()), 422);
    }

});

$app->get('/employees/{id}', function ($request, $response) {
    try {
        $id = $request->getAttribute('id');
        $con = $this->db;
        $sql = "SELECT * FROM employees WHERE id = :id";
        $pre = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $values = array(
            ':id' => $id);
        $pre->execute($values);
        $result = $pre->fetch();
        if ($result) {
            return $response->withJson(array('status' => 'true', 'result' => $result), 200);
        } else {
            return $response->withJson(array('status' => 'User Not Found'), 422);
        }

    } catch (\Exception $ex) {
        return $response->withJson(array('error' => $ex->getMessage()), 422);
    }

});

$app->delete('/employees/{id}', function ($request, $response) {
    try {
        $id = $request->getAttribute('id');
        $con = $this->db;
        $sql = "DELETE FROM employees WHERE id = :id";
        $pre = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $values = array(
            ':id' => $id);
        $result = $pre->execute($values);
        if ($result) {
            return $response->withJson(array('status' => 'User Deleted'), 200);
        } else {
            return $response->withJson(array('status' => 'User Not Found'), 422);
        }

    } catch (\Exception $ex) {
        return $response->withJson(array('error' => $ex->getMessage()), 422);
    }

});

$app->post('employee/login', function ($request, $response) {
    try {
        $con = $this->db;
        $sql = "SELECT * FROM employees WHERE name =:name AND password =:password";
        $pre = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $values = array(
            ':name' => $request->getParam('name'),
            //Using hash for password encryption
            'password' => password_hash($request->getParam('password'), PASSWORD_DEFAULT)
//            'password' => $request->getParam('password')
        );
        $pre->execute($values);
        $result = $pre->fetch();
        if ($result) {
            return $response->withJson(array('status' => 'true', 'result' => $result), 200);
//            return $response->withJson($result,200);
        } else {
            return $response->withJson(array('status' => 'Users Not Found'), 422);
        }

    } catch (\Exception $ex) {
        return $response->withJson(array('error' => $ex->getMessage()), 422);
    }

});

$app->get('/login', function ($request, $response, array $args) {
    // Get params from request.
    $user = $request -> getParam('name');
    $pass = $request -> password_hash($request->getParam('password'), PASSWORD_DEFAULT);
    // Get db connection
    $query = "SELECT * FROM users WHERE name='$user' AND password='$pass'";
    try{
        $con = $this->db;
        $pre = $con -> prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $pre->execute();
        $result = $pre -> fetch();
//        $response = "Sign In successful!";
        if ($result) {
            return $response->withJson(array('status' => 'true', 'result' => $result), 200);
        } else {
            return $response->withJson(array('status' => 'Users Not Found'), 422);
        }
    } catch(PDOException $e) {
        $response = '{"error": {"message":' .$e->getMessage().' }}';
    }
    return $response;
});

// Run app
$app->run();
