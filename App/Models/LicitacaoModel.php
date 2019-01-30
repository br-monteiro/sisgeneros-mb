<?php
/**
 * @Model Licitacao
 */
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;

class LicitacaoModel extends CRUD
{

    protected $entidade = 'licitacao';
    protected $id;
    protected $uasg;
    protected $numero;
    protected $nomeUasg;
    protected $validade;
    protected $idLista;
    private $resultadoPaginator;
    private $navPaginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina, $dateLimit = null)
    {
        $dados = [
            'entidade' => $this->entidade,
            'pagina' => $pagina,
            'maxResult' => 20,
            'orderBy' => 'criacao DESC'
            //'where' => 'nome LIKE ? ORDER BY nome',
            //'bindValue' => [0 => '%MONTEIRO%']
        ];

        if ($dateLimit) {
            $dados['where'] = 'validade >= ?';
            $dados['bindValue'] = [0 => $dateLimit];
        }

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

    public function listaPorFornecedor($idLita)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                DISTINCT licitacao.numero AS numero,
                    licitacao.id_lista,
                    licitacao.uasg,
                    licitacao.nome_uasg,
                    fornecedor.nome AS nome,
                    fornecedor.id as fornecedor_id
            FROM licitacao
            INNER JOIN licitacao_item AS item
                ON item.id_lista = licitacao.id_lista AND item.active = 1
            INNER JOIN fornecedor
                ON fornecedor.id = item.id_fornecedor
            WHERE licitacao.id_lista = ?
            ORDER BY fornecedor.nome");
        $stmt->execute([$idLita]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function novoRegistro()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'numero' => $this->getNumero(),
            'uasg' => $this->getUasg(),
            'nome_uasg' => $this->getNomeUasg(),
            'validade' => $this->getValidade(),
            'id_lista' => $this->getIdLista(),
            'criacao' => time()
        ];

        if (parent::novo($dados)) {
            msg::showMsg('Licitação Registrada com Sucesso. '
                . "<a href='" . cfg::DEFAULT_URI . "item/novo/idlista/" . $this->getIdLista() . "' class='btn btn-info'>"
                . "<i class='fa fa-plus-circle'></i> Add Item</a>"
                . '<script>resetForm();</script>', 'success');
        }
    }

    public function editarRegistro()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'numero' => $this->getNumero(),
            'uasg' => $this->getUasg(),
            'nome_uasg' => $this->getNomeUasg(),
            'validade' => $this->getValidade()
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'licitacao/ver/');
        }
    }

    private function evitarDuplicidade()
    {
        /// Evita a duplicidade de registros
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND numero = ? AND uasg = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getNumero());
        $stmt->bindValue(3, $this->getUasg());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este Número de Licitação'
                . 'para a UASG nº <strong>' . $this->getUasg() . '</strong>'
                . '<script>focusOn("numero")</script>', 'warning');
        }
    }

    private function validaAll()
    {
        // Seta todos os valores
        $this->setId()
            ->setNumero(filter_input(INPUT_POST, 'numero', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUasg(filter_input(INPUT_POST, 'uasg', FILTER_VALIDATE_INT))
            ->setNomeUasg(filter_input(INPUT_POST, 'nome_uasg', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setValidade(filter_input(INPUT_POST, 'validade', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setIdLista();

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaNumero()
            ->validaUasg()
            ->validaNomeUasg()
            ->validaValidade()
            ->validaIdLista();
    }

    /// Seters
    private function setId()
    {
        $value = filter_input(INPUT_POST, 'id');
        $this->id = $value ?: time();
        return $this;
    }

    private function setIdLista()
    {
        $value = filter_input(INPUT_POST, 'id_lista');
        $this->idLista = $value ?: time();
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

    private function validaNumero()
    {
        $value = v::stringType()->notEmpty()->noWhitespace()->length(10, 10)->validate($this->getNumero());
        if (!$value) {
            msg::showMsg('O campo Numero deve ser preenchido corretamente'
                . ' com <strong>10 caracteres obrigatoriamente</strong>.'
                . '<script>focusOn("numero");</script>', 'danger');
        }
        return $this;
    }

    private function validaUasg()
    {
        $value = v::intVal()->notEmpty()->min(6, 6)->validate($this->getUasg());
        if (!$value) {
            msg::showMsg('O campo UASG deve ser um número inteiro válido '
                . '<strong>com 6 caracteres</strong>.'
                . '<script>focusOn("uasg");</script>', 'danger');
        }
        return $this;
    }

    private function validaNomeUasg()
    {
        $value = v::stringType()->notEmpty()->length(1, 50)->validate($this->getNomeUasg());
        if (!$value) {
            msg::showMsg('O campo Nome da Uasg deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("nome_uasg");</script>', 'danger');
        }
        return $this;
    }

    private function validaValidade()
    {
        $validade = explode('/', $this->validade);
        $validade = $validade[2] . '-' . $validade[1] . '-' . $validade[0];
        $value = v::date()->validate($validade);
        if (!$value) {
            msg::showMsg('O campo Validade deve ser preenchido corretamente.'
                . '<script>focusOn("validade");</script>', 'danger');
        }
        $this->validade = strtotime($validade) + 79199; // fix the validate request
        return $this;
    }
}
