<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;

class RecipesPatternsItemsModel extends CRUD
{
    protected $entidade = 'recipes_patterns_items';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function findByidRecipes($recipesPatternsId)
    {
        $query = ""
            . " SELECT "
            . " A.*, B.name ingredient "
            . " FROM {$this->entidade} AS A "
            . " INNER JOIN ingredients AS B "
            . "     ON B.id = A.ingredients_id "
            . " WHERE A.recipes_patterns_id = :recipesPatternsId ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':recipesPatternsId' => $recipesPatternsId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function novoItens($dados, $recipePatternsId)
    {
        foreach ($dados as $value) {
            parent::novo([
                "recipes_patterns_id" => $recipePatternsId,
                "ingredients_id" => $value['ingredients_id'],
                "quantity" => $value['quantity'],
            ]);
        }
    }

    public function editarRegistro($dados, $recipePatternsId)
    {
        foreach ($dados as $value) {
            if ($value['id'] > 0) {
                parent::editar([
                    "ingredients_id" => $value['ingredients_id'],
                    "quantity" => $value['quantity']
                ], $value['id']);
            } else {
                parent::novo([
                    "recipes_patterns_id" => $recipePatternsId,
                    "ingredients_id" => $value['ingredients_id'],
                    "quantity" => $value['quantity']
                ]);
            }
        }
    }

    public function removerRegistro($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'recipespatterns/ver/');
        }
    }

    private function setAll($dados)
    {
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setQuantity(filter_input(INPUT_POST, 'quantity'))
            ->setName(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setListaItens($dados['lista_itens']);
    }

    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser preenchido corretamente', 'danger');
        }
        return $this;
    }

    private function validaQuantity()
    {
        $value = v::floatVal()->notEmpty()->noWhitespace()->validate($this->getQuantity());
        if (!$value) {
            msg::showMsg('O campo Quantidade deve ser preenchido corretamente.'
                . '<script>focusOn("quantity");</script>', 'danger');
        }
        return $this;
    }
}
