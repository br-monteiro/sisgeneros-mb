<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;


class RecipesModel extends CRUD
{
    protected $entidade = 'recipes';

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

    public function novoRegistro($values, $menusId)
    {
        foreach ($values as $value) {
            d($values);
            $dados = [
                'meals_id' => $value['meals']['id'],
                'menus_id' => $menusId,
                'recipes_patterns_id' => $value['recipes']['id'],
                'name' => $value['name'],
                'quantity' => $value['quantity'],
                'date' => date("Y-m-d", strtotime($value['date']))
            ];
            d($dados);
            if (parent::novo($dados)) {
                $recipeId = $this->pdo->lastInsertId();

                if ($recipeId) {
                    $items = $this->buildItemsRecipes($values['recipes']['items']);
                    (new RecipesItemsModel())->novoRegistro($items, $recipeId);
                }
            }
        }
    }

    private function buildItemsRecipes(array $values): array
    {
        $result = [];

        if (isset($values) && is_array($values)) {
            foreach ($values as $value) {
                $result[] = [
                    "biddings_items" => $value['id'],
                    "name" => $value['name'],
                    "suggested_quantity" => $value['quantity'],
                    "quantity" => $value['quantity']
                ];
            }
        }

        return $result;
    }
}
