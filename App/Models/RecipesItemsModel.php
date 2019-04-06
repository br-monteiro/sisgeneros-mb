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
}
