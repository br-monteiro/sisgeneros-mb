<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Helpers\Utils;

class ItemModel extends CRUD
{

    protected $entidade = 'biddings_items';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina, $idlista)
    {
        $dados = [
            'entidade' => 'biddings_items INNER JOIN `suppliers` ON `biddings_items`.`suppliers_id` = `suppliers`.`id`',
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => '`biddings_items`.`number` ASC',
            'where' => '`biddings_items`.`biddings_id` = ?',
            'bindValue' => [$idlista],
            'select' => '`biddings_items`.*, `suppliers`.`name` AS supplier'
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
            'biddings_id' => $this->getBiddingsId(),
            'suppliers_id' => $this->getSuppliersId(),
            'ingredients_id' => $this->getIngredientsId(),
            'number' => $this->getNumber(),
            'name' => $this->getName(),
            'uf' => $this->getUf(),
            'quantity' => $this->getQuantity(),
            'value' => $this->getValue(),
            'active' => $this->getActive()
        ];

        if (parent::novo($dados)) {
            msg::showMsg('Sucesso ao executar operação.'
                . '<script>'
                . 'resetFormOnDemand(["number", "name", "uf", "quantity", "value"]);'
                . 'focusOn("number");'
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
            'biddings_id' => $this->getBiddingsId(),
            'suppliers_id' => $this->getSuppliersId(),
            'ingredients_id' => $this->getIngredientsId(),
            'number' => $this->getNumber(),
            'name' => $this->getName(),
            'uf' => $this->getUf(),
            'quantity' => $this->getQuantity(),
            'value' => $this->getValue(),
            'active' => $this->getActive()
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id, $idlista)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'item/listar/idlista/' . $idlista);
        }
    }

    private function evitarDuplicidade()
    {
        /// Evita a duplicidade de registros
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND biddings_id = ? AND number = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getBiddingsId());
        $stmt->bindValue(3, $this->getNumber());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um Item com este Número para esta Licitação.'
                . '<script>focusOn("number")</script>', 'warning');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND biddings_id = ? AND name = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getBiddingsId());
        $stmt->bindValue(3, $this->getName());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um Item com este Nome para esta Licitação.'
                . '<script>focusOn("name")</script>', 'warning');
        }
    }

    public function findByIdlista($idlista, $idFornecedor)
    {
        $stmt = $this->pdo->prepare(
            "SELECT `biddings_items`.*, `suppliers`.`cnpj`, `suppliers`.`name` AS supplier,
                suppliers.id as supplier_id
            FROM `biddings_items` 
            INNER JOIN `suppliers` ON `biddings_items`.`suppliers_id` = `suppliers`.`id` 
            WHERE `biddings_items`.`biddings_id` = ? AND suppliers.id = ? AND `biddings_items`.`active` = 'yes'
            ORDER BY `biddings_items`.`number` ASC");
        $stmt->execute([$idlista, $idFornecedor]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function validaAll()
    {
        // Seta todos os valuees
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setBiddingsId(filter_input(INPUT_POST, 'biddings_id'))
            ->setSuppliersId(filter_input(INPUT_POST, 'suppliers_id'))
            ->setActive(filter_input(INPUT_POST, 'active') == 'on' ? 'yes' : 'no')
            ->setNumber(filter_input(INPUT_POST, 'number', FILTER_VALIDATE_INT))
            ->setIngredientsId(filter_input(INPUT_POST, 'ingredients_id', FILTER_VALIDATE_INT))
            ->setName(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUf(filter_input(INPUT_POST, 'uf', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setValue(filter_input(INPUT_POST, 'value', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setQuantity(filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT));

        $this->setValue(Utils::moneyToFloat($this->getValue()));
        // Inicia a Validação dos dados
        $this->validaId()
            ->validaBiddingsId()
            ->validaSuppliersId()
            ->validaNumber()
            ->validaName()
            ->validaUf()
            ->validaQuantity();
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

    private function validaBiddingsId()
    {
        $value = v::intVal()->validate($this->getBiddingsId());
        if (!$value) {
            msg::showMsg('O campo ID LISTA deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaSuppliersId()
    {
        $value = v::intVal()->validate($this->getSuppliersId());
        if (!$value) {
            msg::showMsg('O campo ID DO FORNECEDOR deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaNumber()
    {
        $value = v::intVal()->notEmpty()->noWhitespace()->validate($this->getNumber());
        if (!$value) {
            msg::showMsg('O campo Número deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("number");</script>', 'danger');
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

    private function validaUf()
    {
        $value = v::stringType()->notEmpty()->validate($this->getUf());
        if (!$value || !Utils::checkLength($this->getUf(), 1, 4)) {
            msg::showMsg('O campo Nome deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("uf");</script>', 'danger');
        }
        return $this;
    }

    private function validaQuantity()
    {
        $value = v::intVal()->notEmpty()->noWhitespace()->validate($this->getQuantity());
        if (!$value) {
            msg::showMsg('O campo quantity deve ser preenchido corretamente.'
                . '<script>focusOn("quantity");</script>', 'danger');
        }
        return $this;
    }
}
