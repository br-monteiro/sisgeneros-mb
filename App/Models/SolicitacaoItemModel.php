<?php
/**
 * @Model SolicitacaoItem
 */
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

    protected $entidade = 'solicitacao_item';
    protected $id;
    protected $idLista;
    protected $quantidade;
    protected $listaItens = [];
    protected $error = [];
    private $resultadoPaginator;
    private $navPaginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function recebimento($dados)
    {
        foreach ($dados as $key => $val) {
            $val = is_array($val) ? $val['item_quantidade'] : $val;
            $update['item_quantidade_atendida'] = $val;
            parent::editar($update, $key);
        }

        return true;
    }

    public function recebimentoNaoLicitado()
    {
        $value = filter_input_array(INPUT_POST);
        for ($i = 0; $i < count($value['id']); $i++) {
            $dados['item_quantidade_atendida'] = $value['quantidade'][$i];
            parent::editar($dados, $value['id'][$i]);
        }
    }

    public function paginator($pagina, $idLista)
    {
        $dados = [
            'entidade' => $this->entidade,
            'pagina' => $pagina,
            'maxResult' => 50,
            'orderBy' => 'item_numero ASC',
            'where' => 'id_lista = ?',
            'bindValue' => [0 => $idLista]
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

    public function novoRegistro($dados)
    {
        // Seta todos os dados
        $this->setAll($dados);
        $item = new Item();
        foreach ($this->getListaItens() as $idItem => $quantidade) {
            $value = $item->findById($idItem);
            $dados = [
                'id_lista' => $this->getIdLista(),
                'item_numero' => $value['numero'],
                'item_nome' => $value['nome'],
                'item_uf' => $value['uf'],
                'item_quantidade' => $quantidade < 0 ? 0 : $quantidade,
                'item_valor' => $value['valor']
            ];
            if (!$value['numero']) {
                $this->error[] = [
                    'item_numero' => $this->getItemNumero(),
                    'item_nome' => $this->getItemNome()
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
            $value['item_uf'] = strtoupper($value['item_uf']);
            $value['item_nome'] = strtoupper($value['item_nome']);
            parent::novo($value);
        }
    }

    public function editarRegistro($idLista, $user)
    {
        $this->setQuantidade(filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT))
            ->setId(filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT))
            ->validaQuantidade()
            ->validaId();

        $item = $this->findById($this->getId());

        $solicitacaoModel = new Solicitacao();
        $solicitacao = $solicitacaoModel->findById_lista($item['id_lista']);

        if ($solicitacao['status'] !== 'ABERTO') {
            // redireciona para solicitacao/ se a Solicitação ja estiver aprovada
            header("Location:" . cfg::DEFAULT_URI . 'solicitacao/');
            return true;
        }
        if ($item['id_lista'] != $idLista) {
            // não deixa prosseguir se o item pertencer a outra lista
            msg::showMsg('O Item não pode ser alterado.'
                . '<script>focusOn("quantidade");</script>', 'danger');
        }
        // redireciona se o usuário tiver nível diferente de 1-Administrador e
        // se a Om da Solicitação for diferente da do usuário
        if ($user['nivel'] !== 'ADMINISTRADOR') {
            if ($solicitacao['om'] != $user['om']) {
                header("Location:" . cfg::DEFAULT_URI . 'solicitacao/');
                return true;
            }
        }

        $dados = [
            'item_quantidade' => $this->getQuantidade(),
        ];

        if (parent::editar($dados, $this->getId())) {
            $solicitacaoModel->update($solicitacao['id']);
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->entidade} WHERE id_lista = ? ;");
        $stmt->bindValue(1, $id);
        return $stmt->execute();
    }

    public function eliminarItem($id, $idlitsa)
    {
        $this->db->instrucao('select')
            ->setaEntidade($this->getEntidade())
            ->setaFiltros()
            ->where('id_lista', '=', $idlitsa);
        $numRows = count($this->db->executa('select')->fetchAll(\PDO::FETCH_ASSOC));
        if ($numRows > 1) {
            parent::remover($id);
        }
        header("Location:" . cfg::DEFAULT_URI . "solicitacao/detalhar/idlista/{$idlitsa}");
    }

    public function quantidadeDemanda($itemNumero, $idLicitacao)
    {
        $stmt = $this->pdo->prepare(""
            . " SELECT SUM(`solicitacao_item`.`item_quantidade_atendida`) as quantidade "
            . " FROM `solicitacao_item` "
            . " INNER JOIN `solicitacao` "
            . "     ON `solicitacao_item`.`id_lista` = `solicitacao`.`id_lista` "
            . " WHERE "
            . "     `solicitacao`.`status` IN ('RECEBIDO', 'NF-ENTREGUE', 'NF-FINANCAS', 'NF-PAGA') "
            . "     AND `solicitacao_item`.`item_numero` = ? "
            . "     AND `solicitacao`.`id_licitacao` = ?;");
        $stmt->execute([$itemNumero, $idLicitacao]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['quantidade'] : false;
    }

    public function atualizaValor(array $itens)
    {
        foreach ($itens as $id => $valor) {
            $dados = ['item_valor' => $valor];
            parent::editar($dados, $id);
        }
    }

    /**
     * Create a new register based on item
     * @param array $item The item to be based
     * @param int $idLista Id of list
     */
    public function novoDesmembrado(array $item, int $idLista)
    {
        $oldItem = $this->findById($item['id']);
        if ($oldItem) {
            $dados = [
                'id_lista' => $idLista,
                'item_numero' => 0,
                'item_nome' => $oldItem['item_nome'],
                'item_uf' => $oldItem['item_uf'],
                'item_quantidade' => $oldItem['item_quantidade'],
                'item_valor' => $item['valor']
            ];
            parent::novo($dados);
        }
    }

    private function setAll($dados)
    {
        // Seta todos os valores
        $this->setId()
            ->setIdLista($dados['id_lista'])
            ->setIdLicitacao($dados['id_licitacao'])
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

    private function validaQuantidade()
    {
        $value = v::intVal()->notEmpty()->noWhitespace()->validate($this->getQuantidade());
        if (!$value) {
            msg::showMsg('O campo Quantidade deve ser preenchido corretamente.'
                . '<script>focusOn("quantidade");</script>', 'danger');
        }
        return $this;
    }
}
