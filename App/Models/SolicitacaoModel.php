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

class SolicitacaoModel extends CRUD
{

    protected $entidade = 'solicitacao';
    protected $id;
    protected $idLista;
    protected $idLicitacao;
    protected $omId;
    protected $numero;
    protected $listaItens = [];
    protected $naoLicitado = 0;
    protected $dataEntrega;
    private $resultadoPaginator;
    private $navPaginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function recuperaDadosRelatorioSolicitacao($idLista)
    {
        $solicitacao = new SolicitacaoModel();
        $solicitacao = $solicitacao->findById_lista($idLista);

        if ($solicitacao['nao_licitado'] == 0) {
            $query = "SELECT solicitacao.*, solicitacao.numero AS numero_solicitacao, licitacao.*,"
                . "om.nome as om_nome,"
                . "fornecedor.nome as fornecedor_nome "
                . "FROM solicitacao "
                . "INNER JOIN om ON om.id = solicitacao.om_id "
                . "INNER JOIN licitacao ON licitacao.id = solicitacao.id_licitacao "
                . "INNER JOIN fornecedor ON fornecedor.id = solicitacao.fornecedor_id "
                . "WHERE solicitacao.id_lista = ?";
        } else {
            $query = "SELECT solicitacao.*, solicitacao.numero AS numero_solicitacao, om.nome as om_nome,"
                . "fornecedor.nome as fornecedor_nome "
                . "FROM solicitacao "
                . "INNER JOIN om ON om.id = solicitacao.om_id "
                . "INNER JOIN fornecedor ON fornecedor.id = solicitacao.fornecedor_id "
                . "WHERE solicitacao.id_lista = ?";
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$idLista]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function alteraDataEntrega($idLista, $user)
    {
        // verifica se o usuário não é adminitrador
        if ($user['nivel'] !== 'ADMINISTRADOR') {
            // colsuta a solicitação por id_lista
            $sol = $this->findById_lista($idLista);
            // verifica se a solicitação é da mesma OM que o usuário lodado
            if ($sol['om_id'] != $user['om_id']) {
                // caso seja de outra OM, redireciona para histórico de solicitações
                header("location:" . cfg::DEFAULT_URI . "solicitacao/");
            }
        }

        $this->setId()
            ->setDataEntrega(filter_input(INPUT_POST, 'data_entrega', FILTER_SANITIZE_SPECIAL_CHARS));

        $dataEntrega = $this->getDataEntrega();
        $id = $this->getId();

        $this->validaDataEntrega($dataEntrega);

        $dados = [
            'data_entrega' => $dataEntrega
        ];

        if (parent::editar($dados, $id)) {
            msg::showMsg('Data de entrega alterada com sucesso.', 'success');
        }
    }

    public function retornaDadosPapeleta($id, $user = null, $controllerReceber = false)
    {
        $where = '';
        if (isset($user['nivel']) && $user['nivel'] !== 'ADMINISTRADOR') {
            $where = ' AND om.id = ' . $user['om_id'];
        }
        if (!$controllerReceber) {
            $where .= " AND status != 'ABERTO' AND status != 'APROVADO' ";
        } else {
            $where .= " AND status = 'SOLICITADO' ";
        }
        $stmt = $this->pdo->prepare("SELECT nao_licitado FROM solicitacao WHERE id = ? ");
        $stmt->execute([$id]);
        $solicitacao = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($solicitacao['nao_licitado'] == 1) {
            $query = "SELECT 
                sol.*,
                sol.id as solicitacao_id,
                om.*,
                item.*,
                sol.updated_at as alteracao,
                fornecedor.nome AS fornecedor_nome,
                fornecedor.cnpj
            FROM solicitacao AS sol
            INNER JOIN om
               ON om.id = sol.om_id
            INNER JOIN solicitacao_item as item
               ON item.id_lista = sol.id_lista
            INNER JOIN fornecedor
               ON fornecedor.id = sol.fornecedor_id
            WHERE sol.id = ? {$where}";
        } else {
            $query = "SELECT
                sol.*,
                sol.id as solicitacao_id,
                om.*,
                item.id as item_id,
                item.*,
                sol.updated_at as alteracao,
                fornecedor.nome AS fornecedor_nome,
                fornecedor.cnpj,
                licitacao.numero AS licitacao_numero
            FROM solicitacao AS sol
            INNER JOIN solicitacao_item AS item
               ON item.id_lista = sol.id_lista
            INNER JOIN om
               ON om.id = sol.om_id
            INNER JOIN fornecedor
               ON fornecedor.id = sol.fornecedor_id
           INNER JOIN licitacao
               ON licitacao.id = sol.id_licitacao
            WHERE sol.id = ? {$where}";
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function recebimento($id, $user)
    {
        // Valida dados
        $this->validaAll($user['om_id']);
        $dados = [
            'status' => 'RECEBIDO',
            'updated_at' => time(),
            'numero_nota_fiscal' => $this->getNumeroNotaFiscal(),
            'observacao' => $this->getObservacao()
        ];
        if (parent::editar($dados, $id)) {
            $dados['lista_itens'] = $this->getListaItens();
            $itens = new Itens();
            $dados = $itens->recebimento($dados['lista_itens']);

            /// seta a nota para a entrega
            $solicitacao = $this->findById($id);
            $avalicao = new AvaliacaoFornecedorModel();
            $value['nota'] = filter_input(INPUT_POST, 'nota', FILTER_VALIDATE_INT) ?: 1;
            $value['licitacao_id'] = $solicitacao['id_licitacao'];
            $value['fornecedor_id'] = $solicitacao['fornecedor_id'];
            $value['solicitacao_id'] = $solicitacao['id'];
            $value['nao_entregue'] = 0;

            $avalicao->novoRegistro($value);
            if ($dados === true) {
                msg::showMsg('Operação efetuada com sucesso!'
                    . '<script>'
                    . 'mostrar("btn_voltar");'
                    . 'ocultar("tabela_result");'
                    . 'ocultar("btn_enviar");'
                    . 'ocultar("numero_nota_fiscal_container");'
                    . 'ocultar("observacao");'
                    . 'ocultar("nota");'
                    . 'ocultar("legenda");'
                    . '</script>', 'success');
            } else {
                $error = [];
                foreach ($dados as $value) {
                    $error[] = "Iten Nº " . $value['item_numero'] . " - " . $value['item_nome'] . "<br>";
                }
                msg::showMsg('Houve erro ao gravar os seguintes itens:<br>'
                    . implode('', $error)
                    . '<script>resetForm(); </script>', 'danger');
            }
        }
    }

    public function recbimentoNaoLicitado($id, $idLista)
    {
        $this->setNumeroNotaFiscal(filter_input(INPUT_POST, 'numero_nota_fiscal'));
        $this->validaNumeroNotaFiscal($this->getNumeroNotaFiscal());

        $this->setObservacao(filter_input(INPUT_POST, 'observacao', FILTER_SANITIZE_SPECIAL_CHARS));

        $dados = [
            'status' => 'RECEBIDO',
            'updated_at' => time(),
            'numero_nota_fiscal' => $this->getNumeroNotaFiscal(),
            'observacao' => $this->getObservacao()
        ];
        parent::editar($dados, $id);

        $itens = new Itens();
        $itens->recebimentoNaoLicitado($idLista);
        msg::showMsg('Operação efetuada com sucesso!'
            . '<script>'
            . 'mostrar("btn_voltar");'
            . 'ocultar("tabela_result");'
            . 'ocultar("btn_enviar");'
            . 'ocultar("numero_nota_fiscal_container");'
            . 'ocultar("observacao");'
            . 'ocultar("nota");'
            . 'ocultar("legenda");'
            . '</script>', 'success');
    }

    public function paginator($pagina, $user, $busca = null, $om = null, $dtInicio = null, $dtFim = null)
    {
        $innerJoin = " AS sol INNER JOIN om ON om.id = sol.om_id ";
        $subQuery = ' (SELECT nome FROM fornecedor AS f WHERE f.id = sol.fornecedor_id) as fornecedor_nome ';

        $dados = [
            'select' => 'sol.*, ' . $subQuery . ', om.indicativo_naval',
            'entidade' => $this->entidade . $innerJoin,
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => 'sol.updated_at DESC'
        ];

        if (!in_array($user['nivel'], ['ADMINISTRADOR', 'CONTROLADOR'])) {
            $dados['where'] = 'om_id = :omId ';
            $dados['bindValue'] = [':omId' => $user['om_id']];
        }

        if ($user['nivel'] === 'CONTROLADOR') {
            $dados['where'] = 'status != :status ';
            $dados['bindValue'] = [':status' => 'ABERTO'];
        }

        if ($busca) {
            $dateInit = $dateEnd = $busca;

            if (preg_match('/\d{2}-\d{2}-\d{4}/', $busca)) {
                $exDate = explode('-', $busca);
                $exDate = array_reverse($exDate);
                $exDate = implode('-', $exDate);
                $exDate .= 'T00:00:00+00:00';

                $date = new \DateTime($exDate);
                $dateInit = $date->getTimestamp();
                $date->modify('+23 hour');
                $dateEnd = $date->getTimestamp();
            }

            $andExists = isset($dados['where']) ? 'AND' : '';
            $dados['where'] = ($dados['where'] ?? "") . " {$andExists} ( "
                . 'sol.status LIKE :search '
                . 'OR sol.numero LIKE :search '
                . 'OR sol.created_at BETWEEN :dInit AND :dEnd '
                . 'OR sol.updated_at BETWEEN :dInit AND :dEnd '
                . 'OR sol.data_entrega LIKE :search '
                . ') ';

            $bindValue = [
                ':search' => '%' . $busca . '%',
                ':dInit' => $dateInit,
                ':dEnd' => $dateEnd
            ];
            $dados['bindValue'] = $dados['bindValue'] ?? [];
            $dados['bindValue'] = array_merge($dados['bindValue'], $bindValue);
        }

        $paginator = new Paginator($dados);
        $this->resultadoPaginator = $paginator->getResultado();
        $this->navPaginator = $paginator->getNaveBtn();
    }

    public function paginatorSolicitacoes(ControllerAbstract $controller)
    {
        $select = ""
            . " sol.numero AS solicitacao_numero, "
            . " om.indicativo_naval, sol.numero_nota_fiscal AS nota_fiscal, "
            . " sol.nao_licitado, sol.data_entrega, "
            . " sol.status AS solicitacao_status ";
        $innerJoin = ""
            . " AS sol "
            . " INNER JOIN om ON om.id = sol.om_id ";
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
        if (isset($params['om']) && intval($params['om']) !== 0) {
            $dados['where'] = ' om.id = :omId ';
            $dados['bindValue'][':omId'] = $params['om'];
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
            $exDate = explode('-', $params['dateInit']);
            $exDate = array_reverse($exDate);
            $exDate = implode('-', $exDate);
            $exDate .= 'T00:00:00+00:00';
            $date = new \DateTime($exDate);
            if (isset($dados['where'])) {
                $dados['where'] .= ' AND sol.created_at >= :dateInit ';
            } else {
                $dados['where'] = ' sol.created_at >= :dateInit ';
            }
            $dados['bindValue'][':dateInit'] = $date->getTimestamp();
        }
        // search by Date End
        if (isset($params['dateEnd']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateEnd'])) {
            $exDate = explode('-', $params['dateEnd']);
            $exDate = array_reverse($exDate);
            $exDate = implode('-', $exDate);
            $exDate .= 'T23:59:00+00:00';
            $date = new \DateTime($exDate);
            if (isset($dados['where'])) {
                $dados['where'] .= ' AND sol.created_at <= :dateEnd ';
            } else {
                $dados['where'] = ' sol.created_at <= :dateEnd ';
            }
            $dados['bindValue'][':dateEnd'] = $date->getTimestamp();
        }
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

    public function novoNaoLicitado($om)
    {
        $this->validaAll($om);
        $this->validaDataEntrega($this->getDataEntrega());
        $dados = [
            'id_licitacao' => $this->getIdLicitacao(),
            'id_lista' => $this->getIdLista(),
            'om_id' => $this->getOmId(),
            'fornecedor_id' => 0,
            'numero' => $this->getNumero(),
            'status' => 'ABERTO',
            'ano' => date('Y'),
            'created_at' => time(),
            'updated_at' => time(),
            'nao_licitado' => 1,
            'data_entrega' => $this->getDataEntrega()
        ];
        if (parent::novo($dados)) {
            $dados['lista_itens'] = $this->getListaItens();
            (new Itens())->novoNaoLicitado($dados);
            msg::showMsg('Solicitação Registrada com Sucesso!<br>'
                . "<strong>Solicitação Nº {$this->getNumero()} <br>"
                . "Status: ABERTO.</strong><br>"
                . "<a href='" . cfg::DEFAULT_URI . "solicitacao/detalhar/idlista/{$this->getIdLista()}' class='btn btn-info'>"
                . '<i class="fa fa-info-circle"></i> Detalhar Solicitação</a>'
                . '<script>resetForm(); </script>', 'success');
        }
    }

    public function novoRegistro($om)
    {
        // Valida dados
        $this->validaAll($om);
        $this->validaDataEntrega($this->getDataEntrega());

        $dados = [
            'id_licitacao' => $this->getIdLicitacao(),
            'id_lista' => $this->getIdLista(),
            'om_id' => $this->getOmId(),
            'fornecedor_id' => $this->getFornecedorId(),
            'numero' => $this->getNumero(),
            'status' => 'ABERTO',
            'ano' => date('Y'),
            'created_at' => time(),
            'updated_at' => time(),
            'nao_licitado' => $this->getNaoLicitado(),
            'data_entrega' => $this->getDataEntrega()
        ];

        if (parent::novo($dados)) {
            $dados['lista_itens'] = $this->getListaItens();
            $itens = new Itens();
            $dados = $itens->novoRegistro($dados);

            if ($dados === true) {
                msg::showMsg('Solicitação Registrada com Sucesso!<br>'
                    . "<strong>Solicitação Nº {$this->getNumero()} <br>"
                    . "Status: ABERTO.</strong><br>"
                    . "<a href='" . cfg::DEFAULT_URI . "solicitacao/detalhar/idlista/{$this->getIdLista()}' class='btn btn-info'>"
                    . '<i class="fa fa-info-circle"></i> Detalhar Solicitação</a>'
                    . '<script>resetForm(); </script>', 'success');
            }

            $error = [];

            foreach ($dados as $value) {
                $error[] = "Iten Nº " . $value['item_numero'] . " - " . $value['item_nome'] . "<br>";
            }

            msg::showMsg('Houve erro ao gravar os seguintes itens:<br>'
                . implode('', $error), 'danger');
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
            'updated_at' => time()
        ];

        return parent::editar($dados, $id);
    }

    public function aprovar($id)
    {
        $dados = [
            'status' => 'APROVADO',
            'updated_at' => time()
        ];

        if (parent::editar($dados, $id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'solicitacao/');
        }
    }

    public function avaliaAcesso($idlista, $user)
    {
        $solicitacao = $this->findById_lista($idlista);
        // verifica se a solicitação já foi aprovada
        if ($solicitacao['status'] !== 'ABERTO') {
            header("Location:" . cfg::DEFAULT_URI . "solicitacao/");
            return true;
            // verifica se o usuário é da mensa OM da solicitação
        } elseif ($user['nivel'] !== 'ADMINISTRADOR') {
            if ($user['om'] != $solicitacao['om']) {
                header("Location:" . cfg::DEFAULT_URI . "solicitacao/");
                return true;
            }
        }
    }

    /**
     * Generate the number of Solicitação
     * @return int The solictação number
     */
    protected function numberGenerator(int $number = 0): int
    {
        if ($number > 0) {
            $hasEqualsRegister = $this->pdo
                ->query("SELECT id FROM solicitacao WHERE numero = {$number}")
                ->fetch(\PDO::FETCH_OBJ);

            // If exists a register with this number, try with the number plus one
            if ($hasEqualsRegister) {
                return $this->numberGenerator($number + 1);
            }

            return $number;
        }

        $currentYear = date('Y');
        $currentYearShort = date('y');
        $query = "SELECT COUNT(id) as quantity FROM solicitacao WHERE ano = '{$currentYear}'";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $registersQuantity = $stmt->fetch(\PDO::FETCH_OBJ)->quantity;
        $number = (int) $currentYearShort . ($registersQuantity + 1);
        // check if in the exact moment exists a register with this number
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
        $statusPatterns = [
            'ABERTO' => 'APROVADO',
            'APROVADO' => 'PROCESSADO',
            'PROCESSADO' => 'EMPENHADO',
            'EMPENHADO' => 'SOLICITADO',
            'SOLICITADO' => 'RECEBIDO',
            'RECEBIDO' => 'NF-ENTREGUE',
            'NF-ENTREGUE' => 'NF-FINANCAS',
            'NF-FINANCAS' => 'NF-PAGA'
        ];
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
                    'updated_at' => time()
                ];
            } elseif (
                $previousStatus !== false &&
                $solcitacao &&
                $solcitacao['status'] === $status &&
                $action === 'ANTERIOR'
            ) {
                $dados = [
                    'status' => $previousStatus,
                    'updated_at' => time()
                ];
            }

            if (count($dados) > 0) {
                parent::editar($dados, $id);
            }
        }
    }

    /**
     * Select the Solicitation by Id Lista Field
     * @param int $idLista
     * @return array
     */
    public function findByIdLista($idLista)
    {
        $subQueryName = ' (SELECT nome FROM fornecedor AS f WHERE f.id = sol.fornecedor_id) as fornecedor_nome ';
        $subQueryCnpj = ' (SELECT cnpj FROM fornecedor AS f WHERE f.id = sol.fornecedor_id) as fornecedor_cnpj ';
        $query = ""
            . "SELECT "
            . "sol.*, {$subQueryName}, {$subQueryCnpj} "
            . "FROM {$this->entidade} AS sol "
            . "WHERE sol.id_lista = :idLista";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':idLista' => $idLista]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findQtdSolicitByStatus($user, $status = 'ABERTO')
    {
        $query = ""
            . "SELECT "
            . "COUNT(*) quantidade "
            . "FROM {$this->entidade} "
            . "WHERE status LIKE :status";

        if (!in_array($user['nivel'], ['ADMINISTRADOR', 'CONTROLADOR'])) {
            $where = " AND om_id = {$user['om_id']} ";
            $query . $where;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':status' => $status]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findQtdSolicitAtrasadas($user, $status = 'SOLICITADO')
    {

        $query = ""
            . "SELECT "
            . "COUNT(*) quantidade "
            . "FROM {$this->entidade} "
            . "WHERE status LIKE :status AND data_entrega < date('now')";

        if (!in_array($user['nivel'], ['ADMINISTRADOR', 'CONTROLADOR'])) {
            $where = " AND om_id = {$user['om_id']} ";
            $query . $where;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':status' => $status]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findSolitacoesMensal($user)
    {
        $mesPassado = date(strtotime("- 1 month", time()));
        $hoje = time();

        $query = ""
            . "SELECT "
            . "COUNT(*) quantidade "
            . "FROM {$this->entidade} "
            . "WHERE updated_at BETWEEN :dInit AND :dEnd";

        if (!in_array($user['nivel'], ['ADMINISTRADOR', 'CONTROLADOR'])) {
            $where = " AND om_id = {$user['om_id']} ";
            $query . $where;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([":dInit" => $mesPassado, ":dEnd" => $hoje]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Process the solicitations of not biddings
     * @param int $id The solicitacao ID
     */
    public function processNotBiddings(int $id)
    {
        $solicitacao = $this->findById($id);
        // only for status APROVADO
        if (isset($solicitacao['id'], $solicitacao['status']) && $solicitacao['status'] === 'APROVADO') {
            if ($this->isDesmembrado()) {
                $fornecedores = $this->validaFornecedores();
                $idLista = time();
                $newSolicitations = [];
                foreach ($fornecedores as $itens) {
                    $idLista = $idLista + 1; // just create a new list
                    $numero = $this->numberGenerator();
                    $newSolicitations[] = $numero; // save temporary the number
                    $dados = [
                        'id_licitacao' => 0,
                        'id_lista' => $idLista,
                        'om_id' => $solicitacao['om_id'],
                        'fornecedor_id' => $itens[0]['fornecedor_id'],
                        'numero' => $numero,
                        'status' => 'PROCESSADO',
                        'ano' => date('Y'),
                        'created_at' => time(),
                        'updated_at' => time(),
                        'nao_licitado' => 1,
                        'data_entrega' => $solicitacao['data_entrega']
                    ];
                    // create a new solicitation
                    parent::novo($dados);
                    // added new item into solicitation
                    foreach ($itens as $item) {
                        (new Itens())->novoDesmembrado($item, $idLista);
                    }
                }

                if (count($fornecedores)) {
                    $dados = [
                        'updated_at' => time(),
                        'status' => 'DESMEMBRADO',
                        'lista_desmembramento' => implode(' - ', $newSolicitations)
                    ];
                    parent::editar($dados, $id);

                    msg::showMsg('A solicitação foi desmembrada com sucesso.<br>'
                        . 'As novas solicitações originadas foram: <strong>' . $dados['lista_desmembramento']
                        . '</strong><br>Você será redirecionado para a página de Histórico de Solicitações em 5 segundos.'
                        . '<meta http-equiv="refresh" content="5;URL=' . cfg::DEFAULT_URI . 'solicitacao/ver" />'
                        . '<script>'
                        . 'setTimeout(function(){ window.location = "' . cfg::DEFAULT_URI . 'solicitacao/ver"; }, 5000);'
                        . 'freezeForm();'
                        . '</script>', 'success');
                }
                // for solicitations without 'desmembramento'
            } else {
                $dados = [
                    'updated_at' => time(),
                    'status' => 'PROCESSADO',
                    'fornecedor_id' => $this->validaFornecedorId()
                ];
                parent::editar($dados, $id);
                (new Itens())->atualizaValor($this->buildItens());

                msg::showMsg('A solicitação foi processada com sucesso.<br>'
                    . '<br>Você será redirecionado para a página de Histórico de Solicitações em 5 segundos.'
                    . '<meta http-equiv="refresh" content="5;URL=' . cfg::DEFAULT_URI . 'solicitacao/ver" />'
                    . '<script>'
                    . 'setTimeout(function(){ window.location = "' . cfg::DEFAULT_URI . 'solicitacao/ver"; }, 5000);'
                    . 'freezeForm();'
                    . '</script>', 'success');
            }
        } else {
            /**
             * This script is necessary to redirect the user using Ajax
             */
            echo ''
            . '<meta http-equiv="refresh" content="0;URL=' . cfg::DEFAULT_URI . 'solicitacao/ver" />'
            . '<script>'
            . 'setTimeout(function(){ window.location = "' . cfg::DEFAULT_URI . 'solicitacao/ver"; }, 1);'
            . '</script>';
        }
    }

    /**
     * Check if the solicitation is 'desmembrado'
     * @return bool
     */
    private function isDesmembrado(): bool
    {
        return filter_input(INPUT_POST, 'isDesmembrado', FILTER_VALIDATE_INT) === 1;
    }

    /**
     * Check if the fornecedor ID is an integer
     * @param mixed $value The fornecedor ID
     * @return int
     */
    private function validaFornecedorId($value = null): int
    {
        $value = $value ?? filter_input(INPUT_POST, 'fornecedor');
        if (!v::intVal()->validate($value)) {
            msg::showMsg('Erro: Não foi possível verificar a licitação.', 'danger');
        }
        return $value;
    }

    /**
     * Check if the item ID is an integer
     * @param mixed $value
     * @return int
     */
    private function validaItensId($value): int
    {
        if (!v::intVal()->validate($value)) {
            msg::showMsg('Erro: Não foi possível verificar a licitação.', 'danger');
        }
        return $value;
    }

    /**
     * Build the values (R$) according item id
     * @return array
     */
    private function buildItens(): array
    {
        $result = [];
        $requestPost = filter_input_array(INPUT_POST);
        $itens = is_array($requestPost['id'] ?? null) ? $requestPost['id'] : [];

        foreach ($itens as $index => $value) {
            $id = $this->validaItensId($value);
            $valor = $this->validaValor($requestPost['valor'][$index] ?? null);
            $result[$id] = $valor;
        }

        return $result;
    }

    /**
     * Build the itens values according fornecedor ID
     * @return array
     */
    private function validaFornecedores(): array
    {
        $result = [];
        $requestPost = filter_input_array(INPUT_POST);
        $fornecedores = is_array($requestPost['arrFornecedor'] ?? null) ? $requestPost['arrFornecedor'] : [];

        foreach ($fornecedores as $index => $value) {
            $result[$value][] = [
                'fornecedor_id' => $this->validaFornecedorId($value),
                'valor' => $this->validaValor($requestPost['valor'][$index] ?? ''),
                'id' => $this->validaId($requestPost['id'][$index] ?? '')
            ];
        }
        return $result;
    }

    /**
     * Fetch the last updated solicitation
     * @param array $user The user logged in
     * @return array
     */
    public function lastUpdated(array $user): array
    {
        $where = '';
        if (!in_array($user['nivel'], ['ADMINISTRADOR', 'CONTROLADOR'])) {
            $where = " WHERE sol.om_id = " . $user['om_id'];
        }
        $query = ""
            . " SELECT "
            . "     sol.numero, "
            . "     sol.status, "
            . "     sol.updated_at, "
            . "     om.indicativo_naval"
            . " FROM {$this->entidade} AS sol "
            . " INNER JOIN om ON om.id = sol.om_id "
            . " {$where} "
            . " ORDER BY "
            . "     sol.updated_at DESC "
            . " LIMIT 10";
        return $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function validaAll($om)
    {
        $value = filter_input_array(INPUT_POST);
        $this->setNaoLicitado(filter_var($value['nao_licitado'], FILTER_VALIDATE_INT))
            ->setNumeroNotaFiscal(filter_var($value['numero_nota_fiscal'], FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDataEntrega(filter_var($value['data_entrega'], FILTER_SANITIZE_SPECIAL_CHARS))
            ->setObservacao(filter_var($value['observacao'], FILTER_SANITIZE_SPECIAL_CHARS))
            ->setId()
            ->setIdLista()
            ->setOmId(filter_var($om, FILTER_SANITIZE_SPECIAL_CHARS))
            ->setIdLicitacao(filter_var($value['id_licitacao'], FILTER_VALIDATE_INT))
            ->setNumero($this->numberGenerator());

        $this->validaNumeroNotaFiscal($this->getNumeroNotaFiscal());

        for ($i = 0; $i < count($value['quantidade']); $i++) {
            $id = filter_var($value['id'][$i], FILTER_VALIDATE_INT);
            $quantidade = str_replace(",", ".", $value['quantidade'][$i]);
            $quantidade = filter_var($quantidade, FILTER_VALIDATE_FLOAT);
            $this->setFornecedorId(filter_var($value['fornecedor'][0] ?? null, FILTER_VALIDATE_INT));

            if ($id AND $quantidade) {
                $this->listaItens[$id] = $quantidade;
            }
        }

        if (!$this->getNaoLicitado()) {
            $this->validaId()
                ->validaNaoLicitado()
                ->validaListaItens()
                ->validaIdLicitacao();
            return $this;
        }

        for ($i = 0; $i < count($value['quantidade']); $i++) {
            $quantidade = $this->validaQuantidade($value['quantidade'][$i]);
            $uf = $this->validaUf($value['uf'][$i]);
            $valor = 0;
            if (!$this->getNaoLicitado()) {
                $valor = $this->validaValor($value['valor'][$i]);
            }
            $nome = $this->validaNome($value['nome'][$i]);
            $this->listaItens[] = [
                'id_lista' => $this->getIdLista(),
                'item_numero' => 0,
                'item_quantidade' => $quantidade,
                'item_quantidade_atendida' => 0,
                'item_uf' => $uf,
                'item_valor' => $valor,
                'item_nome' => $nome,
            ];
        }
    }

    private function setId()
    {
        $value = filter_input(INPUT_POST, 'id');
        $this->id = $value ?: time();
        return $this;
    }

    private function setIdLista()
    {
        $value = filter_input(INPUT_POST, 'id_lista');
        $this->idLista = $value ?: time();
        return $this;
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

    private function validaIdLicitacao()
    {
        $licitacao = new Licitacao();
        // consulta no banco de dados para verificar se a licitação é válida
        $value = $licitacao->findById($this->getIdLicitacao());
        // verifica se há um retorno na consulta feita acima
        $value = $value['id'] ?: 'inválido';
        $value = v::intVal()->validate($value);
        if (!$value) {
            msg::showMsg('Erro: Não foi possível verificar a licitação.', 'danger');
        }
        return $this;
    }

    private function validaNaoLicitado()
    {
        $value = v::intVal()->validate($this->getNaoLicitado());
        if (!$value) {
            msg::showMsg('Erro: Não foi possível verificar a licitação.', 'danger');
        }
        return $this;
    }

    private function validaListaItens()
    {
        if (empty($this->getListaItens())) {
            msg::showMsg('Para realizar uma solicitação, é imprescindível'
                . ' fornecer a quantidade de no mínimo um Item.', 'danger');
        }
        return $this;
    }

    private function validaQuantidade($value)
    {
        $value = str_replace(",", ".", $value);
        $validate = v::floatVal()->validate($value);
        if ((!$validate) OR ( $value < 0)) {
            msg::showMsg('O(s) valor(es)  do(s) campo(s) QUANTIDADE deve(m) ser'
                . ' número INTEIRO não negativo', 'danger');
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

    private function validaValor($value)
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

    private function validaNome($value)
    {
        $validate = v::stringType()->notEmpty()->length(3, 50)->validate($value);
        if (!$validate) {
            msg::showMsg('O(s) valor(es)  do(s) campo(s) DESCRIÇÃO deve(m) ser'
                . ' preenchido corretamente', 'danger');
        }
        return $value;
    }

    private function validaNumeroNotaFiscal($value)
    {
        $validate = v::stringType()->notEmpty()->length(3, 20)->validate($value);
        if (!$validate) {
            msg::showMsg('Para realizar o recebimeto é necessário fornecer o número da nota fiscal'
                . '<script>focusOn("numero_nota_fiscal");</script>', 'danger');
        }
        return $this;
    }

    private function validaDataEntrega($value)
    {
        $this->setDataEntrega($this->abstractDateValidate($value, 'data_entrega', 'Data estipulada para entrega'));
        return $this;
    }
}
