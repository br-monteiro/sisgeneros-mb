<?php
/**
 * @Model Acesso
 */
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use HTR\Helpers\Session\Session;
use HTR\Helpers\Criptografia\Criptografia as Cripto;
use Respect\Validation\Validator as v;

class AcessoModel extends CRUD
{

    // Tabela usada neste Model
    protected $entidade = 'users';
    protected $id;
    protected $username;
    protected $password;
    protected $omId;
    protected $name;
    protected $email;
    protected $nivel;
    protected $lastIp;
    protected $active;
    protected $time;
    // Recebe o resultado da consulta feita no Banco de Dados
    private $resultadoPaginator;
    // Recebe o Array de links da navegação da paginação
    private $navPaginator;

    /**
     * Método uaso para retornar todos os dados da tabela.
     */
    public function returnAll()
    {
        /*
         * Método padrão do sistema usado para retornar todos os dados da tabela
         */
        return $this->findAll();
    }

    public function paginator($pagina)
    {
        $innerJoin = " INNER JOIN om ON users.om_id = om.id";
        $dados = [
            'entidade' => $this->entidade . $innerJoin,
            'pagina' => $pagina,
            'maxResult' => 10,
            'select' => 'users.*, om.indicativo_naval'
            //'where' => 'nome LIKE ? ORDER BY nome',
            //'bindValue' => [0 => '%MONTEIRO%']
        ];

        $paginator = new Paginator($dados);
        $this->resultadoPaginator = $paginator->getResultado();
        $this->navPaginator = $paginator->getNaveBtn();
    }

    public function novo()
    {
        // Seta automaticamente os atributos necessários
        $this->startSeters()
            // Valida os Dados enviados através do formulário
            ->validaPassword()
            ->validaUsername()
            ->validaOmId()
            ->validaName()
            ->validaEmail()
            ->validaNivel()
            // Verifica se há registro igual
            ->evitarDuplicidade();

        $dados = [
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'om_id' => $this->getOmId(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'nivel' => $this->getNivel(),
            'trocar_senha' => 1,
            'last_ip' => $this->getIp(),
            'last_access' => $this->getTime(),
            'active' => 1, // 1-ativo; 0-inativo  Default : 1
            'created_at' => $this->getTime(),
            'updated_at' => $this->getTime()
        ];

        if (parent::novo($dados)) {
            msg::showMsg('111', 'success');
        } else {
            msg::showMsg('000', 'danger');
        }
    }

    public function editar()
    {
        // Seta automaticamente os atributos necessários
        $this->startSeters()
            // Valida os Dados enviados através do formulário
            ->validaId()
            ->validaUsername()
            ->validaOmId()
            ->validaName()
            ->validaEmail()
            ->validaNivel()
            // Verifica se há registro igual
            ->evitarDuplicidade();

        $dados = [
            'id' => $this->getId(),
            'username' => $this->getUsername(),
            'om_id' => $this->getOmId(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'nivel' => $this->getNivel(),
            'active' => $this->getActive(), // 1-ativo; 0-inativo  Default : 1
            'updated_at' => $this->getTime()
        ];

        if ($this->getPassword()) {
            $this->validaPassword();
            $dados['password'] = $this->getPassword();
        }

        // Verifica se há uma sessão iniciada
        if (!isset($_SESSION['userId'])) {
            $session = new Session();
            $session->startSession();
        }
        // consulta dados o usuário logado
        $user = $this->findById($_SESSION['userId']);

        if ($user['nivel'] == 1 && ($user['id'] != $this->getId())) {
            // seta a troca de senha na próxima vez que o usuário logar
            $dados['trocar_senha'] = 1;
        } else {
            // Para usuário com o nível diferente de 1-Addministrador
            $this->setId($user['id']);
            $dados['active'] = $user['active'];
            $dados['nivel'] = $user['nivel'];
            $dados['om_id'] = $user['om_id'];
        }

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function remover($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . APPDIR . 'acesso/ver/');
        }
    }

    public function findById($id)
    {
        $cripto = new Cripto;
        $value = parent::findById($id);

        if ($value) {
            // decodifica os campos de USERNAME e EMAIL
            $value['username'] = $cripto->decode($value['username']);
            $value['email'] = $cripto->decode($value['email']);
            return $value;
        }

        msg::showMsg('Este registro não foi encontrado. Você será redirecionado em 5 segundos.'
            . '<meta http-equiv="refresh" content="0;URL=' . APPDIR . 'acesso" />', 'danger', false);
    }
    /*
     * Método usado para alterar a senha do usuário no primeiro acesso
     */

    public function mudarSenha($id)
    {
        $this->setTime()
            ->setPassword(filter_input(INPUT_POST, 'password'))
            ->validaPassword();

        $dados = [
            'password' => $this->getPassword(),
            'trocar_senha' => 0,
            'updated_at' => time()
        ];

        if (parent::editar($dados, $id)) {
            msg::showMsg('A senha foi alterada com sucesso! '
                . 'Você será redirecionado para a página inicial em 5 segundos.'
                . '<meta http-equiv="refresh" content="5;URL=' . APPDIR . '" />'
                . '<script>setTimeout(function(){ window.location = "' . APPDIR . '"; }, 5000); </script>', 'success');
        }
    }

    private function evitarDuplicidade()
    {
        /// Evita a duplicidade de registros
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND name = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getName());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este Nome.'
                . '<script>focusOn("name")</script>', 'warning');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND email = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getEmail());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este E-mail.'
                . '<script>focusOn("email")</script>', 'warning');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND username = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getUsername());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('O Login indicado não pode ser usado. Por favor, escolha outro Login.'
                . '<script>focusOn("username")</script>', 'warning');
        }
    }

    public function login()
    {
        // Recebe o valor enviado pelo formulário de login
        $username = filter_input(INPUT_POST, 'username');
        $password = filter_input(INPUT_POST, 'password');

        // Verifica se todos os campos foram preenchidos
        if ($username && $password) {
            $cripto = new Cripto;
            // cripitografa os dados enviados
            $username = $cripto->encode($username);
            // consulta se existe um susário registrado com o USERNAME fornecido
            $result = $this->findByUsername($username);

            if (!$result) {
                // retorna a mensagem de dialogo
                msg::showMsg('<strong>Usuário inválido.</strong>'
                    . ' Verifique se digitou corretamente.'
                    . '<script>focusOn("username");</script>', 'warning');
            }

            if ($result['active'] === '2') {
                // retorna a mensagem de dialogo
                msg::showMsg('<strong>Usuário Bloqueado!</strong><br>'
                    . ' Consulte o Admistrador do Sistema para mais informações.'
                    . '<br><style>body{background-color:#CD2626;</style>'
                    . ADCONT, 'danger');
            }

            // verifica a autenticidade da senha
            if ($cripto->passVerify($password, $result['password'])) {
                // Caso seja um usuário autêntico, inicia a sessão
                $this->registerSession($result);
                return; // stop script
            } else {
                // retorna a mensagem de dialogo
                msg::showMsg('<strong>Algo está errado...</strong>'
                    . ' Verifique se digitou corretamente seu login e senha.'
                    . '<script>focusOn("password");</script>', 'warning');
            }
        }
        // retorna a mensagem de dialogo
        msg::showMsg('Todos os campos são preenchimento obrigatório.', 'danger');
    }

    private function registerSession($dados)
    {
        $session = new Session();
        $session->startSession();
        $_SESSION['token'] = $session->getToken();
        $_SESSION['userId'] = $dados['id'];
        echo '<meta http-equiv="refresh" content="0;URL=' . APPDIR . '" />'
        . '<script>window.location = "' . APPDIR . '"; </script>';
        return true; // stop script
    }

    public function logout()
    {
        $session = new Session();
        return $session->stopSession();
    }

    private function startSeters()
    {
        // Seta todos os valores
        $this->setTime(time())
            ->setId()
            ->setUsername(filter_input(INPUT_POST, 'username'))
            ->setPassword(filter_input(INPUT_POST, 'password'))
            ->setOmId(filter_input(INPUT_POST, 'om_id', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setName(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setEmail(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL))
            ->setNivel(filter_input(INPUT_POST, 'nivel', FILTER_SANITIZE_NUMBER_INT))
            ->setActive(filter_input(INPUT_POST, 'active', FILTER_SANITIZE_NUMBER_INT))
            ->setIp($_SERVER["REMOTE_ADDR"]);
        return $this;
    }

    private function setId($value = null)
    {
        if ($value) {
            $this->id = $value;
            return $this;
        }

        $value = filter_input(INPUT_POST, 'id');
        $this->id = $value ?: $this->getTime();
        return $this;
    }

    public function getResultadoPaginator()
    {
        return $this->resultadoPaginator;
    }

    public function getNavePaginator()
    {
        return $this->navPaginator;
    }

    // Validação
    private function validaId()
    {
        $value = v::int()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaUsername()
    {
        $value = v::string()->notEmpty()->validate($this->getUsername());
        if (!$value) {
            msg::showMsg('O campo Login deve ser preenchido corretamente.'
                . '<script>focusOn("username");</script>', 'danger');
        }

        $this->criptoVar('username', $this->getUsername());

        return $this;
    }

    private function validaPassword()
    {
        $value = v::string()->notEmpty()->length(8, null)->validate($this->getPassword());
        if (!$value) {
            msg::showMsg('O campo Senha deve ser preenchido corretamente'
                . ' com no <strong>mínimo 8 caracteres</strong>.'
                . '<script>focusOn("password");</script>', 'danger');
        }

        $this->criptoVar('password', $this->getPassword(), true);

        return $this;
    }

    private function validaName()
    {
        $value = v::string()->notEmpty()->validate($this->getName());
        if (!$value) {
            msg::showMsg('O campo Nome deve ser preenchido corretamente.'
                . '<script>focusOn("name");</script>', 'danger');
        }
        return $this;
    }

    private function validaOmId()
    {
        $value = v::int()->notEmpty()->validate($this->getOmId());
        if (!$value) {
            msg::showMsg('O campo OM deve ser preenchido corretamente.'
                . '<script>focusOn("om_id");</script>', 'danger');
        }
        return $this;
    }

    private function validaEmail()
    {
        $value = v::email()->notEmpty()->validate($this->getEmail());
        if (!$value) {
            msg::showMsg('O campo E-mail deve ser preenchido corretamente.'
                . '<script>focusOn("email");</script>', 'danger');
        }
        $this->criptoVar('email', $this->getEmail());
        return $this;
    }

    private function validaNivel()
    {
        $value = v::int()->notEmpty()->validate($this->getNivel());
        if (!$value) {
            msg::showMsg('O campo Nível de Acesso deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("nivel");</script>', 'danger');
        }
        return $this;
    }

    private function criptoVar($attribute, $value, $password = false)
    {
        $cripto = new Cripto;
        if (!$password) {
            $this->$attribute = $cripto->encode($value);
        } else {
            $this->$attribute = $cripto->passHash($value);
        }
        return $this;
    }
}
