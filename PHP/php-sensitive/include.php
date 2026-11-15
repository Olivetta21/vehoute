<?php	
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Methods:POST");
	header("Access-Control-Allow-Headers: Content-Type, Authorization");

    //# DOTENV CONFIG
    define("ENVDIR",__DIR__);
    require ENVDIR . '/vendor/autoload.php';        
    Dotenv\Dotenv::createImmutable(ENVDIR)->load();
    //DOTENV CONFIG #

    define("DEVELOPMENT_ENV", $_ENV['DEVELOPMENT_ENV'] === 'true');
    
    function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        elseif (!empty($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
        elseif (DEVELOPMENT_ENV) return '0.0.0.0';
        else return 'unknown';
    }

    function errorMessage($error_title, $error_message) {
        $message = '' . $error_title . '' . (DEVELOPMENT_ENV ?  ':' . $error_message . '': '');
        $acc_tk = "";

        if (isset($_POST['access_token'])) {
            $acc_tk = json_decode($_POST['access_token'], true) ?? $_POST['access_token'] ?? "invalid_token";
        } else if (DEVELOPMENT_ENV) {
            $acc_tk = "unittest";
        } else {
            $acc_tk = "not_provided";
        }

        try {
            $pdo = getDataBase();
            if ($pdo) {
                $stmt = $pdo->prepare("INSERT INTO errors_log (titulo, mensagem, u_access_token, ip, url) VALUES (:titulo, :mensagem, :u_access_token, :ip, :url)");
                $stmt->execute([
                    ':titulo' => $error_title,
                    ':mensagem' => $error_message,
                    ':u_access_token' =>  $acc_tk,
                    ':ip' => getClientIp(),
                    ':url' => $_SERVER['REQUEST_URI'] ?? null
                ]);
            }
        } catch (Exception $e) {
            
        }
        return $message;
    }

    
    function mesclarArrayRecursivo(array $arrA, array $arrB): array
    {
        foreach ($arrB as $chave => $valor) {
            if (
                isset($arrA[$chave]) &&
                is_array($arrA[$chave]) &&
                is_array($valor)
            ) {
                // Se ambos forem arrays numéricos, concatena os elementos.
                if (
                    array_is_list($arrA[$chave]) &&
                    array_is_list($valor)
                ) {
                    $arrA[$chave] = array_merge($arrA[$chave], $valor);
                } else {
                    // Caso contrário, mescla recursivamente.
                    $arrA[$chave] = mesclarArrayRecursivo($arrA[$chave], $valor);
                }
            } else {
                // Sobrescreve o valor anterior.
                $arrA[$chave] = $valor;
            }
        }

        return $arrA;
    }

    function refazArrayComValoresStringDelimitados($array, $delimitador = '|') {
        $separado = [];
        foreach ($array as $index => $valor) {
            if (is_array($valor)) {
                $valor = implode($delimitador, refazArrayComValoresStringDelimitados($valor, $delimitador));
            }

            $t = explode($delimitador, $valor);
            foreach ($t as $subtabela) {
                if (!in_array($subtabela, $separado)) {
                    $separado[] = $subtabela;
                }
            }
        }
        return $separado;
    }

    class Auditoria {

        private $audits = [];

        function add($tabela, $acao, $antes, $depois, $sucesso) {
            if (!isset($GLOBALS['USUARIO_ID'])) { 
                if (DEVELOPMENT_ENV) {
                    $GLOBALS['USUARIO_ID'] = -1; //teste unitário
                } else return null;
            }
            

            $values = [
                ':usuario_id' => $GLOBALS['USUARIO_ID'],
                ':tabela' => $tabela,
                ':acao' => $acao
            ];

            if ($antes !== null) {
                $values[':antes'] = json_encode($antes);
            }
            if ($depois !== null) {
                $values[':depois'] = json_encode($depois);
            }
            if ($sucesso !== null) {
                $values[':sucesso'] = $sucesso ? 1 : 0;
            }

            $keys = array_keys($values);
            $columns = array_map(fn($key) => substr($key, 1), $keys);

            try {
                $pdo = getDataBase();
                if ($pdo) {
                    $stmt = $pdo->prepare("INSERT INTO audit_log (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $keys) . ") returning *");
                    $stmt->execute($values);

                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    $this->audits = [
                        'id' => $result['id'],
                        'tabela' => $tabela,
                        'acao' => $acao,
                        'antes' => $antes,
                        'depois' => $depois,
                        'sucesso' => $sucesso ? 1 : 0
                    ];

                    return $this;
                }
            } catch (Exception $e) {
                errorMessage("Erro ao registrar log de auditoria", $e->getMessage());
            }
            return null;
        }

        private function change($column, $value, $concat = true) {
            if (!array_key_exists($column, $this->audits)) {
                errorMessage("Coluna de log de auditoria não encontrada", $column);
                return;
            }
            if ($concat) {
                $current_value = $this->audits[$column];
                if ($current_value !== null) {
                    if (is_array($current_value)) {
                        $value = mesclarArrayRecursivo($current_value, is_array($value) ? $value : [$value]);
                    } else {
                        $value = $current_value . '|' . (is_array($value) ? json_encode($value) : $value);
                    }
                }                
            }
            $this->audits[$column] = $value;

            try {
                $pdo = getDataBase();
                if ($pdo) {
                    $sql = "UPDATE audit_log SET " . $column . " = :value WHERE id = :log_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':value' => is_array($value) ? json_encode($value) : $value,
                        ':log_id' => $this->audits['id']
                    ]);
                }
            } catch (Exception $e) {
                errorMessage("Erro ao atualizar log de auditoria", $e->getMessage());
            }
        }

        function sucesso($sucesso) {
            $this->change('sucesso', $sucesso ? 1 : 0, false);
        }

        function tabela($tabela, $concat = true) {
            $this->change('tabela', $tabela, $concat);
        }

        function acao($acao, $concat = true) {
            $this->change('acao', $acao, $concat);
        }

        function antes($antes, $concat = true) {
            $this->change('antes', $antes, $concat);
        }

        function depois($depois, $concat = true) {
            $this->change('depois', $depois, $concat);
        }
    }

    function validarSenha($senha) {
        if (strlen($senha) < 8) return 0;
        if (!preg_match('/[A-Z]/', $senha)) return 0;
        if (!preg_match('/[a-z]/', $senha)) return 0;
        if (!preg_match('/\d/', $senha)) return 0;
        if (!preg_match('/[#@$!%*?&]/', $senha)) return 0;
        return 1;
    }

    function validarTokenPublico($token) {
        return preg_match('/^[a-zA-Z0-9._%+-]{8,}$/', $token);
    }
    
    function validarNomeUsuario($nome) {
        return preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ]+(?:\s+[A-Za-zÀ-ÖØ-öø-ÿ]+)+$/', $nome);
    }

    function validarTelefone($telefone) {
        return preg_match('/^\+?[1-9]\d{7,14}$/', $telefone);
    }

    function validarEmail($email) {
        return preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email);
    }

    function validarLoginUsuario($login) {
        return preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ0-9 ._%+-]{8,}$/', $login);
    }

    function validarOTP($otp) {
        return preg_match('/^[A-Za-z0-9]{8,8}$/', $otp);
    }

    function normalizarFiltroTexto($valor) {
        if ($valor === null || is_array($valor) || is_object($valor)) {
            return null;
        }

        $valor = trim((string) $valor);
        if ($valor === '' || strtolower($valor) === 'undefined' || strtolower($valor) === 'null') {
            return null;
        }

        return $valor;
    }

    function validarIdPositivo($valor) {
        $min = DEVELOPMENT_ENV ? -100 : 1;
        return filter_var($valor, FILTER_VALIDATE_INT, ["options" => ["min_range" => $min]]) !== false;
    }

	function returnJson($data, $type = null) {
		$result = [];
        if (is_array($data) && count($data) > 0) {
            $type = $type ?: array_keys($data)[0];
			foreach ($data as $key => $value) {
				$result[$key] = is_bool($value) ? $value : mb_convert_encoding($value, 'UTF-8', 'auto');
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
    
    function getRequestValue($key, $check_type = null) {
        try {
            if (isset($_POST[$key])) {
                $value = json_decode($_POST[$key], true);
                
                return $check_type ? ($check_type($value) ? $value : null) : $value;
            }
        } catch (Exception) {

        }

        return null;
    }

    function getRandomHex($length = 256){
        $length = $length / 2;
        return bin2hex(random_bytes($length));
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
                $sql = "SELECT u.*, (adm.id is not null) as is_adm from
                        getUsuarioByToken(:access_token) u
                        left join administrador adm on adm.id = u.id";
                $perms_sql = "SELECT pu.nome from getPermissoesDoUsuario(:usuario_id) p join permissao_usuario pu on pu.id = p.perm_id where p.negado = false";

                $pdo = getDataBase();

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    "access_token"=> $access_token
                ]);

                if ($stmt->rowCount() === 1) {
                    $credenciais = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

                    $stmt = $pdo->prepare($perms_sql);
                    $stmt->execute([
                        "usuario_id" => '{' . $credenciais['id'] . '}'
                    ]);

                    if ($stmt->rowCount() > 0) {
                        $permissoes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                        $credenciais['permissoes'] = array_map('trim', $permissoes);
                    } else {
                        $credenciais['permissoes'] = [];
                    }

                    $GLOBALS['USUARIO_ID'] = $credenciais['id'];
                    $GLOBALS['USUARIO_PERMS'] = $credenciais['permissoes'];
                    $GLOBALS['USUARIO_ISADMIN'] = $credenciais['is_adm'];
                    $credenciais['pdo'] = $pdo;
                    return $credenciais;
                }
            }
        } catch (Exception) {

        }

        usleep(rand(1000, 10000) * 1000);
        returnJson(["invalid_token" => errorMessage("invalid_token", $_POST['access_token'] ?? "not_provided")]);
    }

    function userHasPermission($perm, $credenciais = null, $throw_error = true) {
        $permissions = [];
        if (isset($GLOBALS['USUARIO_PERMS'])) {
            $permissions = $GLOBALS['USUARIO_PERMS'];
        } else if ($credenciais && isset($credenciais['permissoes'])) {
            $permissions = $credenciais['permissoes'];
        }
        if (isset($GLOBALS['USUARIO_ISADMIN']) && $GLOBALS['USUARIO_ISADMIN'] === true) {
            $permissions[] = "Administrador";
        } else if ($credenciais && isset($credenciais['is_adm']) && $credenciais['is_adm'] === true) {
            $permissions[] = "Administrador";
        }
        $r = false;
        $permissions = array_map('strtolower', $permissions);
        $perm = is_array($perm) ? array_map('strtolower', $perm) : strtolower($perm);
        if (is_array($perm)) {
            $r = count(array_intersect($permissions, $perm)) == count($perm);
        } else {
            $r = in_array($perm, $permissions);
        }
        if ($throw_error && !$r) {
            throw new Exception("Usuario não possui permissão: " . (is_array($perm) ? implode(',', $perm) : $perm));
        }
        return $r;
    }

  
    function enviarEmail($data) {
        if (!$data['destinatario'] || !$data['mensagem'] || !$data['assunto']) {
            return ["resp"=>3,"msg"=>"incorrect mail parameters"];
        }

        try {
            $ch = curl_init($_ENV['MAIL_URL']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

            $response = curl_exec($ch);
            curl_close($ch);

            $resp = json_decode($response, true);

            if ($resp['status'] === "sucesso") {
                return ["resp"=>0,"msg"=>"success"];
            }

            return ["resp"=>1,"msg"=>"Servidor de emails fora do ar"];
        }
        catch (Exception $e) {        
            return ["resp"=>2,"msg"=>$e->getMessage()];
        }
    }  

?>