<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Helpers\Utils;
use App\Models\RecipesModel;

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
            'entidade' => 'menus 
            INNER JOIN users requester ON requester.id = menus.users_id_requesters
            INNER JOIN users authorizers ON authorizers.id = menus.users_id_authorizers',
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => 'beginning_date ASC',
            'select' => 'menus.*, requester.name requester, authorizers.name authorizers'
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

    public function novoRegistro($user)
    {
        // Valida dados
        $this->validaAll($user);
        // inserindo o cardápio
        $dados = [
            'oms_id' => $this->getOmsId(),
            'users_id_requesters' => $this->getUserRequesters(),
            'users_id_authorizers' => $this->getUserAuthorizers(),
            'beginning_date' => $this->getBeginningDate(),
            'ending_date' => $this->getEndingDate(),
            'raw_menus_object' => json_encode($this->getRecipes())
        ];

        if (parent::novo($dados)) {
            $menusId = $this->pdo->lastInsertId();
            foreach ($this->getRecipes() as $values) {
                // inserindo as receitas
                (new RecipesModel())->novoRegistro($values, $menusId);
            }
            echo '{"id": ' . $menusId . '}';
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

    public function removerRegistro($id, $menusId)
    {
        $recipesModel = new RecipesModel;
        $result = $recipesModel->findById($id);
        if ($result) {
            $count = $this->pdo
                ->query("SELECT id FROM recipes WHERE date = '{$result['date']}' AND menus_id = {$result['menus_id']}")
                ->fetchAll(\PDO::FETCH_ASSOC);

            if (count($count) > 1) {
                $recipesModel->removerRegistro($id, $menusId);
            } else {
                header('Location: ' . cfg::DEFAULT_URI . "cardapio/detalhar/id/{$menusId}");
            }
        }
    }

    public function removerMenu($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'cardapio/ver/');
        }
    }

    public function aprovar($id, $user)
    {
        $dados = [
            'status' => 'APROVADO',
            'users_id_authorizers' => $user['id']
        ];

        if (parent::editar($dados, $id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'cardapio/');
        }
    }

    public function atualizaDataCardapio()
    {
        $beginningDate = filter_input(INPUT_POST, 'beginningDate');
        $endingDate = date("Y-m-d", strtotime("" . Utils::dateDatabaseFormate($beginningDate) . " +6 day"));
        $id = filter_input(INPUT_POST, 'id');

        $dados = [
            'beginning_date' => Utils::dateDatabaseFormate($beginningDate),
            'ending_date' => $endingDate
        ];
        if (parent::editar($dados, $id)) {
            msg::showMsg('001', 'success');
        }
    }

    private function validaAll($user)
    {
        $omId = intval($user['oms_id'] ?? 0);
        $userId = intval($user['id'] ?? 0);
        // Seta todos os valores
        $menuMap = filter_input_array(INPUT_POST);
        $data = $menuMap["menuMap"];
        $beginningDate = date("Y-m-d", strtotime($data[0]["date"]));
        $endingDate = date("Y-m-d", strtotime("" . $data[0]["date"] . " +6 day"));
        $this->setOmsId(filter_var($omId, FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUserRequesters(filter_var($userId, FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUserAuthorizers(filter_var($userId, FILTER_SANITIZE_SPECIAL_CHARS))
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
            $query = "SELECT id FROM {$this->entidade} WHERE oms_id = :omId AND (beginning_date >= :date AND ending_date <= :date)";
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

    public function changeStatus(string $status, int $id)
    {
        $dados = [
            'status' => strtoupper($status)
        ];
        return parent::editar($dados, $id);
    }

    /**
     * Returns all data to make the menus reports
     * @param int $id
     * @return array
     */
    public function returnsDataFromMenus(int $id): array
    {
        $query = ""
            . " SELECT "
            . "    mn.beginning_date, "
            . "    mn.ending_date, "
            . "    rcp.name AS recipes_name, "
            . "    mls.name AS meals_name, "
            . "    mls.sort, "
            . "    rcp.date, "
            . "    oms.fiscal_agent, "
            . "    oms.fiscal_agent_graduation, "
            . "    oms.munition_fiel, "
            . "    oms.munition_fiel_graduation, "
            . "    oms.munition_manager, "
            . "    oms.munition_manager_graduation "
            . " FROM "
            . "    menus AS mn "
            . "        INNER JOIN recipes AS rcp ON rcp.menus_id = mn.id "
            . "        INNER JOIN meals AS mls ON mls.id = rcp.meals_id "
            . "        INNER JOIN oms ON oms.id = mn.oms_id "
            . " WHERE "
            . "    mn.id = {$id}  "
            . " ORDER BY rcp.date, mls.sort, rcp.sort, mn.id; ";
        return $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
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
