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
        $date = $values["date"];
        $recipes = $this->buildRecipes($values["data"]);

        foreach ($recipes as $value) {
            $dados = [
                'meals_id' => $value['mealsId'],
                'menus_id' => $menusId,
                'recipes_patterns_id' => $value['recipesPatternsId'],
                'name' => $value['name'],
                'quantity_people' => $value['quantity'],
                'date' => date("Y-m-d", strtotime($date))
            ];
            if (parent::novo($dados)) {
                $recipeId = $this->pdo->lastInsertId();
                if ($recipeId) {
                    (new RecipesItemsModel())->novoRegistro($value['items'], $recipeId);
                }
            }
        }
    }

    private function buildRecipes(array $values) : array
    {
        $result = [];

        if (isset($values) && is_array($values)) {
            foreach ($values as $value) {
                $recipes = $value["recipes"];
                $result[] = [
                    'mealsId' => $recipes['meals']['id'],
                    'recipesPatternsId' => $recipes['recipesPatternsId'],
                    'name' => trim($recipes['name']),
                    'quantity' => $recipes['quantity'],
                    'items' => $this->buildItemsRecipes($recipes['items'])
                ];
            }
        }

        return $result;
    }

    private function buildItemsRecipes(array $values): array
    {
        $result = [];
        if (isset($values) && is_array($values)) {
            foreach($values as $value) {
                $result[] = [
                    "biddingsItems" => $value['biddings_items_id'],
                    "name" => $value['name'],
                    "suggestedQuantity" => $value['quantity'],
                    "quantity" => $value['quantity']
                ];
            }
        }
        return $result;
    }
}
