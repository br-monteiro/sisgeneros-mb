<?php
/**
 * @Model Solicitacao
 */
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use App\Models\LicitacaoModel as Licitacao;
use App\Models\SolicitacaoItemModel as Itens;
use Respect\Validation\Validator as v;
use App\Models\AvaliacaoFornecedorModel;

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
            if ($sol['id_om'] != $user['om_id']) {
                // caso seja de outra OM, redireciona para histórico de solicitações
                header("location:" . APPDIR . "solicitacao/");
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
        $where = $user['nivel'] !== 'ADMINISTRADOR' ? ' AND om.id = ' . $user['om_id'] : '';
        $where .= !$controllerReceber ? " AND status != 'ABERTO' AND status != 'APROVADO' " : " AND status = 'SOLICITADO' ";
        $stmt = $this->pdo->prepare("SELECT nao_licitado FROM solicitacao WHERE id = ? ");
        $stmt->execute([$id]);
        $s = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($s['nao_licitado'] == 1) {
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
            $value['nao_entregue'] = 0;

            $avalicao->novo($value);
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
            . '</script>', 'success');
    }

    public function paginator($pagina, $user, $busca = null)
    {
        $innerJoin = ""
            . " INNER JOIN om "
            . "    ON om.id = solicitacao.om_id "
            . " INNER JOIN fornecedor "
            . "    ON fornecedor.id = solicitacao.fornecedor_id ";

        $dados = [
            'select' => 'solicitacao.*, fornecedor.nome AS fornecedor_nome, om.indicativo_naval',
            'entidade' => $this->entidade . $innerJoin,
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => 'solicitacao.updated_at DESC'
        ];
        // para usuários com nível de acesso diferente de administrador
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
                . 'fornecedor.nome LIKE :search '
                . 'OR solicitacao.numero LIKE :search '
                . 'OR solicitacao.created_at BETWEEN :dInit AND :dEnd '
                . 'OR solicitacao.updated_at BETWEEN :dInit AND :dEnd '
                . 'OR solicitacao.data_entrega LIKE :search '
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
            'fornecedor_id' => $this->getFornecedorId(),
            'numero' => $this->getNumero(),
            'status' => 'ABERTO',
            'created_at' => time(),
            'updated_at' => time(),
            'nao_licitado' => $this->getNaoLicitado(),
            'data_entrega' => $this->getDataEntrega()
        ];
        if (parent::novo($dados)) {
            $dados['lista_itens'] = $this->getListaItens();
            $itens = new Itens();
            $dados = $itens->novoNaolicitado($dados);
            if ($dados === true) {
                msg::showMsg('Solicitação Registrada com Sucesso!<br>'
                    . "<strong>Solicitação Nº {$this->getNumero()} <br>"
                    . "Status: ABERTO.</strong><br>"
                    . "<a href='" . APPDIR . "solicitacao/detalhar/idlista/{$this->getIdLista()}' class='btn btn-info'>"
                    . '<i class="fa fa-info-circle"></i> Detalhar Solicitação</a>'
                    . '<script>resetForm(); </script>', 'success');
            }
        }
    }

    public function novo($om)
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
            'created_at' => time(),
            'updated_at' => time(),
            'nao_licitado' => $this->getNaoLicitado(),
            'data_entrega' => $this->getDataEntrega()
        ];

        if (parent::novo($dados)) {
            $dados['lista_itens'] = $this->getListaItens();
            $itens = new Itens();
            $dados = $itens->novo($dados);

            if ($dados === true) {
                msg::showMsg('Solicitação Registrada com Sucesso!<br>'
                    . "<strong>Solicitação Nº {$this->getNumero()} <br>"
                    . "Status: ABERTO.</strong><br>"
                    . "<a href='" . APPDIR . "solicitacao/detalhar/idlista/{$this->getIdLista()}' class='btn btn-info'>"
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

    public function remover($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->entidade} WHERE id_lista = ?");
        $stmt->bindValue(1, $id);
        if ($stmt->execute()) {
            header('Location: ' . APPDIR . 'solicitacao/');
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
            header('Location: ' . APPDIR . 'solicitacao/');
        }
    }

    public function avaliaAcesso($idlista, $user)
    {
        $solicitacao = $this->findById_lista($idlista);
        // verifica se a solicitação já foi aprovada
        if ($solicitacao['status'] !== 'ABERTO') {
            header("Location:" . APPDIR . "solicitacao/");
            return true;
            // verifica se o usuário é da mensa OM da solicitação
        } elseif ($user['nivel'] !== 'ADMINISTRADOR') {
            if ($user['om'] != $solicitacao['om']) {
                header("Location:" . APPDIR . "solicitacao/");
                return true;
            }
        }
    }

    public function chart($user)
    {
        $sql = !in_array($user['nivel'], ['ADMINISTRADOR', 'CONTROLADOR']) ? " AND om_id = '{$user['om_id']}'" : null;
        $result = [];
        foreach ($this->arrayDate() as $key) {
            $stmt = $this->pdo->prepare(""
                . "SELECT id FROM {$this->getEntidade()} "
                . "WHERE status = 'RECEBIDO' AND created_at > ? AND created_at < ? " . $sql);
            $stmt->bindValue(1, $key['inicio']);
            $stmt->bindValue(2, $key['fim']);
            $stmt->execute();
            $result[] = count($stmt->fetchAll(\PDO::FETCH_ASSOC));
        }
        return implode(",", $result);
    }

    private function arrayDate()
    {
        $ano = date("Y");
        return [
            ['inicio' => strtotime("01-01-" . $ano), 'fim' => strtotime("31-01-" . $ano)],
            ['inicio' => strtotime("01-02-" . $ano), 'fim' => strtotime("28-02-" . $ano)],
            ['inicio' => strtotime("01-03-" . $ano), 'fim' => strtotime("31-03-" . $ano)],
            ['inicio' => strtotime("01-04-" . $ano), 'fim' => strtotime("30-04-" . $ano)],
            ['inicio' => strtotime("01-05-" . $ano), 'fim' => strtotime("31-05-" . $ano)],
            ['inicio' => strtotime("01-06-" . $ano), 'fim' => strtotime("30-06-" . $ano)],
            ['inicio' => strtotime("01-07-" . $ano), 'fim' => strtotime("31-07-" . $ano)],
            ['inicio' => strtotime("01-08-" . $ano), 'fim' => strtotime("31-08-" . $ano)],
            ['inicio' => strtotime("01-09-" . $ano), 'fim' => strtotime("30-09-" . $ano)],
            ['inicio' => strtotime("01-10-" . $ano), 'fim' => strtotime("31-10-" . $ano)],
            ['inicio' => strtotime("01-11-" . $ano), 'fim' => strtotime("30-11-" . $ano)],
            ['inicio' => strtotime("01-12-" . $ano), 'fim' => strtotime("31-12-" . $ano)]
        ];
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
     * Process the solcitação status. The status allowed
     * is PROCESSADO, EMPENHADO and SOLICITADO
     * @param int $id Identification of Solicitação
     * @param string $status The status to be changing
     */
    public function processarStatus(int $id, string $status)
    {
        /**
         * This array is filled with the index as new status and the
         * value as the current status in the register
         */
        $statusPatterns = [
            'PROCESSADO' => 'APROVADO',
            'EMPENHADO' => 'PROCESSADO',
            'SOLICITADO' => 'EMPENHADO'
        ];
        $currentStatus = $statusPatterns[$status] ?? false;
        $solcitacao = $this->findById($id);

        if (
            $currentStatus !== false &&
            $solcitacao &&
            $solcitacao['status'] === $currentStatus
        ) {
            $dados = [
                'status' => $status,
                'updated_at' => time()
            ];
            parent::editar($dados, $id);
        }
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
            $this->setFornecedorId(filter_var($value['fornecedor'][0], FILTER_VALIDATE_INT));

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
            $valor = $this->validaValor($value['valor'][$i]);
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
    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
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
        $validate = v::date('d-m-Y')->length(10, 10)->validate($value);
        if (!$validate) {
            msg::showMsg('O campo <b>Data estipulada para entrega</b> deve ser preenchido com uma data válida.'
                . '<script>focusOn("data_entrega");</script>', 'danger');
        }
        return $this;
    }
}
