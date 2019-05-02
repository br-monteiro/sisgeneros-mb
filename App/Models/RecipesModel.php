<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Helpers\Utils;
use App\Config\Configurations as cfg;

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
        $recipes = $this->buildRecipes($values["data"] ?? []);

        foreach ($recipes as $value) {
            $dados = [
                'meals_id' => $value['mealsId'],
                'menus_id' => $menusId,
                'recipes_patterns_id' => $value['recipesPatternsId'],
                'name' => $value['name'],
                'quantity_people' => $value['quantity'],
                'sort' => $value['sort'],
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

    private function buildRecipes($values)
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
                    'sort' => $recipes['sort'],
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

    public function findByRecipeByMenuId($menusId)
    {
        $query = ""
            . " SELECT "
            . " A.id, A.date, "
            . " B.name AS meals, C.name AS recipes, A.quantity_people "
            . " FROM {$this->entidade} AS A "
            . " INNER JOIN meals AS B "
            . "     ON B.id = A.meals_id "
            . " INNER JOIN recipes_patterns AS C "
            . "     ON C.id = A.recipes_patterns_id "
            . " WHERE A.menus_id = :menusId "
            . "ORDER BY A.date, B.sort";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':menusId' => $menusId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function editarRegistro()
    {
        $this->validaAll();

        $dados = [
            'meals_id' => $this->getMealsId(),
            'recipes_patterns_id' => $this->getRecipesPatternsId(),
            'quantity_people' => $this->getQuantity(),
            'date' => $this->getDate()
        ];
        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id, $menusId)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . "cardapio/detalhar/id/{$menusId}");
        }
    }

    private function validaAll()
    {
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setName(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setRecipesPatternsId(filter_input(INPUT_POST, 'recipes', FILTER_VALIDATE_INT))
            ->setMealsId(filter_input(INPUT_POST, 'mealsId', FILTER_VALIDATE_INT))
            ->setQuantity(filter_input(INPUT_POST, 'quantity_people', FILTER_VALIDATE_INT))
            ->setDate(filter_input(INPUT_POST, 'date', FILTER_SANITIZE_SPECIAL_CHARS));

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaDate();
    }

    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaDate()
    {
        $this->setDate($this->abstractDateValidate($this->getDate(), 'date', 'Data do cardápio'));
        return $this;
    }

    private function abstractDateValidate(string $value, string $fieldName, string $labelName)
    {
        $date = Utils::dateDatabaseFormate($value);
        if (!v::date()->validate($date)) {
            msg::showMsg('O campo ' . $labelName . ' deve ser preenchido corretamente.'
                . '<script>focusOn("' . $fieldName . '");</script>', 'danger');
        }
        return $date;
    }
}
