<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use App\Models\ItemModel as Item;
use App\Models\SolicitacaoModel as Solicitacao;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;

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
        foreach ($dados as $key => $val) {
            $val = is_array($val) ? $val['quantity'] : $val;
            $update['delivered'] = $val;
            parent::editar($update, $key);
        }

        return true;
    }

    public function recebimentoNaoLicitado()
    {
        $value = filter_input_array(INPUT_POST);
        for ($i = 0; $i < count($value['id']); $i++) {
            $dados['delivered'] = $value['quantity'][$i];
            parent::editar($dados, $value['id'][$i]);
        }
    }

    public function paginator($pagina, $idLista)
    {
        $dados = [
            'entidade' => $this->entidade,
            'pagina' => $pagina,
            'maxResult' => 50,
            'orderBy' => 'number ASC',
            'where' => 'requests_id = ?',
            'bindValue' => [$idLista]
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

    public function novoRegistro($dados)
    {
        // Seta todos os dados
        $this->setAll($dados);
        $item = new Item();
        foreach ($this->getListaItens() as $idItem => $quantidade) {
            $value = $item->findById($idItem);
            $dados = [
                'requests_id' => $this->getIdLista(),
                'number' => $value['number'],
                'name' => $value['name'],
                'uf' => $value['uf'],
                'quantity' => $quantidade < 0 ? 0 : $quantidade,
                'value' => $value['value']
            ];
            if (!$value['number']) {
                $this->error[] = [
                    'number' => $this->getNumber(),
                    'name' => $this->getNome()
                ];
            } else {
                parent::novo($dados);
            }
        }
        if (empty($this->getError())) {
            return true;
        }
        return $this->getError();
    }

    public function novoNaoLicitado($dados)
    {
        foreach ($dados['lista_itens'] as $value) {
            $value['uf'] = strtoupper($value['uf']);
            $value['value'] = strtoupper($value['value']);
            $value['name'] = strtoupper($value['name']);
            parent::novo($value);
        }
    }

    public function editarRegistro($idLista, $user)
    {
        $this->setQuantity(filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT))
            ->setId(filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT))
            ->validaQuantity()
            ->validaId();

        $item = $this->findById($this->getId());

        $solicitacaoModel = new Solicitacao();
        $solicitacao = $solicitacaoModel->findById_lista($item['requests_id']);

        if ($solicitacao['status'] !== 'ABERTO') {
            // redireciona para solicitacao/ se a Solicitação ja estiver aprovada
            header("Location:" . cfg::DEFAULT_URI . 'solicitacao/');
            return true;
        }
        if ($item['requests_id'] != $idLista) {
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
            'quantity' => $this->getQuantity(),
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
            ->setIdLista($dados['requests_id'])
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
        $value = v::intVal()->notEmpty()->noWhitespace()->validate($this->getQuantity());
        if (!$value) {
            msg::showMsg('O campo Quantidade deve ser preenchido corretamente.'
                . '<script>focusOn("quantity");</script>', 'danger');
        }
        return $this;
    }
}
