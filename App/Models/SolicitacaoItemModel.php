<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use App\Models\ItemModel as Item;
use App\Models\SolicitacaoModel as Solicitacao;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Helpers\Utils;

class SolicitacaoItemModel extends CRUD
{

    protected $entidade = 'requests_items';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function recebimento($dados)
    {
        foreach ($dados as $id => $quantity) {
            parent::editar(['delivered' => $quantity], $id);
        }
    }

    public function recebimentoNaoLicitado()
    {
        $value = filter_input_array(INPUT_POST);

        for ($i = 0; $i < count($value['ids']); $i++) {
            $dados['delivered'] = $value['quantity'][$i];
            parent::editar($dados, $value['ids'][$i]);
        }
    }

    public function paginator($pagina, $idlista)
    {
        $dados = [
            'entidade' => $this->entidade,
            'pagina' => $pagina,
            'maxResult' => 50,
            'orderBy' => 'number ASC',
            'where' => 'requests_id = ?',
            'bindValue' => [$idlista]
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

    public function novoRegistro($dados, $requestsId)
    {
        $item = new Item();
        foreach ($dados as $idItem => $quantity) {
            $value = $item->findById($idItem);
            $dados = [
                'requests_id' => $requestsId,
                'number' => $value['number'],
                'name' => $value['name'],
                'uf' => $value['uf'],
                'quantity' => $quantity,
                'delivered' => 0,
                'value' => $value['value']
            ];
            parent::novo($dados);
        }
    }

    public function novoNaoLicitado($dados, $requestId)
    {
        foreach ($dados as $value) {
            parent::novo([
                'requests_id' => $requestId,
                'name' => $value['name'],
                'uf' => $value['uf'],
                'quantity' => $value['quantity'],
                'delivered' => 0,
                'value' => $value['value']
            ]);
        }
    }

    public function editarRegistro($idlista, $user)
    {
        $quantity = filter_input(INPUT_POST, 'quantity');
        $quantity = Utils::moneyToFloat($quantity);

        $this->setQuantity($quantity)
            ->setId(filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT))
            ->validaQuantity()
            ->validaId();

        $item = $this->findById($this->getId());

        $solicitacaoModel = new Solicitacao();
        $solicitacao = $solicitacaoModel->findById($item['requests_id']);

        if ($solicitacao['status'] !== 'ABERTO') {
            // redireciona para solicitacao/ se a Solicitação ja estiver aprovada
            header("Location:" . cfg::DEFAULT_URI . 'solicitacao/');
            return true;
        }
        if ($item['requests_id'] != $idlista) {
            // não deixa prosseguir se o item pertencer a outra lista
            msg::showMsg('O Item não pode ser alterado.'
                . '<script>focusOn("quantity");</script>', 'danger');
        }
        // redireciona se o usuário tiver nível diferente de 1-Administrador e
        // se a Om da Solicitação for diferente da do usuário
        if ($user['level'] !== 'ADMINISTRADOR') {
            if ($solicitacao['oms_id'] != $user['oms_id']) {
                header("Location:" . cfg::DEFAULT_URI . 'solicitacao/');
                return true;
            }
        }

        $dados = [
            'quantity' => Utils::normalizeFloat($this->getQuantity(), 3),
        ];

        if (parent::editar($dados, $this->getId())) {
            $solicitacaoModel->update($solicitacao['id']);
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->entidade} WHERE requests_id = ? ;");
        $stmt->bindValue(1, $id);
        return $stmt->execute();
    }

    public function eliminarItem($id, $idlitsa)
    {
        $this->db->instrucao('select')
            ->setaEntidade($this->getEntidade())
            ->setaFiltros()
            ->where('requests_id', '=', $idlitsa);
        $numRows = count($this->db->executa('select')->fetchAll(\PDO::FETCH_ASSOC));
        if ($numRows > 1) {
            parent::remover($id);
        }
        header("Location:" . cfg::DEFAULT_URI . "solicitacao/detalhar/idlista/{$idlitsa}");
    }

    public function quantidadeDemanda($itemnumber, $idLicitacao)
    {
        $stmt = $this->pdo->prepare(""
            . " SELECT SUM(`requests_items`.`delivered`) as sum_quantity "
            . " FROM `requests_items` "
            . " INNER JOIN `requests` "
            . "     ON `requests_items`.`requests_id` = `requests`.`id` "
            . " WHERE "
            . "     `requests`.`status` IN ('RECEBIDO', 'NF-ENTREGUE', 'NF-FINANCAS', 'NF-PAGA') "
            . "     AND `requests_items`.`number` = ? "
            . "     AND `requests`.`biddings_id` = ?;");
        $stmt->execute([$itemnumber, $idLicitacao]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['quantity'] : false;
    }

    private function setAll($dados)
    {
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setidlista($dados['requests_id'])
            ->setIdLicitacao($dados['biddings_id'])
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
