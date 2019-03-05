<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;

class FornecedorModel extends CRUD
{

    protected $entidade = 'suppliers';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina)
    {
        $dados = [
            'entidade' => $this->entidade,
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => 'name ASC'
        ];

        $this->paginator = new Paginator($dados);
    }

    public function getResultadoPaginator()
    {
        return $this->paginator->getResultado();
    }

    public function getNavePaginator()
    {
        return $this->paginator->getNaveBtn();
    }

    public function novoRegistro()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'name' => $this->getName(),
            'cnpj' => $this->getCnpj(),
            'details' => $this->getDetails()
        ];
        if (parent::novo($dados)) {
            msg::showMsg('111', 'success');
        }
    }

    public function editarRegistro()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'name' => $this->getName(),
            'cnpj' => $this->getCnpj(),
            'details' => $this->getDetails()
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'suppliers/ver/');
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
            msg::showMsg('Já existe um registro com este Name.<script>focusOn("name")</script>', 'warning');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND cnpj = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getCnpj());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este número de CNPJ.<script>focusOn("cnpj")</script>', 'warning');
        }
    }

    private function validaAll()
    {
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setCnpj(filter_input(INPUT_POST, 'cnpj', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setName(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDetails(filter_input(INPUT_POST, 'details', FILTER_SANITIZE_SPECIAL_CHARS));

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaName()
            ->validaCnpj()
            ->validaDetails();
    }

    // Validação
    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaName()
    {
        $value = v::stringType()->notEmpty()->validate($this->getName());
        if (!$value) {
            msg::showMsg('O campo Nome deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("name");</script>', 'danger');
        }
        return $this;
    }

    private function validaCnpj()
    {
        $value = v::cnpj()->validate($this->getCnpj());
        if (!$value) {
            msg::showMsg('O campo CNPJ deve ser preenchido com um registro válido.'
                . '<script>focusOn("cnpj");</script>', 'danger');
        }
        return $this;
    }

    private function validaDetails()
    {
        $value = v::stringType()->validate($this->getDetails());
        if (!$value) {
            msg::showMsg('O campo Dados deve ser preenchido corretamente.'
                . '<script>focusOn("datails");</script>', 'danger');
        }
        return $this;
    }
}
