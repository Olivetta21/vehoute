<?php	
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Methods:POST");
	header("Access-Control-Allow-Headers: Content-Type, Authorization");

    //# DOTENV CONFIG
    define("ENVDIR",__DIR__);
    require ENVDIR . '/vendor/autoload.php';        
    Dotenv\Dotenv::createImmutable(ENVDIR)->load();
    //DOTENV CONFIG #
    
    function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        else return $_SERVER['REMOTE_ADDR'];
    }

	function returnJson($data, $type = null) {
		$result = [];
        if (is_array($data) && count($data) > 0) {
            $type = $type ?: array_keys($data)[0];
			foreach ($data as $key => $value) {
				$result[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
			}
		} else {
            $type = 'in_error';
            $result = 'internal_error';
        }
        
        $responses = [
            'success' => 200,
            'error' => 400,
            'warn' => 409,
            'in_error' => 500,
        ];
        http_response_code($responses[$type] ?? 200);
		echo json_encode($result);
        exit;
	}

    function getRandomBytes(){
        return bin2hex(random_bytes(256));
    }

    function getDataBase() {
        $host = $_ENV['DB_HOST'];
        $dbname = $_ENV['DB_DATABASE'];
        $user = $_ENV['DB_USERNAME'];
        $pass = $_ENV['DB_PASSWORD'];

        try {
            $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
            if ($pdo) return $pdo;
        } catch (Exception) {

        }

        returnJson(["error" => "database_error", "details"=>"failed to connect to database"]);
    }

    function resetAccessToken() {
        setcookie(
            "access_token",
            "", time()-9999, "/"
        );
    }
        
    function getCredentials(){ 
        try {
            if (isset($_POST['access_token'])) {
                $access_token = json_decode($_POST['access_token'], true);
                $sql = "SELECT * from getUsuarioByToken(:access_token)";

                $pdo = getDataBase();

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    "access_token"=> $access_token
                ]);

                if ($stmt->rowCount() === 1) {
                    $usuario = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
                    $usuario['pdo'] = $pdo;
                    return $usuario;
                }
            }
        } catch (Exception) {

        }

        usleep(rand(1000, 10000) * 1000);
        http_response_code(500);
        exit;
    }

?>