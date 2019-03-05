<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;

class LicitacaoModel extends CRUD
{

    protected $entidade = 'biddings';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

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
            'orderBy' => 'created_at DESC'
        ];

        if ($dateLimit) {
            $dados['where'] = 'validate >= ?';
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
                DISTINCT biddings.number,
                    biddings.uasg,
                    biddings.description,
                    biddings.uasg_name,
                    suppliers.name,
                    suppliers.id as suppliers_id
            FROM biddings
            INNER JOIN biddings_items AS item
                ON item.biddings_id = biddings.id AND item.active = 'yes'
            INNER JOIN suppliers
                ON suppliers.id = item.suppliers_id
            WHERE biddings.id = ?
            ORDER BY suppliers.name");
        $stmt->execute([$idLita]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listaItemFornecedor($search)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                DISTINCT biddings.number,
                    biddings.id,
                    biddings.uasg,
                    biddings.uasg_name,
                    item.name produtoNome,
                    suppliers.name AS name,
                    suppliers.id as suppliers_id
            FROM biddings
            INNER JOIN biddings_item AS item
                ON item.biddings_id = biddings.id AND item.active = 'yes'
            INNER JOIN suppliers
                ON suppliers.id = item.suppliers_id
            WHERE item.name LIKE :search AND biddings.validate >= '" . date('Y-m-d') . "'
            ORDER BY item.name");
        $stmt->execute([':search' => "%{$search}%"]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function novoRegistro()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'number' => $this->getNumber(),
            'uasg' => $this->getUasg(),
            'description' => $this->getDescription(),
            'uasg_name' => $this->getUsasgName(),
            'validate' => $this->getValidate(),
            'created_at' => date('Y-m-d')
        ];

        if (parent::novo($dados)) {
            msg::showMsg('Licitação Registrada com Sucesso. '
                . "<a href='" . cfg::DEFAULT_URI . "item/novo/idlista/" . $this->getIdLista() . "' class='btn btn-info'>"
                . "<i class='fa fa-plus-circle'></i> Adicionar Item</a>"
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
            'number' => $this->getnumber(),
            'uasg' => $this->getUasg(),
            'description' => $this->getDescription(),
            'uasg_name' => $this->getUsasgName(),
            'validate' => $this->getValidate()
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'biddings/ver/');
        }
    }

    private function evitarDuplicidade()
    {
        /// Evita a duplicidade de registros
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND number = ? AND uasg = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getnumber());
        $stmt->bindValue(3, $this->getUasg());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este Número de Licitação'
                . 'para a UASG nº <strong>' . $this->getUasg() . '</strong>'
                . '<script>focusOn("number")</script>', 'warning');
        }
    }

    private function validaAll()
    {
        // Seta todos os valores
        $this->setId()
            ->setnumber(filter_input(INPUT_POST, 'number', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUasg(filter_input(INPUT_POST, 'uasg', FILTER_VALIDATE_INT))
            ->setdescription(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setNomeUasg(filter_input(INPUT_POST, 'uasg_name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setValidate(filter_input(INPUT_POST, 'validate', FILTER_SANITIZE_SPECIAL_CHARS));

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaNumber()
            ->validaUasg()
            ->validaDescription()
            ->validaUasgName()
            ->validaValidate();
    }

    /// Seters
    private function setId()
    {
        $value = filter_input(INPUT_POST, 'id');
        $this->setId($value ?? time());
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

    private function validanumber()
    {
        $value = v::stringType()->notEmpty()->noWhitespace()->length(10, 10)->validate($this->getNumber());
        if (!$value) {
            msg::showMsg('O campo number deve ser preenchido corretamente'
                . ' com <strong>10 caracteres obrigatoriamente</strong>.'
                . '<script>focusOn("number");</script>', 'danger');
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

    private function validaUasgName()
    {
        $value = v::stringType()->notEmpty()->length(1, 50)->validate($this->getNomeUasg());
        if (!$value) {
            msg::showMsg('O campo Nome da Uasg deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("uasg_name");</script>', 'danger');
        }
        return $this;
    }

    private function validadescription()
    {
        $value = v::stringType()->notEmpty()->length(1, 30)->validate($this->getDescription());
        if (!$value) {
            msg::showMsg('O campo Descrição da licitação deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("description");</script>', 'danger');
        }
        return $this;
    }

    private function validaValidate()
    {
        $validate = explode('/', $this->validate);
        $validate = $validate[2] . '-' . $validate[1] . '-' . $validate[0];
        $value = v::date()->validate($validate);
        if (!$value) {
            msg::showMsg('O campo Validade deve ser preenchido corretamente.'
                . '<script>focusOn("validate");</script>', 'danger');
        }
        $this->setValidate($validate);
        return $this;
    }
}
