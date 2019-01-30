<?php
/**
 * @Model Item
 */
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;

class ItemModel extends CRUD
{

    protected $entidade = 'licitacao_item';
    protected $id;
    protected $idLista;
    protected $idFornecedor;
    protected $numero;
    protected $nome;
    protected $uf;
    protected $quantidade;
    protected $valor;
    private $resultadoPaginator;
    private $navPaginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina, $idLista)
    {
        /*
         * SELECT `licitacao_item`.*, `fornecedor`.`nome` AS fornecedor 
         * FROM `licitacao_item` 
         * INNER JOIN `fornecedor` ON `licitacao_item`.`id_fornecedor` = `fornecedor`.`id` 
         * WHERE `licitacao_item`.`id_lista` = ? 
         * ORDER BY `licitacao_item`.`numero` ASC ;
         */
        $dados = [
            'entidade' => 'licitacao_item INNER JOIN `fornecedor` ON `licitacao_item`.`id_fornecedor` = `fornecedor`.`id`',
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => '`licitacao_item`.`numero` ASC',
            'where' => '`licitacao_item`.`id_lista` = ?',
            'bindValue' => [0 => $idLista],
            'select' => '`licitacao_item`.*, `fornecedor`.`nome` AS fornecedor'
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

    public function novoRegistro()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'id_lista' => $this->getIdLista(),
            'id_fornecedor' => $this->getIdFornecedor(),
            'numero' => $this->getNumero(),
            'nome' => $this->getNome(),
            'uf' => $this->getUf(),
            'quantidade' => $this->getQuantidade(),
            'valor' => $this->getValor(),
            'active' => $this->getHabilitacao()
        ];

        if (parent::novo($dados)) {
            msg::showMsg('Sucesso ao executar operação.'
                . '<script>'
                . 'resetFormOnDemand(["numero", "nome", "uf", "quantidade", "valor"]);'
                . 'focusOn("numero");'
                . '</script>', 'success');
        }
    }

    public function editarRegistro()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'id_lista' => $this->getIdLista(),
            'id_fornecedor' => $this->getIdFornecedor(),
            'numero' => $this->getNumero(),
            'nome' => $this->getNome(),
            'uf' => $this->getUf(),
            'quantidade' => $this->getQuantidade(),
            'valor' => $this->getValor(),
            'active' => $this->getHabilitacao()
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id, $idLista)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'item/listar/idlista/' . $idLista);
        }
    }

    private function evitarDuplicidade()
    {
        /// Evita a duplicidade de registros
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND id_lista = ? AND numero = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getIdLista());
        $stmt->bindValue(3, $this->getNumero());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um Item com este Número para esta Licitação.'
                . '<script>focusOn("numero")</script>', 'warning');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND id_lista = ? AND nome = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getIdLista());
        $stmt->bindValue(3, $this->getNome());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um Item com este Nome para esta Licitação.'
                . '<script>focusOn("nome")</script>', 'warning');
        }
    }

    public function findByIdLista($idLista, $idFornecedor)
    {
        $stmt = $this->pdo->prepare(
            "SELECT `licitacao_item`.*, `fornecedor`.`cnpj`, `fornecedor`.`nome` AS fornecedor,
                fornecedor.id as fornecedor_id
            FROM `licitacao_item` 
            INNER JOIN `fornecedor` ON `licitacao_item`.`id_fornecedor` = `fornecedor`.`id` 
            WHERE `licitacao_item`.`id_lista` = ? AND fornecedor.id = ? AND `licitacao_item`.`active` = 1
            ORDER BY `licitacao_item`.`numero` ASC");
        $stmt->execute([$idLista, $idFornecedor]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function validaAll()
    {
        // Seta todos os valores
        $this->setId()
            ->setIdLista()
            ->setIdFornecedor(filter_input(INPUT_POST, 'id_fornecedor'))
            ->setHabilitacao(filter_input(INPUT_POST, 'habilitacao'))
            ->setNumero(filter_input(INPUT_POST, 'numero', FILTER_VALIDATE_INT))
            ->setNome(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUf(filter_input(INPUT_POST, 'uf', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setValor(filter_input(INPUT_POST, 'valor', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setQuantidade(filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT));

        $valor = $this->getvalor();
        $valor = str_replace(".", "", $valor);
        $valor = str_replace(",", ".", $valor);
        $valor = $valor ? number_format($valor, 2) : '0.0';
        $this->setValor($valor);
        // Inicia a Validação dos dados
        $this->validaId()
            ->validaIdLista()
            ->validaIdFornecedor()
            ->validaNumero()
            ->validaNome()
            ->validaUf()
            ->validaQuantidade()
            ->validaHabilitacao();
    }

    private function setId()
    {
        $value = filter_input(INPUT_POST, 'id');
        $this->id = $value ?: time();
        return $this;
    }

    private function setIdLista()
    {
        $value = filter_input(INPUT_POST, 'id_lista');
        $this->idLista = !empty($value) ? $value : time();
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

    private function validaIdLista()
    {
        $value = v::intVal()->validate($this->getIdLista());
        if (!$value) {
            msg::showMsg('O campo ID LISTA deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaIdFornecedor()
    {
        $value = v::intVal()->validate($this->getIdFornecedor());
        if (!$value) {
            msg::showMsg('O campo ID DO FORNECEDOR deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaNumero()
    {
        $value = v::intVal()->notEmpty()->noWhitespace()->validate($this->getNumero());
        if (!$value) {
            msg::showMsg('O campo Numero deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("numero");</script>', 'danger');
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

    private function validaUf()
    {
        $value = v::stringType()->notEmpty()->length(1, 4)->validate($this->getUf());
        if (!$value) {
            msg::showMsg('O campo Nome deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("uf");</script>', 'danger');
        }
        return $this;
    }

    private function validaQuantidade()
    {
        $value = v::intVal()->notEmpty()->noWhitespace()->validate($this->getQuantidade());
        if (!$value) {
            msg::showMsg('O campo Quantidade deve ser preenchido corretamente.'
                . '<script>focusOn("quantidade");</script>', 'danger');
        }
        return $this;
    }

    private function validaHabilitacao()
    {
        $value = $this->getHabilitacao() ? 1 : 0;
        $this->setHabilitacao($value);
    }
}
