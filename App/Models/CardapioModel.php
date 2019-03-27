<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;

class CardapioModel extends CRUD
{

    protected $entidade = 'menus';

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
            'orderBy' => 'beginning_date ASC'
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

    public function novoRegistro($omId)
    {
        // Valida dados
        $this->validaAll($omId);

        // inserindo o cardápio
        $dados = [
            'oms_id' => $this->getOmsId(),
            'beginning_date' => $this->getBeginningDate(),
            'ending_date' => $this->getEndingDate()
        ];

        if ($dados) {
            $menusId = 10;//$this->pdo->lastInsertId();
            foreach ($this->getRecipes() as $values) {
                // inserindo as receitas
                $recipeId = (new RecipesModel())->novoRegistro($values['data'], $menusId);
            }
        }
    }

    public function editarRegistro()
    {
        // Valida dados
        $this->validaAll();

        $dados = [
            'oms_id' => $this->getOmsId(),
            'status' => $this->getStatus(),
            'beginning_date' => $this->getBeginningDate(),
            'ending_date' => $this->getEndingDate()
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'suppliers/ver/');
        }
    }

    private function validaAll($oms)
    {
        // Seta todos os valores
        $menuMap = filter_input_array(INPUT_POST);
        $data = $menuMap["menuMap"];

        $beginningDate = date("Y-m-d", strtotime($data[0]["date"]));
        $endingDate = date("Y-m-d", strtotime("".$data[0]["date"]." +7 day"));

        $this->setOmsId(filter_var($oms, FILTER_SANITIZE_SPECIAL_CHARS))
            ->setBeginningDate($beginningDate)
            ->setEndingDate($endingDate)
            ->setRecipes($data);
    }

    /**
     * Check if the date has menu registred
     * @param string $date
     * @param int $omId
     * @return \stdClass
     */
    public function checkDate(string $date = '', int $omId): \stdClass
    {
        $result = new \stdClass;
        $result->inputDate = $date;
        $result->menusId = 0;

        if (preg_match('/^(19|20)\d{2}-(0[1-9]|1[0-2])-(0[1-9]|1\d|2\d|3[01])$/', $date)) {
            $query = "SELECT id FROM {$this->entidade} WHERE oms_id = :omId AND (beginning_date >= :date OR ending_date <= :date)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                ':omId' => $omId,
                ':date' => $date
            ]);
            $resultQuery = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($resultQuery) {
                $result->menusId = $resultQuery['id'];
            }
        }

        return $result;
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

    private function abstractDateValidate(string $value, string $fieldName, string $labelName)
    {
        $date = Utils::dateDatabaseFormate($value);
        if (!v::date()->validate($date)) {
            msg::showMsg('O campo ' . $labelName . ' deve ser preenchido corretamente.'
                . '<script>focusOn("' . $fieldName . '");</script>', 'danger');
        }
        return $date;
    }

    private function validaBeginningDate()
    {
        $this->setBeginningDate($this->abstractDateValidate($this->getBeginningDate(), 'beginning_date', 'Data início'));
        return $this;
    }

    private function validaEndingDate()
    {
        $this->setEndingDate($this->abstractDateValidate($this->getEndingDate(), 'ending_date', 'Data final'));
        return $this;
    }

    private function validaOmsId()
    {
        $value = v::intVal()->validate($this->getOmsId());
        if (!$value) {
            msg::showMsg('O campo Om deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }
}
