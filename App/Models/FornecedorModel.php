<?php
/**
 * @Model Fornecedor
 */
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;

class FornecedorModel extends CRUD
{

    protected $entidade = 'fornecedor';
    protected $id;
    protected $nome;
    protected $cnpj;
    protected $dados;
    private $resultadoPaginator;
    private $navPaginator;

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
            'cnpj' => $this->getCnpj(),
            'dados' => $this->getDados()
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
            'cnpj' => $this->getCnpj(),
            'dados' => $this->getDados()
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function remover($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . APPDIR . 'fornecedor/ver/');
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
        $this->setId()
            ->setCnpj(filter_input(INPUT_POST, 'cnpj', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setNome(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDados(filter_input(INPUT_POST, 'dados', FILTER_SANITIZE_SPECIAL_CHARS));

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaNome()
            ->validaCnpj()
            ->validaDados();
    }

    /// Seters
    private function setId()
    {
        $value = filter_input(INPUT_POST, 'id');
        $this->id = $value ?: time();
        return $this;
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

    private function validaNome()
    {
        $value = v::stringType()->notEmpty()->validate($this->getNome());
        if (!$value) {
            msg::showMsg('O campo Nome deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("nome");</script>', 'danger');
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

    private function validaDados()
    {
        $value = v::stringType()->validate($this->getDados());
        if (!$value) {
            msg::showMsg('O campo Dados deve ser preenchido corretamente.'
                . '<script>focusOn("dados");</script>', 'danger');
        }
        return $this;
    }
}
