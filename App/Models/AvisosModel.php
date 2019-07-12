<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Models\AvisosListaOmsModel;
use App\Models\OmModel;
use App\Helpers\Utils;

class AvisosModel extends CRUD
{

    protected $entidade = 'billboards';

    /**
     * @var \HTR\Helpers\Paginator\Paginator 
     */
    private $paginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina)
    {
        $innerJoin = ""
            . " AS bill "
            . " GROUP BY bill.id";
        $dados = [
            'entidade' => $this->entidade . $innerJoin,
            'select' => 'bill.*',
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

    public function novoRegistro()
    {
        $this->validaAll();

        $dados = [
            'title' => $this->getTitle(),
            'content' => $this->getContent(),
            'beginning_date' => $this->getBeginningDate(),
            'ending_date' => $this->getEndingDate()
        ];

        if (parent::novo($dados)) {
            $lastId = $this->pdo->lastInsertId();
            $quadroAvisosListaOms = new AvisosListaOmsModel();

            foreach ($this->buildOmsId() as $omId) {
                $dados = [
                    'oms_id' => $omId,
                    'billboards_id' => $lastId
                ];
                $quadroAvisosListaOms->novo($dados);
            }

            msg::showMsg('111', 'success');
        }
    }

    public function editarRegistro()
    {
        $this->validaAll();

        $dados = [
            'title' => $this->getTitle(),
            'content' => $this->getContent(),
            'beginning_date' => $this->getBeginningDate(),
            'ending_date' => $this->getEndingDate()
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function fetchDataToEdit(int $id): array
    {
        $result = [];
        $aviso = $this->findById($id);
        if ($aviso) {
            $result['result'] = $aviso;
            $query = ""
                . "SELECT "
                . " bol.id, oms.naval_indicative, oms.name "
                . " FROM billboards_oms_lists AS bol "
                . " INNER JOIN oms "
                . "     ON oms.id = bol.oms_id "
                . " WHERE bol.billboards_id = {$aviso['id']} "
                . " ORDER BY oms.name ";
            $result['oms'] = $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        }
        header('Location: ' . cfg::DEFAULT_URI . 'avisos/ver');
    }

    public function fetchOmOut(int $id)
    {
        $result = [];
        $omCount = count((new OmModel())->findAll());
        $omInsertedCount = count((new AvisosListaOmsModel())->findAllByBillboards_id($id));

        if ($omCount != $omInsertedCount) {
            $query = ""
                . " SELECT "
                . " oms.id, oms.naval_indicative, oms.name "
                . " FROM oms "
                . " WHERE oms.id NOT IN ("
                . "     SELECT "
                . "         oms.id "
                . "     FROM billboards_oms_lists AS bol "
                . "     INNER JOIN oms "
                . "         ON oms.id = bol.oms_id "
                . "     WHERE bol.billboards_id = {$id} "
                . " ) "
                . " ORDER BY oms.name";
            $result = $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        }
        return $result;
    }

    public function adicionarNovaOM()
    {
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setOmsId(filter_input(INPUT_POST, 'oms'));

        $this->validaId()
            ->validaInt($this->getOmsId());

        $query = ""
            . " SELECT "
            . " id "
            . " FROM billboards_oms_lists AS bol "
            . " WHERE bol.oms_id = :omId AND bol.billboards_id = :billId";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':omId' => $this->getOmsId(),
            ':billId' => $this->getId()
        ]);

        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Esta oms já foi adicionada.', 'danger');
        }

        $dados = [
            'oms_id' => $this->getOmsId(),
            'billboards_id' => $this->getId()
        ];

        if ((new AvisosListaOmsModel())->novo($dados)) {
            msg::showMsg('111', 'success');
        }
    }

    public function fetchAllAvisosByOmId(int $omId)
    {
        $date = date('Y-m-d');
        $query = ""
            . " SELECT "
            . " DISTINCT bill.* "
            . " FROM billboards AS bill "
            . " INNER JOIN billboards_oms_lists AS bol "
            . "     ON bol.billboards_id = bill.id "
            . " WHERE "
            . "     bill.beginning_date <= DATE('{$date}') "
            . "     AND bill.ending_date >= DATE('{$date}') "
            . "     AND bol.oms_id = {$omId} "
            . " GROUP BY bill.title, bill.content, bill.id "
            . " ORDER BY DATE(bill.beginning_date) ";

        return $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function removerRegistro($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'avisos/ver/');
        }
    }

    public function eliminarOm(int $id, $avisoId)
    {
        if ((new AvisosListaOmsModel())->remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'avisos/editar/id/' . $avisoId);
        }
    }

    private function validaAll()
    {
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setTitle(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setContent(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setBeginningDate(filter_input(INPUT_POST, 'beginning_date', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setEndingDate(filter_input(INPUT_POST, 'ending_date', FILTER_SANITIZE_SPECIAL_CHARS));

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaTitle()
            ->validaContent()
            ->validaBeginningDate()
            ->validaEndingDate();
    }
    
    private function validaInt($value)
    {
        $value = v::intVal()->validate($value);
        if (!$value) {
            msg::showMsg('Não foi possível registrar a solicitação', 'danger');
        }
        return $value;
    }

    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaTitle()
    {
        $value = v::stringType()->notEmpty()->validate($this->getTitle());
        if (!$value || !Utils::checkLength($this->getTitle(), 3, 100)) {
            msg::showMsg('O campo Título deve ser preenchido corretamente.'
                . '<script>focusOn("title");</script>', 'danger');
        }
        return $this;
    }

    private function validaContent()
    {
        $value = v::stringType()->notEmpty()->validate($this->getContent());
        if (!$value || !Utils::checkLength($this->getContent(), 3, 256)) {
            msg::showMsg('O campo Mensagem deve ser preenchido corretamente.'
                . '<script>focusOn("title");</script>', 'danger');
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

    private function buildOmsId(): array
    {
        $result = [];
        $requestPost = filter_input_array(INPUT_POST);
        $items = is_array($requestPost['oms'] ?? null) ? $requestPost['oms'] : [];

        foreach ($items as $omId) {
            $value = v::intVal()->validate($omId);
            if (!$value) {
                msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
            }
            $result[] = $omId;
        }

        return $result;
    }
}
