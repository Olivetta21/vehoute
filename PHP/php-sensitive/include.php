<?php
    //# DOTENV CONFIG
    define("ENVDIR",__DIR__);
    require ENVDIR . '/vendor/autoload.php';        
    Dotenv\Dotenv::createImmutable(ENVDIR)->load();
    //DOTENV CONFIG #

    define("DEVELOPMENT_ENV", ($_ENV['DEVELOPMENT_ENV'] ?? 'false') === 'true');
    define("SITE_URL", $_ENV['SITE_URL'] ?? "http://localhost");

    // Cabeçalhos de CORS e segurança (CORS_ORIGIN permite restringir a origem)
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        header("Access-Control-Allow-Origin: " . ($_ENV['CORS_ORIGIN'] ?? '*'));
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("Referrer-Policy: no-referrer");
        header("Cache-Control: no-store");
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
    
    function getClientIp() {
        // Cabeçalhos enviados pelo cliente só são confiáveis atrás de um proxy próprio
        if (($_ENV['TRUST_PROXY'] ?? 'false') === 'true' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $partes = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim(end($partes));
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
        return DEVELOPMENT_ENV ? '0.0.0.0' : 'unknown';
    }

    /** Remove segredos antes de gravar dados em log ou auditoria. */
    function redigirSensivel($dados) {
        if (!is_array($dados)) return $dados;
        $sensiveis = ['senha', 'senha_atual', 'password', 'otp', 'token', 'access_token', 'r_senha'];
        foreach ($dados as $chave => $valor) {
            if (is_string($chave) && in_array(strtolower($chave), $sensiveis, true)) {
                $dados[$chave] = '***';
            } else if (is_array($valor)) {
                $dados[$chave] = redigirSensivel($valor);
            }
        }
        return $dados;
    }

    /** Conexão exclusiva (e reaproveitada) para logs: não sofre rollback da transação principal. */
    function getLogDataBase() {
        static $pdo = null;
        if ($pdo === null) $pdo = getDataBase();
        return $pdo;
    }

    function errorMessage($error_title, $error_message) {
        $message = '' . $error_title . '' . (DEVELOPMENT_ENV ?  ':' . $error_message . '': '');
        // O token de sessão nunca é gravado em claro, apenas o seu hash
        $token = isset($_POST['access_token']) ? json_decode($_POST['access_token'], true) : null;
        if (is_string($token) && $token !== '') {
            $acc_tk = 'sha256:' . hash('sha256', $token);
        } else {
            $acc_tk = DEVELOPMENT_ENV ? 'unittest' : 'not_provided';
        }

        $detalhe = is_scalar($error_message) || $error_message === null
            ? (string) $error_message
            : json_encode(redigirSensivel($error_message));
        $detalhe = preg_replace('/access_token=[A-Za-z0-9]+/', 'access_token=***', $detalhe);
        if (preg_match('/senha|otp|codigo|código|access_token/iu', $error_title)
            && preg_match('/inválid|não atende|invalid/iu', $error_title)) {
            $detalhe = '***';
        }

        try {
            $stmt = getLogDataBase()->prepare("INSERT INTO errors_log (titulo, mensagem, u_access_token, ip, url) VALUES (:titulo, :mensagem, :u_access_token, :ip, :url) RETURNING id");
            $stmt->execute([
                ':titulo' => $error_title,
                ':mensagem' => $detalhe,
                ':u_access_token' =>  $acc_tk,
                ':ip' => substr(getClientIp(), 0, 45),
                ':url' => $_SERVER['REQUEST_URI'] ?? null
            ]);
            // returnJson anexa este id à resposta, para o usuário informar ao suporte
            $GLOBALS['ULTIMO_ERRO_ID'] = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log("Vehoute: falha ao gravar errors_log: " . $e->getMessage());
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
            

            $antes = redigirSensivel($antes);
            $depois = redigirSensivel($depois);

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
                $pdo = getLogDataBase();
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
            $value = redigirSensivel($value);
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
                $pdo = getLogDataBase();
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
        if (!is_string($senha) || strlen($senha) < 8 || strlen($senha) > 72) return 0; // bcrypt ignora além de 72 bytes
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

    /** Identificação do erro para o suporte: id em errors_log (se houve registro) e horário. */
    function anexarReferenciaDoErro(array $result) {
        if (!empty($GLOBALS['ULTIMO_ERRO_ID'])) $result['erro_id'] = $GLOBALS['ULTIMO_ERRO_ID'];
        $result['erro_data'] = date(DATE_ATOM);
        return $result;
    }

	function returnJson($data, $type = null) {
        if (is_array($data) && count($data) > 0) {
            $type = $type ?: array_keys($data)[0];
            $result = $data;
		} else {
            $type = 'in_error';
            $result = ['error' => 'internal_error'];
        }

        $responses = [
            'success' => 200,
            'error' => 400,
            'invalid_token' => 401,
            'nopermission' => 403,
            'warn' => 409,
            'rate_limit' => 429,
            'in_error' => 500,
        ];
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($responses[$type] ?? 200);
        }
        if (($responses[$type] ?? 200) >= 400) $result = anexarReferenciaDoErro($result);
		echo json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        exit;
	}

    function getRequestValue($key, $check_type = null) {
        if (!isset($_POST[$key]) || !is_string($_POST[$key])) return null;
        $value = json_decode($_POST[$key], true);
        return $check_type ? ($check_type($value) ? $value : null) : $value;
    }

    /**
     * Limite de tentativas por IP (janela fixa em arquivo), contra força bruta e spam de e-mail.
     * Chamar apenas nos endpoints. Encerra a requisição com HTTP 429 ao exceder.
     */
    function limitarRequisicoes($acao, $maximo, $janela_segundos, $chave = '') {
        $arquivo = sys_get_temp_dir() . '/vehoute_rl_' . hash('sha256', $acao . '|' . getClientIp() . '|' . strtolower((string) $chave));
        $fp = @fopen($arquivo, 'c+');
        if (!$fp) return;
        flock($fp, LOCK_EX);
        $dados = json_decode(stream_get_contents($fp), true);
        if (!is_array($dados) || $dados['expira'] <= time()) {
            $dados = ['total' => 0, 'expira' => time() + $janela_segundos];
        }
        $dados['total']++;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($dados));
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($dados['total'] > $maximo) {
            if (!headers_sent()) header('Retry-After: ' . max(1, $dados['expira'] - time()));
            returnJson(["error" => "Muitas tentativas. Aguarde alguns minutos e tente novamente."], 'rate_limit');
        }
    }

    function revogarTokensDoUsuario($pdo, $usuario_id, $exceto_token = null) {
        $sql = "delete from usuario_access_token where usuario_id = :usuario_id";
        $params = ["usuario_id" => $usuario_id];
        if ($exceto_token !== null) {
            $sql .= " and token <> :token";
            $params["token"] = $exceto_token;
        }
        $pdo->prepare($sql)->execute($params);
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
        $port = $_ENV['DB_PORT'] ?? '5432';

        try {
            return new PDO("pgsql:host=$host;port=$port;dbname=$dbname;connect_timeout=5", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (Exception $e) {
            error_log("Vehoute: falha ao conectar no banco: " . $e->getMessage());
        }

        returnJson(["error" => "database_error", "details"=>"failed to connect to database"], 'in_error');
    }

    function definirCookieAccessToken($valor, $expira) {
        setcookie("access_token", $valor, [
            'expires' => $expira,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Strict',
            // httponly fica desligado: o frontend lê o cookie para restaurar a sessão
        ]);
    }

    function resetAccessToken() {
        definirCookieAccessToken("", time() - 9999);
    }

    function getCredentials(){ 
        try {
            if (isset($_POST['access_token']) && is_string($_POST['access_token'])) {
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

        usleep(random_int(200, 800) * 1000); // atraso curto: não prende os workers do servidor
        returnJson(["invalid_token" => errorMessage("invalid_token", "sessão ausente ou expirada")]);
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
            // Em requisições web o frontend recebe a chave "nopermission"; em CLI (testes) segue a exceção
            if (PHP_SAPI !== 'cli') {
                $nomes = implode(', ', (array) $perm);
                errorMessage("Usuario não possui permissão", $nomes);
                returnJson(["nopermission" => $nomes]);
            }
            throw new Exception("Usuario não possui permissão: " . (is_array($perm) ? implode(',', $perm) : $perm));
        }
        return $r;
    }

  
    function enviarEmail($data) {
        //Retorna json: {"status":<string>, "mensagem":<string>}
        if (!isset($data) || !isset($data['destinatario']) || !validarEmail($data['destinatario'])) {
            return ["status"=>"erro","mensagem"=>"dados inválidos"];
        }

        try {
            $ch = curl_init($_ENV['MAIL_URL']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

            $response = curl_exec($ch);
            curl_close($ch);

            $resp = json_decode($response, true);

            //cofirma se a resposta é válida
            if (!is_array($resp) || !isset($resp['status']) || !isset($resp['mensagem'])) {
                return ["status"=>"erro","mensagem"=>"Resposta inválida do serviço de envio de email: " . $response];
            }

            return $resp;
        }
        catch (Exception $e) {        
            return ["status"=>"erro","mensagem"=>$e->getMessage()];
        }
    }


    function getUserMail($user_id) {
        try {
            $pdo = getDataBase();
            $stmt = $pdo->prepare("SELECT email FROM usuario WHERE id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            if ($stmt->rowCount() === 1) {
                return $stmt->fetchColumn();
            }
        }
        catch (Exception $e) {
            errorMessage("Erro ao buscar email do usuário", $e->getMessage());
        }
        return null;
    }

    function getUserName($user_id) {
        try {
            $pdo = getDataBase();
            $stmt = $pdo->prepare("SELECT nome FROM usuario WHERE id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            if ($stmt->rowCount() === 1) {
                return $stmt->fetchColumn();
            }
        }
        catch (Exception $e) {
            errorMessage("Erro ao buscar nome do usuário", $e->getMessage());
        }
        return null;
    }

    function getUserByEmail($email) {
        try {
            $pdo = getDataBase();
            $stmt = $pdo->prepare("SELECT id, nome FROM usuario WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->rowCount() === 1) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        catch (Exception $e) {
            errorMessage("Erro ao buscar nome do usuário", $e->getMessage());
        }
        return null;
    }

    function getURNameOfOwnerByTrackerID($tracker_id) {
        try {
            $pdo = getDataBase();
            $stmt = $pdo->prepare("SELECT nome from usuario_rastreador where rastreador_id = :tracker_id and usuario_id = (select dono_id from rastreador where id = :tracker_id);");
            $stmt->execute([':tracker_id' => $tracker_id]);
            if ($stmt->rowCount() === 1) {
                return $stmt->fetchColumn();
            }
        }
        catch (Exception $e) {
            errorMessage("Erro ao buscar nome do UR pelo rastreador", $e->getMessage());
        }
        return null;
    }

    function enviarNotificacaoEmail($data) {
        if (!isset($data) || !isset($data['destinatario']) || !validarEmail($data['destinatario'])) {
            return ["status"=>"erro","mensagem"=>"dados inválidos"];
        }

        $usuario = getUserByEmail($data['destinatario']);
        if (!$usuario) {
            return ["status"=>"erro","mensagem"=>"não tem um usuário com esse email"];
        }

        try {
            $pdo = getDataBase();
            $stmt = $pdo->prepare("SELECT variavel from getconfiguracoesdousuario(:usuario_id) where nome = 'Receber notificação por Email'");
            $stmt->execute([':usuario_id' => $usuario['id']]);
            if ($stmt->rowCount() !== 1) {
                return ["status"=>"erro","mensagem"=>"permissao para enviar email de notificação não encontrada"];
            }
            $permite = $stmt->fetchColumn();
            $permite = $permite === 'true' ? true : false;

            if (!$permite) {
                return ["status"=>"erro","mensagem"=>"sem permissao para enviar notificação"];
            }

            return enviarEmail($data);
        }
        catch (Exception $e) {
            errorMessage("Erro ao enviar notificação por email", $e->getMessage());
            return ["status"=>"erro","mensagem"=>"falha ao enviar notificação"];
        }
    }

?>