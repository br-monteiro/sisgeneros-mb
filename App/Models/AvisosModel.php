<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Models\AvisosListaOmsModel;
use App\Models\OmModel;

class AvisosModel extends CRUD
{

    protected $entidade = 'quadro_avisos';
    private $resultadoPaginator;
    private $navPaginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina)
    {
        $innerJoin = ""
            . " AS qa "
            . " INNER JOIN users AS us"
            . "     ON us.id = qa.usuario_criador "
            . " INNER JOIN quadro_avisos_lista_oms AS qalo "
            . "     ON qalo.quadro_avisos_id = qa.id "
            . " GROUP BY qa.id";
        $dados = [
            'entidade' => $this->entidade . $innerJoin,
            'select' => 'qa.*, us.name AS usuario_criador_nome, COUNT(*) AS om_quantidade ',
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => 'created_at ASC'
        ];

        $paginator = new Paginator($dados);
        $this->resultadoPaginator = $paginator->getResultado();
        $this->navPaginator = $paginator->getNaveBtn();
    }

    public function getResultadoPaginator()
    {
        return $this->resultadoPaginator;
    }

    public function getNavePaginator()
    {
        return $this->navPaginator;
    }

    public function novoRegistro(array $user)
    {
        $this->validaAll();

        $dados = [
            'titulo' => $this->getTitulo(),
            'corpo' => $this->getCorpo(),
            'usuario_criador' => $user['id'],
            'created_at' => date('d-m-Y'),
            'data_inicio' => $this->getDataInicio(),
            'data_fim' => $this->getDataFim()
        ];

        if (parent::novo($dados)) {
            $lastId = $this->pdo->lastInsertId();
            $quadroAvisosListaOms = new AvisosListaOmsModel();

            foreach ($this->buildOmsId() as $omId) {
                $dados = [
                    'om_id' => $omId,
                    'quadro_avisos_id' => $lastId
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
            'titulo' => $this->getTitulo(),
            'corpo' => $this->getCorpo(),
            'data_inicio' => $this->getDataInicio(),
            'data_fim' => $this->getDataFim()
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
                . " qalo.id, om.indicativo_naval, om.nome "
                . " FROM quadro_avisos_lista_oms AS qalo "
                . " INNER JOIN om "
                . "     ON om.id = qalo.om_id "
                . " WHERE qalo.quadro_avisos_id = {$aviso['id']} "
                . " ORDER BY om.nome ";
            $result['oms'] = $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        }
        header('Location: ' . cfg::DEFAULT_URI . 'avisos/ver');
    }

    public function fetchOmOut(int $id)
    {
        $result = [];
        $omCount = count((new OmModel())->findAll());
        $omInsertedCount = count((new AvisosListaOmsModel())->findAllByQuadro_avisos_id($id));

        if ($omCount != $omInsertedCount) {
            $query = ""
                . " SELECT "
                . " om.id, om.indicativo_naval, om.nome "
                . " FROM om "
                . " WHERE om.id NOT IN ("
                . "     SELECT "
                . "         om.id "
                . "     FROM quadro_avisos_lista_oms AS qalo "
                . "     INNER JOIN om "
                . "         ON om.id = qalo.om_id "
                . "     WHERE qalo.quadro_avisos_id = {$id} "
                . " ) "
                . " ORDER BY om.nome";
            $result = $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        }
        return $result;
    }

    public function adicionarNovaOM()
    {
        $this->setId()
            ->setOmId(filter_input(INPUT_POST, 'om'));

        $this->validaId()
            ->validaInt($this->getOmId());

        $query = ""
            . " SELECT "
            . " id "
            . " FROM quadro_avisos_lista_oms AS qalo "
            . " WHERE qalo.om_id = :omId AND qalo.quadro_avisos_id = :qaId";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':omId' => $this->getOmId(),
            ':qaId' => $this->getId()
        ]);

        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Esta OM já foi adicionada.', 'danger');
        }

        $dados = [
            'om_id' => $this->getOmId(),
            'quadro_avisos_id' => $this->getId()
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
            . " qa.* "
            . " FROM quadro_avisos AS qa "
            . " INNER JOIN quadro_avisos_lista_oms AS qalo "
            . "     ON qalo.om_id = {$omId} "
            . " WHERE "
            . "     qa.data_inicio <= DATE('{$date}') "
            . "     AND qa.data_fim >= DATE('{$date}') "
            . " ORDER BY DATE(qa.created_at) ";

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
        $this->setId()
            ->setTitutlo(filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setCorpo(filter_input(INPUT_POST, 'corpo', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDataInicio(filter_input(INPUT_POST, 'data_inicio', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDataFim(filter_input(INPUT_POST, 'data_fim', FILTER_SANITIZE_SPECIAL_CHARS));

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaTitulo()
            ->validaCorpo()
            ->validaDataInicio()
            ->validaDataFim();
    }

    private function setId()
    {
        $value = filter_input(INPUT_POST, 'id');
        $this->id = $value ?: time();
        return $this;
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

    private function validaTitulo()
    {
        $value = v::stringType()->notEmpty()->length(3, 100)->validate($this->getTitulo());
        if (!$value) {
            msg::showMsg('O campo Título deve ser preenchido corretamente.'
                . '<script>focusOn("titulo");</script>', 'danger');
        }
        return $this;
    }

    private function validaCorpo()
    {
        $value = v::stringType()->notEmpty()->length(3, 256)->validate($this->getCorpo());
        if (!$value) {
            msg::showMsg('O campo Mensagem deve ser preenchido corretamente.'
                . '<script>focusOn("titulo");</script>', 'danger');
        }
        return $this;
    }

    private function abstractDateValidate(string $value, string $fieldName, string $labelName)
    {
        $date = explode('-', $value);
        $date = $date[2] . '-' . $date[1] . '-' . $date[0];
        if (!v::date()->validate($date)) {
            msg::showMsg('O campo ' . $labelName . ' deve ser preenchido corretamente.'
                . '<script>focusOn("' . $fieldName . '");</script>', 'danger');
        }
        return $date;
    }

    private function validaDataInicio()
    {
        $this->setDataInicio($this->abstractDateValidate($this->getDataInicio(), 'data_inicio', 'Data início'));
        return $this;
    }

    private function validaDataFim()
    {
        $this->setDataFim($this->abstractDateValidate($this->getDataFim(), 'data_fim', 'Data final'));
        return $this;
    }

    private function buildOmsId(): array
    {
        $result = [];
        $requestPost = filter_input_array(INPUT_POST);
        $items = is_array($requestPost['om'] ?? null) ? $requestPost['om'] : [];

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
