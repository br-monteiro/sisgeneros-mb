<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use HTR\Helpers\Session\Session;
use HTR\Helpers\Criptografia\Criptografia;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;

class AcessoModel extends CRUD
{

    protected $entidade = 'users';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    private $paginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina)
    {
        $innerJoin = " INNER JOIN oms ON users.oms_id = oms.id";
        $dados = [
            'entidade' => $this->entidade . $innerJoin,
            'pagina' => $pagina,
            'maxResult' => 10,
            'select' => 'users.*, oms.indicativo_naval'
        ];

        $this->paginator = new Paginator($dados);
    }

    public function novoRegistro()
    {
        $this->buildSetters()
            // Valida os Dados enviados através do formulário
            ->validaPassword()
            ->validaUsername()
            ->validaOmsId()
            ->validaName()
            ->validaEmail()
            ->validaLevel()
            // Verifica se há registro igual
            ->evitarDuplicidade();

        $dados = [
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'oms_id' => $this->getOmsId(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'level' => $this->getLevel(),
            'change_password' => 'yes',
            'active' => 'yes',
            'created_at' => self::currentDate(),
            'updated_at' => self::currentDate()
        ];

        if (parent::novo($dados)) {
            msg::showMsg('111', 'success');
        } else {
            msg::showMsg('000', 'danger');
        }
    }

    /**
     * Return the current date. By default the format is YYYY-MM-DD
     * @param string $format The format of output
     * @return string
     */
    private static function currentDate(string $format = 'Y-m-d'): string
    {
        return date($format);
    }

    public function editarRegistro()
    {
        // Seta automsaticamente os atributos necessários
        $this->buildSetters()
            // Valida os Dados enviados através do formulário
            ->validaId()
            ->validaUsername()
            ->validaOmsId()
            ->validaName()
            ->validaEmail()
            ->validaLevel()
            // Verifica se há registro igual
            ->evitarDuplicidade();

        $dados = [
            'id' => $this->getId(),
            'username' => $this->getUsername(),
            'oms_id' => $this->getOmsId(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'level' => $this->getLevel(),
            'active' => $this->getActive(),
            'updated_at' => self::currentDate()
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

        if ($user['level'] == 'ADMINISTRADOR' && ($user['id'] != $this->getId())) {
            // seta a troca de senha na próxima vez que o usuário logar
            $dados['change_password'] = 'yes';
        } else {
            // Para usuário com o nível diferente de 1-Administrador
            $this->setId($user['id']);
            $dados['active'] = $user['active'];
            $dados['level'] = $user['level'];
            $dados['oms_id'] = $user['oms_id'];
        }

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'acesso/ver/');
        }
    }

    public function findById($id)
    {
        $value = parent::findById($id);

        if ($value) {
            return $value;
        }

        msg::showMsg('Este registro não foi encontrado. Você será redirecionado em 5 segundos.'
            . '<meta http-equiv="refresh" content="0;URL=' . cfg::DEFAULT_URI . 'acesso" />', 'danger', false);
    }

    /**
     * Método usado para alterar a senha do usuário no primeiro acesso
     * @param int $id The user ID
     */
    public function mudarSenha($id)
    {
        $this->setTime()
            ->setPassword(filter_input(INPUT_POST, 'password'))
            ->validaPassword();

        $dados = [
            'password' => $this->getPassword(),
            'change_password' => 'no',
            'updated_at' => self::currentDate()
        ];

        if (parent::editar($dados, $id)) {
            msg::showMsg('A senha foi alterada com sucesso! '
                . 'Você será redirecionado para a página inicial em 5 segundos.'
                . '<meta http-equiv="refresh" content="5;URL=' . cfg::DEFAULT_URI . '" />'
                . '<script>setTimeout(function(){ window.location = "' . cfg::DEFAULT_URI . '"; }, 5000); </script>', 'success');
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
            msg::showMsg('Já existe um registro com este Nomse.'
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
            $cripto = new Criptografia;
            // consulta se existe um susário registrado com o USERNAME fornecido
            $result = $this->findByUsername($username);

            if (!$result) {
                msg::showMsg('<strong>Usuário inválido.</strong>'
                    . ' Verifique se digitou corretamente.'
                    . '<script>focusOn("username");</script>', 'warning');
            }

            if ($result['active'] === 'no') {
                msg::showMsg('<strong>Usuário Bloqueado!</strong><br>'
                    . ' Consulte o Admistrador do Sistema para mais informações.'
                    . cfg::ADMIN_CONTACT, 'danger');
            }

            if ($cripto->passVerify($password . cfg::STR_SALT, $result['password'])) {
                $this->registerSession($result);
                return; // just stop execution
            } else {
                msg::showMsg('<strong>Algo está errado...</strong>'
                    . ' Verifique se digitou corretamente seu login e senha.'
                    . '<script>focusOn("password");</script>', 'warning');
            }
        }
        msg::showMsg('Todos os campos são preenchimento obrigatório.', 'danger');
    }

    private function registerSession($dados)
    {
        $session = new Session();
        $session->startSession();
        $_SESSION['token'] = $session->getToken();
        $_SESSION['userId'] = $dados['id'];
        echo '<meta http-equiv="refresh" content="0;URL=' . cfg::DEFAULT_URI . '" />'
        . '<script>window.location = "' . cfg::DEFAULT_URI . '"; </script>';
        return true; // stop script
    }

    public function logout()
    {
        $session = new Session();
        return $session->stopSession();
    }

    private function buildSetters()
    {
        $this->setId()
            ->setUsername(filter_input(INPUT_POST, 'username'))
            ->setPassword(filter_input(INPUT_POST, 'password'))
            ->setOmsId(filter_input(INPUT_POST, 'oms_id', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setName(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setEmail(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL))
            ->setLevel(filter_input(INPUT_POST, 'level', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setActive(filter_input(INPUT_POST, 'active', FILTER_SANITIZE_NUMBER_INT));

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
        return $this->paginator->getResultado();
    }

    public function getNavePaginator()
    {
        return $this->paginator->getNaveBtn();
    }

    // Validação
    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaUsername()
    {
        $value = v::stringType()->notEmpty()->validate($this->getUsername());
        if (!$value) {
            msg::showMsg('O campo Login deve ser preenchido corretamente.'
                . '<script>focusOn("username");</script>', 'danger');
        }

        $this->criptoVar('username', $this->getUsername());

        return $this;
    }

    private function validaPassword()
    {
        $value = v::stringType()->notEmpty()->length(8, null)->validate($this->getPassword());
        if (!$value) {
            msg::showMsg('O campo Senha deve ser preenchido corretamente'
                . ' com no <strong>mínimo 8 caracteres</strong>.'
                . '<script>focusOn("password");</script>', 'danger');
        }

        $this->criptoVar('password', $this->getPassword() . cfg::STR_SALT, true);

        return $this;
    }

    private function validaName()
    {
        $value = v::stringType()->notEmpty()->validate($this->getName());
        if (!$value) {
            msg::showMsg('O campo Nomse deve ser preenchido corretamente.'
                . '<script>focusOn("name");</script>', 'danger');
        }
        return $this;
    }

    private function validaOmsId()
    {
        $value = v::intVal()->notEmpty()->validate($this->getOmsId());
        if (!$value) {
            msg::showMsg('O campo OM deve ser preenchido corretamente.'
                . '<script>focusOn("oms_id");</script>', 'danger');
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

    private function validaLevel()
    {
        $value = v::stringType()->notEmpty()->validate($this->getLevel());
        if (!$value) {
            msg::showMsg('O campo Nível de Acesso deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("level");</script>', 'danger');
        }
        return $this;
    }

    private function criptoVar($attribute, $value, $password = false)
    {
        $cripto = new Criptografia;
        if (!$password) {
            $this->$attribute = $value;
        } else {
            $this->$attribute = $cripto->passHash($value);
        }
        return $this;
    }
}
