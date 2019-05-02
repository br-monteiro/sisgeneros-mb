<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;

class RecipesPatternsModel extends CRUD
{
    protected $entidade = 'recipes_patterns';

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

    public function novoRegistro()
    {
        // Valida dados
        $this->validaAll();

        $dados = [
            'name' => $this->getName(),
        ];

        if (parent::novo($dados)) {
            $lastId = $this->pdo->lastInsertId();

            $itens = new RecipesPatternsItemsModel();
            $itens->novoItens($this->getItemsList(), $lastId);

            msg::showMsg('111', 'success');
        }
    }

    public function editarRegistro()
    {
        // Valida dados
        $this->validaAll();

        $dados = [
            'name' => $this->getName(),
        ];

        if (parent::editar($dados, $this->getId())) {

            $itens = new RecipesPatternsItemsModel();
            $itens->editarRegistro($this->getItemsList(), $this->getId());

            msg::showMsg('001', 'success');
        }
    }

    private function buildItemsIngredients(array $values): array
    {
        $result = [];

        if (isset($values['ingredients_id']) && is_array($values['ingredients_id'])) {
            foreach ($values['ingredients_id'] as $index => $value) {
                $result[] = [
                    "id" => isset($values['recipesPatternsId'][$index]) ? $values['recipesPatternsId'][$index] : "",
                    "ingredients_id" => $value,
                    "quantity" => $values['quantity'][$index]
                ];
            }
        }

        return $result;
    }

    public function findRecipeItemsByRecipesId($id)
    {
        $query = ""
            . " SELECT "
            . " rpi.id, rpi.ingredients_id, ing.name, quantity "
            . " FROM recipes_patterns_items AS rpi"
            . " INNER JOIN ingredients AS ing "
            . "     ON ing.id = rpi.ingredients_id "
            . " WHERE rpi.recipes_patterns_id = :recipesId ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':recipesId' => $id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function removerRegistro($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'recipespatterns/ver/');
        }
    }

    private function validaAll()
    {
        $value = filter_input_array(INPUT_POST);
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setName(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setIngredientsId(filter_input(INPUT_POST, 'ingredients_id'))
            ->setQuantity(filter_input(INPUT_POST, 'quantity'));

        $this->setItemsList($this->buildItemsIngredients($value));

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaName();
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

    private function validaName()
    {
        $value = v::stringType()->notEmpty()->validate($this->getName());
        if (!$value) {
            msg::showMsg('O campo Nome deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("name");</script>', 'danger');
        }
        return $this;
    }
}
