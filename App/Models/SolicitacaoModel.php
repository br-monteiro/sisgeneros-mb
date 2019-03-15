<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use App\Models\LicitacaoModel as Licitacao;
use App\Models\SolicitacaoItemModel as Itens;
use Respect\Validation\Validator as v;
use App\Models\AvaliacaoFornecedorModel;
use App\Config\Configurations as cfg;
use HTR\System\ControllerAbstract;
use App\Helpers\Utils;

class SolicitacaoModel extends CRUD
{

    protected $entidade = 'requests';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;
    protected $listaItens = [];

    public function returnAll()
    {
        return $this->findAll();
    }

    public function recuperaDadosRelatorioSolicitacao($id)
    {
        $requests = $this->findById($id);
        if ($requests['biddings_id']) {
            $query = "SELECT requests.*, requests.number AS number_requests, biddings.*,"
                . "oms.name as oms_name, "
                . "suppliers.name as suppliers_name "
                . "FROM requests "
                . "INNER JOIN oms ON oms.id = requests.oms_id "
                . "INNER JOIN biddings ON biddings.id = requests.biddings_id "
                . "INNER JOIN suppliers ON suppliers.id = requests.suppliers_id "
                . "WHERE requests.id = ?";
        } else {
            $query = "SELECT requests.*, requests.number AS number_requests, oms.name as oms_name,"
                . "suppliers.name as suppliers_name "
                . "FROM requests "
                . "INNER JOIN oms ON oms.id = requests.oms_id "
                . "INNER JOIN suppliers ON suppliers.id = requests.suppliers_id "
                . "WHERE requests.id = ?";
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function alteraDeliveryDate($id, $user)
    {
        // verifica se o usuário não é adminitrador
        if ($user['level'] !== 'ADMINISTRADOR') {
            // colsuta a solicitação por id_lista
            $sol = $this->findById($id);
            // verifica se a solicitação é da mesma OM que o usuário lodado
            if ($sol['oms_id'] != $user['oms_id']) {
                // caso seja de outra OM, redireciona para histórico de solicitações
                header("location:" . cfg::DEFAULT_URI . "solicitacao/");
                exit; // just stop the execution
            }
        }

        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setDeliveryDate(filter_input(INPUT_POST, 'delivery_date', FILTER_SANITIZE_SPECIAL_CHARS));
        $id = $this->getId();

        $this->validaDeliveryDate($this->getDeliveryDate());

        $dados = [
            'delivery_date' => $this->getDeliveryDate()
        ];

        if (parent::editar($dados, $id)) {
            msg::showMsg('Data de entrega alterada com sucesso.', 'success');
        }
    }

    public function retornaDadosPapeleta($id, $user = null, $controllerReceber = false)
    {
        $where = '';
        if (isset($user['level']) && $user['level'] !== 'ADMINISTRADOR') {
            $where = ' AND oms.id = ' . $user['oms_id'];
        }
        if (!$controllerReceber) {
            $where .= " AND status != 'ABERTO' AND status != 'APROVADO' ";
        } else {
            $where .= " AND status = 'SOLICITADO' ";
        }
        $stmt = $this->pdo->prepare("SELECT biddings_id FROM requests WHERE id = ? ");
        $stmt->execute([$id]);
        $requests = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($requests['biddings_id']) {
            $query = "SELECT
                sol.*,
                sol.id AS requests_id,
                sol.number AS requests_number,
                oms.*,
                oms.name AS oms_name,
                item.id as item_id,
                item.*,
                sol.updated_at,
                suppliers.name AS suppliers_name,
                suppliers.cnpj,
                biddings.number AS biddings_number
            FROM requests AS sol
            INNER JOIN requests_items AS item
               ON item.requests_id = sol.id
            INNER JOIN oms
               ON oms.id = sol.oms_id
            INNER JOIN suppliers
               ON suppliers.id = sol.suppliers_id
           INNER JOIN biddings
               ON biddings.id = sol.biddings_id
            WHERE sol.id = ? {$where}";
        } else {
            $query = "SELECT 
                sol.*,
                sol.id AS requests_id,
                sol.number AS requests_number,
                oms.*,
                oms.name AS oms_name,
                item.*,
                sol.updated_at,
                suppliers.name AS suppliers_name,
                suppliers.cnpj
            FROM requests AS sol
            INNER JOIN oms
               ON oms.id = sol.oms_id
            INNER JOIN requests_items as item
               ON item.requests_id = sol.id
            INNER JOIN suppliers
               ON suppliers.id = sol.suppliers_id
            WHERE sol.id = ? {$where}";
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function recebimento($id)
    {
        $this->setInvoice(filter_input(INPUT_POST, 'invoice'));
        $this->validaInvoice($this->getInvoice());
        $this->setObservation(filter_input(INPUT_POST, 'observation', FILTER_SANITIZE_SPECIAL_CHARS));
        $this->setItemsList($this->buildItemsBiddings(filter_input_array(INPUT_POST)));
        $this->validaItemsList();

        $dados = [
            'status' => 'RECEBIDO',
            'updated_at' => date('Y-m-d H:i:s'),
            'invoice' => $this->getInvoice(),
            'observation' => $this->getObservation()
        ];

        if (parent::editar($dados, $id)) {

            (new Itens())->recebimento($this->getItemsList());

            (new AvaliacaoFornecedorModel())->novoRegistro([
                'evaluation' => filter_input(INPUT_POST, 'evaluation', FILTER_VALIDATE_INT) ?: 3,
                'requests_id' => $id
            ]);

            msg::showMsg('Operação efetuada com sucesso!'
                . '<script>'
                . 'mostrar("btn_voltar");'
                . 'ocultar("tabela_result");'
                . 'ocultar("btn_enviar");'
                . 'ocultar("invoice_container");'
                . 'ocultar("observation");'
                . 'ocultar("evaluation");'
                . 'ocultar("legenda");'
                . '</script>', 'success');
        }
    }

    public function recebimentoNaoLicitado($id)
    {
        $this->setInvoice(filter_input(INPUT_POST, 'invoice'));
        $this->validaInvoice($this->getInvoice());

        $this->setObservation(filter_input(INPUT_POST, 'observation', FILTER_SANITIZE_SPECIAL_CHARS));

        $dados = [
            'status' => 'RECEBIDO',
            'updated_at' => date('Y-m-d H:i:s'),
            'invoice' => $this->getInvoice(),
            'observation' => $this->getObservation()
        ];

        parent::editar($dados, $id);

        $itens = new Itens();
        $itens->recebimentoNaoLicitado($id);
        msg::showMsg('Operação efetuada com sucesso!'
            . '<script>'
            . 'mostrar("btn_voltar");'
            . 'ocultar("tabela_result");'
            . 'ocultar("btn_enviar");'
            . 'ocultar("invoice_container");'
            . 'ocultar("observation");'
            . 'ocultar("evaluation");'
            . 'ocultar("legenda");'
            . '</script>', 'success');
    }

    public function paginator($pagina, $user, $busca = null, $oms = null, $dtInicio = null, $dtFim = null)
    {
        $innerJoin = " AS sol INNER JOIN oms ON oms.id = sol.oms_id ";
        $subQuery = ' (SELECT name FROM suppliers AS f WHERE f.id = sol.suppliers_id) as suppliers_name ';

        $dados = [
            'select' => 'sol.*, ' . $subQuery . ', oms.naval_indicative',
            'entidade' => $this->entidade . $innerJoin,
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => 'sol.updated_at DESC'
        ];

        if (!in_array($user['level'], ['ADMINISTRADOR', 'CONTROLADOR'])) {
            $dados['where'] = 'oms_id = :omsId ';
            $dados['bindValue'] = [':omsId' => $user['oms_id']];
        }

        if ($user['level'] === 'CONTROLADOR') {
            $dados['where'] = 'status != :status ';
            $dados['bindValue'] = [':status' => 'ABERTO'];
        }

        if ($busca) {
            $dateInit = $dateEnd = $busca;

            if (preg_match('/\d{2}-\d{2}-\d{4}/', $busca)) {
                $dateEnd = Utils::dateDatabaseFormate($busca);
            }

            $andExists = isset($dados['where']) ? 'AND' : '';
            $dados['where'] = ($dados['where'] ?? "") . " {$andExists} ( "
                . 'sol.status LIKE :search '
                . 'OR sol.number LIKE :search '
                . 'OR sol.created_at BETWEEN :dInit AND :dEnd '
                . 'OR sol.updated_at BETWEEN :dInit AND :dEnd '
                . 'OR sol.delivery_date LIKE :search '
                . ') ';

            $bindValue = [
                ':search' => '%' . $busca . '%',
                ':dInit' => $dateInit,
                ':dEnd' => $dateEnd
            ];
            $dados['bindValue'] = $dados['bindValue'] ?? [];
            $dados['bindValue'] = array_merge($dados['bindValue'], $bindValue);
        }

        $this->paginator = new Paginator($dados);
    }

    public function paginatorSolicitacoes(ControllerAbstract $controller)
    {
        $select = ""
            . " sol.number AS requests_number, "
            . " oms.naval_indicative, sol.invoice, "
            . " sol.biddings_id, sol.delivery_date, "
            . " sol.status AS requests_status ";
        $innerJoin = ""
            . " AS sol "
            . " INNER JOIN oms ON oms.id = sol.oms_id ";
        $dados = [
            'entidade' => $this->entidade . $innerJoin,
            'select' => $select,
            'pagina' => $controller->getParametro('pagina'),
            'maxResult' => 500,
            'orderBy' => 'sol.created_at ASC',
            'bindValue' => []
        ];
        $params = $controller->getParametro();
        // search by Om
        if (isset($params['oms']) && intval($params['oms']) !== 0) {
            $dados['where'] = ' oms.id = :omsId ';
            $dados['bindValue'][':omsId'] = $params['oms'];
        }
        // search by status
        if (isset($params['status'])) {
            if (isset($dados['where'])) {
                $dados['where'] .= ' AND sol.status = :status ';
            } else {
                $dados['where'] = ' sol.status = :status ';
            }
            $dados['bindValue'][':status'] = $params['status'];
        }
        // search by Date Init
        if (isset($params['dateInit']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateInit'])) {
            if (isset($dados['where'])) {
                $dados['where'] .= ' AND sol.created_at >= :dateInit ';
            } else {
                $dados['where'] = ' sol.created_at >= :dateInit ';
            }
            $dados['bindValue'][':dateInit'] = Utils::dateDatabaseFormate($params['dateInit']);
        }
        // search by Date End
        if (isset($params['dateEnd']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateEnd'])) {
            if (isset($dados['where'])) {
                $dados['where'] .= ' AND sol.created_at <= :dateEnd ';
            } else {
                $dados['where'] = ' sol.created_at <= :dateEnd ';
            }
            $dados['bindValue'][':dateEnd'] = Utils::dateDatabaseFormate($params['dateEnd']);
        }
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

    public function novoNaoLicitado($oms, $directoryReference)
    {
        $this->validaAll($oms);
        $this->validaDeliveryDate($this->getDeliveryDate());
        $this->validaFiles();
        $dados = [
            'biddings_id' => $this->getBiddingsId(),
            'oms_id' => $this->getOmsId(),
            'suppliers_id' => $this->getSuppliersId(),
            'number' => $this->getNumber(),
            'status' => 'ABERTO',
            'created_at' => date('Y-m-d'),
            'updated_at' => date('Y-m-d H:i:s'),
            'biddings_id' => 0,
            'delivery_date' => $this->getDeliveryDate()
        ];

        if (parent::novo($dados)) {
            $lastId = $this->pdo->lastInsertId();

            (new Itens())->novoNaoLicitado($this->getItemsList(), $lastId);

            $this->saveFiles($directoryReference, $dados['number']);

            msg::showMsg('Solicitação Registrada com Sucesso!<br>'
                . "<strong>Solicitação Nº {$this->getNumber()} <br>"
                . "Status: ABERTO.</strong><br>"
                . "<a href='" . cfg::DEFAULT_URI . "solicitacao/detalhar/idlista/{$lastId}' class='btn btn-info'>"
                . '<i class="fa fa-info-circle"></i> Detalhar Solicitação</a>'
                . '<script>resetForm(); </script>', 'success');
        }
    }

    public function novoRegistro($oms)
    {
        // Valida dados
        $this->validaAll($oms);
        $this->validaDeliveryDate($this->getDeliveryDate());

        $dados = [
            'biddings_id' => $this->getBiddingsId(),
            'oms_id' => $this->getOmsId(),
            'suppliers_id' => $this->getSuppliersId(),
            'number' => $this->getNumber(),
            'status' => 'ABERTO',
            'created_at' => date('Y-m-d'),
            'updated_at' => date('Y-m-d H:i:s'),
            'biddings_id' => $this->getBiddingsId(),
            'delivery_date' => $this->getDeliveryDate()
        ];

        if (parent::novo($dados)) {
            $lastId = $this->pdo->lastInsertId();
            (new Itens())->novoRegistro($this->getItemsList(), $lastId);

            msg::showMsg('Solicitação Registrada com Sucesso!<br>'
                . "<strong>Solicitação Nº {$this->getNumber()} <br>"
                . "Status: ABERTO.</strong><br>"
                . "<a href='" . cfg::DEFAULT_URI . "solicitacao/detalhar/idlista/{$lastId}' class='btn btn-info'>"
                . '<i class="fa fa-info-circle"></i> Detalhar Solicitação</a>'
                . '<script>resetForm(); </script>', 'success');
        }
    }

    public function removerRegistro($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->entidade} WHERE id_lista = ?");
        $stmt->bindValue(1, $id);
        if ($stmt->execute()) {
            header('Location: ' . cfg::DEFAULT_URI . 'solicitacao/');
        }
    }

    public function update($id)
    {
        $dados = [
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return parent::editar($dados, $id);
    }

    public function aprovar($id)
    {
        $dados = [
            'status' => 'APROVADO',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (parent::editar($dados, $id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'solicitacao/');
        }
    }

    public function avaliaAcesso($id, $user)
    {
        $requests = $this->findById($id);
        // verifica se a solicitação já foi aprovada
        if ($requests['status'] !== 'ABERTO') {
            header("Location:" . cfg::DEFAULT_URI . "solicitacao/");
            // verifica se o usuário é da mensa OM da solicitação
        } elseif ($user['level'] !== 'ADMINISTRADOR' && $user['oms_id'] != $requests['oms_id']) {
            header("Location:" . cfg::DEFAULT_URI . "solicitacao/");
        }
        return $requests;
    }

    /**
     * Generate the number of Solicitação
     * @return int The solictação number
     */
    protected function numberGenerator(int $number = 0): int
    {
        if ($number > 0) {
            $hasEqualsRegister = $this->pdo
                ->query("SELECT id FROM requests WHERE number = {$number}")
                ->fetch(\PDO::FETCH_OBJ);

            // If exists a register with this number, try with the number plus one
            if ($hasEqualsRegister) {
                return $this->numberGenerator($number + 1);
            }

            return $number;
        }

        $currentYear = date('Y');
        $currentYearShort = date('y');
        $query = "SELECT COUNT(id) as quantity FROM requests WHERE YEAR(created_at) = '{$currentYear}'";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $registersQuantity = $stmt->fetch(\PDO::FETCH_OBJ)->quantity;
        $number = (int) $currentYearShort . ($registersQuantity + 1);
        // check if in the exact momsent exists a register with this number
        return $this->numberGenerator($number);
    }

    /**
     * Process the solcitação status.
     * @param int $id Identification of Solicitação
     * @param string $status The status to be changing
     * @param stritn $action The action to be executed
     */
    public function processStatus(int $id, string $status, string $action)
    {
        $statusPatterns = cfg::DEFAULT_REQUEST_STATUS;
        $allowedActions = [
            'PROXIMO',
            'ANTERIOR'
        ];

        if (in_array($action, $allowedActions)) {
            $dados = [];
            $nextSatus = $statusPatterns[$status] ?? false;
            $previousStatus = array_search($status, $statusPatterns);
            $solcitacao = $this->findById($id);

            if (
                $nextSatus !== false &&
                $solcitacao &&
                $solcitacao['status'] === $status &&
                $action === 'PROXIMO'
            ) {
                $dados = [
                    'status' => $nextSatus,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            } elseif (
                $previousStatus !== false &&
                $solcitacao &&
                $solcitacao['status'] === $status &&
                $action === 'ANTERIOR'
            ) {
                $dados = [
                    'status' => $previousStatus,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            if (count($dados) > 0) {
                parent::editar($dados, $id);
            }
        }
    }

    /**
     * Select the Solicitation by Id Lista Field
     * @param int $requestId
     * @return array
     */
    public function findByIdlista($requestId)
    {
        $query = ""
            . " SELECT "
            . " sol.*, "
            . " supp.name AS suppliers_name, "
            . " supp.cnpj AS suppliers_cnpj, "
            . " supp.details AS suppliers_details "
            . " FROM {$this->entidade} AS sol "
            . " INNER JOIN suppliers AS supp "
            . "     ON supp.id = sol.suppliers_id "
            . " WHERE sol.id = :requestId ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':requestId' => $requestId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findQtdSolicitByStatus($user, $status = 'ABERTO')
    {
        $query = ""
            . "SELECT "
            . "COUNT(*) quantity "
            . "FROM {$this->entidade} "
            . "WHERE status LIKE :status";

        if (!in_array($user['level'], ['ADMINISTRADOR', 'CONTROLADOR'])) {
            $where = " AND oms_id = {$user['oms_id']} ";
            $query . $where;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':status' => $status]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findQtdSolicitAtrasadas($user, $status = 'SOLICITADO')
    {
        $query = ""
            . " SELECT "
            . " COUNT(*) AS quantity "
            . " FROM {$this->entidade} "
            . " WHERE status LIKE :status AND delivery_date < '" . date('Y-m-d') . "' ";
        if (!in_array($user['level'], ['ADMINISTRADOR', 'CONTROLADOR'])) {
            $where = " AND oms_id = {$user['oms_id']} ";
            $query . $where;
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':status' => $status]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findSolitacoesMensal($user)
    {
        $query = ""
            . " SELECT "
            . " COUNT(*) AS quantity "
            . " FROM {$this->entidade} "
            . " WHERE created_at BETWEEN '" . date('Y-m') . "-01' AND '" . date('Y-m-d') . "' ";

        if (in_array($user['level'], ['ADMINISTRADOR', 'CONTROLADOR'])) {
            $query .= " AND oms_id = {$user['oms_id']} ";
        }

        return $this->pdo
                ->query($query)
                ->fetch((\PDO::FETCH_ASSOC));
    }

    /**
     * Fetch the last updated solicitation
     * @param array $user The user logged in
     * @return array
     */
    public function lastUpdated(array $user): array
    {
        $where = '';
        if (!in_array($user['level'], ['ADMINISTRADOR', 'CONTROLADOR'])) {
            $where = " WHERE sol.oms_id = " . $user['oms_id'];
        }
        $query = ""
            . " SELECT "
            . "     sol.number, "
            . "     sol.status, "
            . "     sol.updated_at, "
            . "     oms.naval_indicative"
            . " FROM {$this->entidade} AS sol "
            . " INNER JOIN oms ON oms.id = sol.oms_id "
            . " {$where} "
            . " ORDER BY "
            . "     sol.updated_at DESC "
            . " LIMIT 10";
        return $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function validaAll($oms)
    {
        $value = filter_input_array(INPUT_POST);
        $this->setBiddingsId(filter_var($value['biddings_id'], FILTER_VALIDATE_INT))
            ->setSuppliersId(filter_var($value['suppliers_id'], FILTER_VALIDATE_INT))
            ->setInvoice(filter_var($value['invoice'], FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDeliveryDate(filter_var($value['delivery_date'], FILTER_SANITIZE_SPECIAL_CHARS))
            ->setObservation(filter_var($value['observation'], FILTER_SANITIZE_SPECIAL_CHARS))
            ->setOmsId(filter_var($oms, FILTER_SANITIZE_SPECIAL_CHARS))
            ->setBiddingsId(filter_var($value['biddings_id'], FILTER_VALIDATE_INT))
            ->setNumber($this->numberGenerator())
            ->setId(filter_var($value['id'] ?? time(), FILTER_VALIDATE_INT));

        $this->validaInvoice($this->getInvoice());
        $this->validaSuppliers();
        $this->validaId();

        if ($this->getBiddingsId()) {
            $this->validaBiddingsId();
            $this->setItemsList($this->buildItemsBiddings($value));
        } else {
            $this->setItemsList($this->buildItemsNotBiddings($value));
        }

        $this->validaItemsList();
    }

    /**
     * Make the itens of Not Biggings requests
     * @param array $values The input values
     * @return array
     */
    private function buildItemsNotBiddings(array $values): array
    {
        $result = [];

        if (isset($values['quantity']) && is_array($values['quantity'])) {
            foreach ($values['quantity'] as $index => $value) {
                $result[] = [
                    'number' => 0,
                    'delivered' => 0,
                    'quantity' => $this->validaQuantity($value),
                    'uf' => $this->validaUf($values['uf'][$index]),
                    'value' => $this->validaValue($values['value'][$index]),
                    'name' => $this->validaName($values['name'][$index]),
                ];
            }
        }

        return $result;
    }

    /**
     * Make the itens of Biggings requests
     * @param array $values The input values
     * @return array
     */
    private function buildItemsBiddings(array $values): array
    {
        $result = [];

        if (isset($values['quantity'], $values['ids']) && is_array($values['quantity'])) {
            foreach ($values['quantity'] as $index => $value) {
                $id = filter_var($values['ids'][$index], FILTER_VALIDATE_INT);
                $quantidade = filter_var(Utils::normalizeFloat($value), FILTER_VALIDATE_FLOAT);

                if ($id && $quantidade) {
                    $result[$id] = $quantidade;
                }
            }
        }

        return $result;
    }

    // Validação
    private function validaId($input = null)
    {
        $value = v::intVal()->validate($input ?? $this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
        }
        return $input ?? $this;
    }

    private function validaSuppliers($input = null)
    {
        $value = v::intVal()->validate($this->getSuppliersId());
        if (!$value) {
            msg::showMsg('É necessário informar um Fornecedor', 'danger');
        }
        return $this;
    }

    private function validaBiddingsId()
    {
        $biddings = new Licitacao();
        // consulta no banco de dados para verificar se a licitação é válida
        $value = $biddings->findById($this->getBiddingsId());
        // verifica se há um retorno na consulta feita acima
        $value = $value['id'] ?? false;
        if (!$value) {
            msg::showMsg('Erro: Não foi possível verificar a licitação.', 'danger');
        }
        return $this;
    }

    private function validaItemsList()
    {
        if (empty($this->getItemsList())) {
            msg::showMsg('Para realizar uma solicitação, é imprescindível'
                . ' fornecer a quantidade de no mínimo um Item.', 'danger');
        }
        return $this;
    }

    private function validaQuantity($value)
    {
        $value = str_replace(",", ".", $value);
        $value = intval($value);
        $validate = v::intVal()->validate($value);
        if ((!$validate) OR ( $value <= 0)) {
            msg::showMsg('O(s) valor(es)  do(s) campo(s) QUANTIDADE deve(m) ser'
                . ' número INTEIRO não negativo e maior que zero', 'danger');
        }
        return $value;
    }

    private function validaUf($value)
    {
        $validate = v::stringType()->notEmpty()->length(2, 5)->validate($value);
        if (!$validate OR is_numeric($value)) {
            msg::showMsg('O(s) valor(es)  do(s) campo(s) UF deve(m) ser'
                . ' preenchido corretamente', 'danger');
        }
        return $value;
    }

    private function validaValue($value)
    {
        $value = str_replace(".", "", $value);
        $value = str_replace(",", ".", $value);

        $validate = v::floatVal()->notEmpty()->validate($value);
        if (!$validate) {
            msg::showMsg('O(s) valor(es)  do(s) campo(s) VALOR deve(m) ser'
                . ' preenchido corretamente', 'danger');
        }
        return $value;
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

    private function validaName($value)
    {
        $validate = v::stringType()->notEmpty()->length(3, 50)->validate($value);
        if (!$validate) {
            msg::showMsg('O(s) valor(es)  do(s) campo(s) DESCRIÇÃO deve(m) ser'
                . ' preenchido corretamente', 'danger');
        }
        return $value;
    }

    private function validaInvoice($value)
    {
        $validate = v::stringType()->notEmpty()->length(3, 20)->validate($value);
        if (!$validate) {
            msg::showMsg('Para realizar o recebimeto é necessário fornecer o número da nota fiscal'
                . '<script>focusOn("invoice");</script>', 'danger');
        }
        return $this;
    }

    private function validaDeliveryDate($value)
    {
        $this->setDeliveryDate($this->abstractDateValidate($value, 'delivery_date', 'Data estipulada para entrega'));
        return $this;
    }

    private function validaFiles()
    {
        $files = $_FILES['files'] ?? false;
        if ($files) {
            foreach ($files['type'] as $type) {
                if ($type != 'application/pdf') {
                    msg::showMsg('Só é permitido o envio de arquivos no formato PDF.', 'danger');
                }
            }
        } else {
            msg::showMsg('Deve ser feito o Upload de pelo menos um arquivo.', 'danger');
        }
    }

    /**
     * Save the files uploaded
     * @param string $directoryReference
     * @param int $solicitationNumber
     */
    private function saveFiles(string $directoryReference, int $solicitationNumber)
    {
        $files = $_FILES['files'] ?? false;
        $fullPath = $directoryReference . cfg::DS . 'arquivos' . cfg::DS . $solicitationNumber . cfg::DS;

        if ($files && $this->createDirectory($fullPath)) {
            foreach ($files["tmp_name"] as $index => $file) {
                $fileDestination = $fullPath . $solicitationNumber . '_' . $index . '.pdf';
                move_uploaded_file($file, $fileDestination);
            }
        } else {
            msg::showMsg('Não foi possível salvar os arquivos informados', 'danger');
        }
    }

    /**
     * Create a new directory
     * @param string $fullPath The full path of directory
     * @return bool
     */
    private function createDirectory(string $fullPath): bool
    {
        if (file_exists($fullPath)) {
            return true;
        }

        return mkdir($fullPath, 0777, true);
    }

    public function saveOneFile(string $directoryReference, int $solicitationNumber)
    {
        $file = $_FILES['arquivo'] ?? false;
        $fullPath = $directoryReference . cfg::DS . 'arquivos' . cfg::DS . $solicitationNumber . cfg::DS;

        if ($file && $file['type'] === 'application/pdf' && $this->createDirectory($fullPath)) {
            $fileDestination = $fullPath . $solicitationNumber . '_' . date('Y-m-d-h-m-i-s') . '.pdf';
            move_uploaded_file($file['tmp_name'], $fileDestination);
            msg::showMsg('Arquivo salvo com sucesso.'
                . '<script>resetForm(); </script>', 'success');
        } else {
            msg::showMsg('Não foi possível salvar o arquivo informado', 'danger');
        }
    }
}
