<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;


class RecipesItemsModel extends CRUD
{
    protected $entidade = 'recipes_items';

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

    public function novoRegistro($dados, $recipeId)
    {
        foreach ($dados as $value) {
            parent::novo([
                "recipes_id" => $recipeId,
                "biddings_items_id" => $value['biddingsItems'],
                "name" => $value['name'],
                "suggested_quantity" => $value['suggestedQuantity'],
                "quantity" => $value['quantity']
            ]);
        }
    }

    public function findByRecipe($recipesId)
    {
        $query = ""
            . " SELECT A.id, A.name AS item, A.quantity, B.name AS biddings, C.name AS suppliers "
            . " FROM {$this->entidade} AS A "
            . " LEFT JOIN biddings_items AS B "
            . "     ON B.id = A.biddings_items_id "
            . " LEFT JOIN suppliers AS C "
            . "     ON C.id = B.suppliers_id "
            . " WHERE A.recipes_id = :recipesId ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':recipesId' => $recipesId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function editarRegistro()
    {
        $this->validaAll();

        $dados = [
            'quantity' => $this->getQuantity()
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    private function validaAll()
    {
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setQuantity(filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT));

        // Inicia a Validação dos dados
        $this->validaId();
        $this->validaQuantity();
    }

    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaQuantity()
    {
        $value = v::floatVal()->validate($this->getQuantity());
        if (!$value) {
            msg::showMsg('O campo de quantidade deve ser preenchido corretamente', 'danger');
        }
        return $this;
    }
}
