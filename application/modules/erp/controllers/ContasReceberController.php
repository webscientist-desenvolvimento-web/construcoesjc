<?php

class Erp_ContasReceberController extends Erp_Controller_Action {

    public function init() {
        $this->model = new ContasReceber_Model();
        unset($this->buttons['add']);
        $this->actions['pay'] = 'btPay';
        $this->form = WS_Form_Generator::generateForm('ContasReceber', $this->model->getFormFields());
        parent::init();
    }

    public function formularioAction() {
        $OrcamentoModel = new Orcamentos_Model();
        $ClientesEnderecosModel = new ClientesEnderecos_Model();

        $orcamento_id = $this->_request->getParam('parent_id');

        $id = $this->view->data['id'];
        if (empty($id)):
            $id = $this->_request->getParam('id');
        endif;
        if (!empty($id)):
            $fatura = $this->model->find($id);
            $orcamento_id = $fatura['orcamento_id'];
        endif;

        if (!empty($orcamento_id)):
            $orcamento = $OrcamentoModel->find($orcamento_id);
            $cliente_id = $orcamento['cliente_id'];
            $enderecos = $ClientesEnderecosModel->fetchPair($cliente_id);
            $this->model->_formFields['endereco_id']['options'] = $enderecos;
            $this->form = WS_Form_Generator::generateForm('ContasReceber', $this->model->getFormFields());

            $this->model->_params['orcamento_id'] = $orcamento_id;
        endif;

        parent::formularioAction();
    }

    public function orcamentoAction() {
        $orcamento_id = $this->_request->getParam('parent_id');
        $items = $this->model->buscarPorOrcamento($orcamento_id);

        $this->view->items = $items;
        $this->view->orcamento_id = $orcamento_id;

        $OrdemServicoModel = new OrdensServico_Model();
        $valorTotal = $OrdemServicoModel->somaValores($orcamento_id);
        $this->view->valor_total = $valorTotal['soma'];
    }

    public function clienteAction() {
        $cliente_id = $this->_request->getParam('parent_id');
        $items = $this->model->buscarPorCliente($cliente_id);
        $this->view->items = $items;
    }

    public function pagarAction() {
        $this->model->setFormFieldsPagar();
        $this->form = WS_Form_Generator::generateForm('ContasReceberPagar', $this->model->getFormFields());
        $this->options['linkUpdate'] = '/' . $this->module . '/' . $this->controller . '/pagar/' . $this->_request->getParam('id');
        parent::formularioAction();
        if (!empty($this->view->data['id'])):
            $this->view->data = $this->model->adjustToView($this->model->find($this->view->data['id']));
        endif;
    }

    public function gerarparcelasAction() {
        if ($this->_hasParam('valor')):
            $data = $this->_getAllParams();
            $valorParcela = number_format($data['valor'] / $data['parcelas'], 2);
            $WD = new WS_Date();
            for ($i = 0; $i < $data['parcelas']; $i++):
                $parcela = null;
                $parcela['valor'] = $valorParcela;
                if ($i == 0):
                    $parcela['valor'] = $data['valor'] - ($valorParcela * ($data['parcelas'] - 1));
                endif;
                $WD->add($data['periodicidade'], Zend_Date::DAY);
                $parcela['data'] = $WD->toString('dd/MM/yyyy');
                $parcelas[] = $parcela;
            endfor;
            $this->view->parcelas = $parcelas;
        endif;
    }

    public function salvarparcelasAction() {
        $data = $this->_getAllParams();
        try {
            foreach ($data['parcela'] AS $parcela):
                $parcela['orcamento_id'] = $data['orcamento_id'];
                $parcela['forma_pagamento_id'] = $data['forma_pagamento_id'];
                $parcela['endereco_id'] = $data['endereco_id'];
                $parcela['empresa_id'] = $data['empresa_id'];
                $parcela['observacoes'] = $data['observacoes'];
                $parcela['descricao'] = $data['descricao'];
                $parcela['data_vencimento'] = WS_Date::adjustToDb($parcela['data_vencimento']);
                $parcela['data_lancamento'] = date('Y-m-d');
                $parcela['valor_retido'] = WS_Money::adjustToDb($parcela['valor_retido']);

                $this->model->_db->insert($parcela, $this->model->getOption('messages', 'add'), $this->model->_db->getTableName());
            endforeach;
            $this->alerta('sucess', 'Parcelas criadas com sucesso');
        } catch (Exception $e) {
            $this->alerta('error', 'Erro: ' . $e->getMessage());
        }
        exit();
    }

}