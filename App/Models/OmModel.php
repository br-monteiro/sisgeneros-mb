<?php
/**
 * @Model Om
 */
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;

class OmModel extends CRUD
{

    protected $entidade = 'om';
    protected $id;
    protected $nome;
    protected $uasg;
    protected $indicativoNaval;
    protected $time;
    protected $resultadoPaginator;
    protected $navPaginator;

    /*
     * Método uaso para retornar todos os dados da tabela.
     */

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina)
    {
        $dados = [
            'entidade' => $this->entidade,
            'pagina' => $pagina,
            'maxResult' => 10,
            'orderBy' => 'nome ASC'
            //'where' => 'nome LIKE ? ORDER BY nome',
            //'bindValue' => [0 => '%MONTEIRO%']
        ];

        $paginator = new Paginator($dados);
        $this->resultadoPaginator = $paginator->getResultado();
        $this->navPaginator = $paginator->getNaveBtn();
    }

    public function getResultadoPaginator()
    {
        return $this->resultadoPaginator;
    }

    public function getNavePaginator()
    {
        return $this->navPaginator;
    }

    public function novo()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'nome' => $this->getNome(),
            'uasg' => $this->getUasg(),
            'indicativo_naval' => $this->getIndicativoNaval(),
            'agente_fiscal' => $this->getAgenteFiscal(),
            'agente_fiscal_posto' => $this->getAgenteFiscalPosto(),
            'gestor_municiamento' => $this->getGestorMuniciamento(),
            'gestor_municiamento_posto' => $this->getGestorMuniciamentoPosto(),
            'fiel_municiamento' => $this->getFielMuniciamento(),
            'fiel_municiamento_posto' => $this->getFielMuniciamentoPosto(),
            'created_at' => $this->getTime(),
            'updated_at' => $this->getTime()
        ];
        if (parent::novo($dados)) {
            msg::showMsg('111', 'success');
        }
    }

    public function editar()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'nome' => $this->getNome(),
            'uasg' => $this->getUasg(),
            'indicativo_naval' => $this->getIndicativoNaval(),
            'agente_fiscal' => $this->getAgenteFiscal(),
            'agente_fiscal_posto' => $this->getAgenteFiscalPosto(),
            'gestor_municiamento' => $this->getGestorMuniciamento(),
            'gestor_municiamento_posto' => $this->getGestorMuniciamentoPosto(),
            'fiel_municiamento' => $this->getFielMuniciamento(),
            'fiel_municiamento_posto' => $this->getFielMuniciamentoPosto(),
            'updated_at' => $this->getTime()
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function remover($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . APPDIR . 'om/ver/');
        }
    }

    private function evitarDuplicidade()
    {
        /// Evita a duplicidade de registros
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND nome = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getNome());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este Nome.<script>focusOn("nome")</script>', 'warning');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND uasg = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getUasg());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este número de UASG.<script>focusOn("uasg")</script>', 'warning');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND indicativo_naval = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getIndicativoNaval());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este Indicativo Naval.<script>focusOn("indicativo_naval")</script>', 'warning');
        }
    }

    private function validaAll()
    {
        // Seta todos os valores
        $this->setTime(time())
            ->setId()
            ->setUasg(filter_input(INPUT_POST, 'uasg', FILTER_VALIDATE_INT))
            ->setNome(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setIndicativoNaval(filter_input(INPUT_POST, 'indicativo_naval'))
            ->setAgenteFiscal(filter_input(INPUT_POST, 'agente_fiscal'))
            ->setAgenteFiscalPosto(filter_input(INPUT_POST, 'agente_fiscal_posto'))
            ->setGestorMuniciamento(filter_input(INPUT_POST, 'gestor_municiamento'))
            ->setGestorMuniciamentoPosto(filter_input(INPUT_POST, 'gestor_municiamento_posto'))
            ->setFielMuniciamento(filter_input(INPUT_POST, 'fiel_municiamento'))
            ->setFielMuniciamentoPosto(filter_input(INPUT_POST, 'fiel_municiamento_posto'));

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaUasg()
            ->validaNome()
            ->validaIndicativoNaval()
            ->validaAgenteFiscal()
            ->validaAgenteFiscalPosto()
            ->validaGestorMuniciamento()
            ->validaGestorMuniciamentoPosto()
            ->validaFielMuniciamento()
            ->validaFielMuniciamentoPosto();
    }

    private function setId()
    {
        $value = filter_input(INPUT_POST, 'id');
        $this->id = $value ?: time();
        return $this;
    }

    private function validaId()
    {
        $value = v::int()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaNome()
    {
        $value = v::string()->notEmpty()->length(1, 60)->validate($this->getNome());
        if (!$value) {
            msg::showMsg('O campo Nome deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("nome");</script>', 'danger');
        }
        return $this;
    }

    private function validaUasg()
    {
        $value = v::int()->notEmpty()->min(6, 6)->validate($this->getUasg());
        if (!$value) {
            msg::showMsg('O campo UASG deve ser um número inteiro válido '
                . '<strong>com 6 caracteres</strong>.'
                . '<script>focusOn("uasg");</script>', 'danger');
        }
        return $this;
    }

    private function validaIndicativoNaval()
    {
        $value = v::string()->notEmpty()->length(6, 6)->validate($this->getIndicativoNaval());
        if (!$value) {
            msg::showMsg('O campo Indicativo Naval deve ser preenchido'
                . 'corretamente <strong>com 6 caracteres</strong>.'
                . '<script>focusOn("indicativo_naval");</script>', 'danger');
        }
        return $this;
    }

    private function validaAgenteFiscal()
    {
        $value = v::string()->notEmpty()->length(3, 100)->validate($this->getAgenteFiscal());
        if (!$value) {
            msg::showMsg('O campo Agente Fiscal deve ser preenchido'
                . 'corretamente <strong>com no mínimo 3 e máximo 100 caracteres</strong>.'
                . '<script>focusOn("agente_fiscal");</script>', 'danger');
        }
        return $this;
    }

    private function validaAgenteFiscalPosto()
    {
        $value = v::string()->notEmpty()->length(10, 40)->validate($this->getAgenteFiscalPosto());
        if (!$value) {
            msg::showMsg('O campo Agente Fiscal Posto deve ser preenchido'
                . 'corretamente <strong>com no mínimo 10 e máximo 20 caracteres</strong>.'
                . '<script>focusOn("agente_fiscal_posto");</script>', 'danger');
        }
        return $this;
    }

    private function validaGestorMuniciamento()
    {
        $value = v::string()->notEmpty()->length(3, 100)->validate($this->getGestorMuniciamento());
        if (!$value) {
            msg::showMsg('O campo Gestor Municiamento deve ser preenchido'
                . 'corretamente <strong>com no mínimo 3 e máximo 100 caracteres</strong>.'
                . '<script>focusOn("gestor_municiamento");</script>', 'danger');
        }
        return $this;
    }

    private function validaGestorMuniciamentoPosto()
    {
        $value = v::string()->notEmpty()->length(10, 40)->validate($this->getGestorMuniciamentoPosto());
        if (!$value) {
            msg::showMsg('O campo Gestor Municiamento Posto deve ser preenchido'
                . 'corretamente <strong>com no mínimo 10 e máximo 20 caracteres</strong>.'
                . '<script>focusOn("gestor_municiamento_posto");</script>', 'danger');
        }
        return $this;
    }

    private function validaFielMuniciamento()
    {
        $value = v::string()->notEmpty()->length(3, 100)->validate($this->getFielMuniciamento());
        if (!$value) {
            msg::showMsg('O campo Fiel Municiamento deve ser preenchido'
                . 'corretamente <strong>com no mínimo 3 e máximo 100 caracteres</strong>.'
                . '<script>focusOn("fiel_municiamento");</script>', 'danger');
        }
        return $this;
    }

    private function validaFielMuniciamentoPosto()
    {
        $value = v::string()->notEmpty()->length(10, 40)->validate($this->getFielMuniciamentoPosto());
        if (!$value) {
            msg::showMsg('O campo Fiel Municiamento Posto deve ser preenchido'
                . 'corretamente <strong>com no mínimo 10 e máximo 20 caracteres</strong>.'
                . '<script>focusOn("fiel_municiamento_posto");</script>', 'danger');
        }
        return $this;
    }
}