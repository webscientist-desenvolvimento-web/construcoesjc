<?php

class ERP_BoletoSicrediController extends Zend_Controller_Action {

    public function init() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $id = $this->_request->getParam('id');
        $action = $this->_request->getActionName();
        if ($action != 'imprimir'):
            if (!empty($id)):
                $this->id = base64_decode($id);
            else:
                echo 'Informe o código!';
                exit();
            endif;
        endif;

        function digitoVerificador_nossonumero($numero) {
            $resto2 = modulo_11($numero, 9, 1);
            // esta rotina sofrer algumas altera��es para ajustar no layout do SICREDI
            $digito = 11 - $resto2;
            if ($digito > 9) {
                $dv = 0;
            } else {
                $dv = $digito;
            }
            return $dv;
        }

        function digitoVerificador_campolivre($numero) {
            $resto2 = modulo_11($numero, 9, 1);
// esta rotina sofreu algumas altera��es para ajustar no layout do SICREDI
            if ($resto2 <= 1) {
                $dv = 0;
            } else {
                $dv = 11 - $resto2;
            }
            return $dv;
        }

        function digitoVerificador_barra($numero) {
            $resto2 = modulo_11($numero, 9, 1);
// esta rotina sofrer algumas altera��es para ajustar no layout do SICREDI
            $digito = 11 - $resto2;
            if ($digito <= 1 || $digito >= 10) {
                $dv = 1;
            } else {
                $dv = $digito;
            }
            return $dv;
        }

// FUN��ES
// Algumas foram retiradas do Projeto PhpBoleto e modificadas para atender as particularidades de cada banco

        function formata_numero($numero, $loop, $insert, $tipo = "geral") {
            if ($tipo == "geral") {
                $numero = str_replace(",", "", $numero);
                while (strlen($numero) < $loop) {
                    $numero = $insert . $numero;
                }
            }
            if ($tipo == "valor") {
                /*
                  retira as virgulas
                  formata o numero
                  preenche com zeros
                 */
                $numero = str_replace(",", "", $numero);
                while (strlen($numero) < $loop) {
                    $numero = $insert . $numero;
                }
            }
            if ($tipo == "convenio") {
                while (strlen($numero) < $loop) {
                    $numero = $numero . $insert;
                }
            }
            return $numero;
        }

        function fbarcode($valor) {

            $fino = 1;
            $largo = 3;
            $altura = 50;

            $barcodes[0] = "00110";
            $barcodes[1] = "10001";
            $barcodes[2] = "01001";
            $barcodes[3] = "11000";
            $barcodes[4] = "00101";
            $barcodes[5] = "10100";
            $barcodes[6] = "01100";
            $barcodes[7] = "00011";
            $barcodes[8] = "10010";
            $barcodes[9] = "01010";
            for ($f1 = 9; $f1 >= 0; $f1--) {
                for ($f2 = 9; $f2 >= 0; $f2--) {
                    $f = ($f1 * 10) + $f2;
                    $texto = "";
                    for ($i = 1; $i < 6; $i++) {
                        $texto .= substr($barcodes[$f1], ($i - 1), 1) . substr($barcodes[$f2], ($i - 1), 1);
                    }
                    $barcodes[$f] = $texto;
                }
            }


//Desenho da barra
//Guarda inicial
            ?><img src=/images/boletos/p.png width=<?php echo $fino ?> height=<?php echo $altura ?> border=0><img
                src=/images/boletos/b.png width=<?php echo $fino ?> height=<?php echo $altura ?> border=0><img
                src=/images/boletos/p.png width=<?php echo $fino ?> height=<?php echo $altura ?> border=0><img
                src=/images/boletos/b.png width=<?php echo $fino ?> height=<?php echo $altura ?> border=0><img
                <?php
                $texto = $valor;
                if ((strlen($texto) % 2) <> 0) {
                    $texto = "0" . $texto;
                }

// Draw dos dados
                while (strlen($texto) > 0) {
                    $i = round(esquerda($texto, 2));
                    $texto = direita($texto, strlen($texto) - 2);
                    $f = $barcodes[$i];
                    for ($i = 1; $i < 11; $i+=2) {
                        if (substr($f, ($i - 1), 1) == "0") {
                            $f1 = $fino;
                        } else {
                            $f1 = $largo;
                        }
                        ?>
                        src=/images/boletos/p.png width=<?php echo $f1 ?> height=<?php echo $altura ?> border=0><img
                        <?php
                        if (substr($f, $i, 1) == "0") {
                            $f2 = $fino;
                        } else {
                            $f2 = $largo;
                        }
                        ?>
                        src=/images/boletos/b.png width=<?php echo $f2 ?> height=<?php echo $altura ?> border=0><img
                        <?php
                    }
                }

// Draw guarda final
                ?>
                src=/images/boletos/p.png width=<?php echo $largo ?> height=<?php echo $altura ?> border=0><img
                src=/images/boletos/b.png width=<?php echo $fino ?> height=<?php echo $altura ?> border=0><img
                src=/images/boletos/p.png width=<?php echo 1 ?> height=<?php echo $altura ?> border=0>
            <?php
        }

//Fim da fun��o

        function esquerda($entra, $comp) {
            return substr($entra, 0, $comp);
        }

        function direita($entra, $comp) {
            return substr($entra, strlen($entra) - $comp, $comp);
        }

        function fator_vencimento($data) {
            $data = explode("/", $data);
            $ano = $data[2];
            $mes = $data[1];
            $dia = $data[0];
            return(abs((_dateToDays("1997", "10", "07")) - (_dateToDays($ano, $mes, $dia))));
        }

        function _dateToDays($year, $month, $day) {
            $century = substr($year, 0, 2);
            $year = substr($year, 2, 2);
            if ($month > 2) {
                $month -= 3;
            } else {
                $month += 9;
                if ($year) {
                    $year--;
                } else {
                    $year = 99;
                    $century--;
                }
            }
            return ( floor(( 146097 * $century) / 4) +
                    floor(( 1461 * $year) / 4) +
                    floor(( 153 * $month + 2) / 5) +
                    $day + 1721119);
        }

        function modulo_10($num) {
            $numtotal10 = 0;
            $fator = 2;

            // Separacao dos numeros
            for ($i = strlen($num); $i > 0; $i--) {
                // pega cada numero isoladamente
                $numeros[$i] = substr($num, $i - 1, 1);
                // Efetua multiplicacao do numero pelo (falor 10)
                $temp = $numeros[$i] * $fator;
                $temp0 = 0;
                foreach (preg_split('//', $temp, -1, PREG_SPLIT_NO_EMPTY) as $k => $v) {
                    $temp0+=$v;
                }
                $parcial10[$i] = $temp0; //$numeros[$i] * $fator;
                // monta sequencia para soma dos digitos no (modulo 10)
                $numtotal10 += $parcial10[$i];
                if ($fator == 2) {
                    $fator = 1;
                } else {
                    $fator = 2; // intercala fator de multiplicacao (modulo 10)
                }
            }

            // v�rias linhas removidas, vide fun��o original
            // Calculo do modulo 10
            $resto = $numtotal10 % 10;
            $digito = 10 - $resto;
            if ($resto == 0) {
                $digito = 0;
            }

            return $digito;
        }

        function modulo_11($num, $base = 9, $r = 0) {
            /**
             *   Autor:
             *           Pablo Costa <pablo@users.sourceforge.net>
             *
             *   Fun��o:
             *    Calculo do Modulo 11 para geracao do digito verificador
             *    de boletos bancarios conforme documentos obtidos
             *    da Febraban - www.febraban.org.br
             *
             *   Entrada:
             *     $num: string num�rica para a qual se deseja calcularo digito verificador;
             *     $base: valor maximo de multiplicacao [2-$base]
             *     $r: quando especificado um devolve somente o resto
             *
             *   Sa�da:
             *     Retorna o Digito verificador.
             *
             *   Observa��es:
             *     - Script desenvolvido sem nenhum reaproveitamento de c�digo pr� existente.
             *     - Assume-se que a verifica��o do formato das vari�veis de entrada � feita antes da execu��o deste script.
             */
            $soma = 0;
            $fator = 2;

            /* Separacao dos numeros */
            for ($i = strlen($num); $i > 0; $i--) {
                // pega cada numero isoladamente
                $numeros[$i] = substr($num, $i - 1, 1);
                // Efetua multiplicacao do numero pelo falor
                $parcial[$i] = $numeros[$i] * $fator;
                // Soma dos digitos
                $soma += $parcial[$i];
                if ($fator == $base) {
                    // restaura fator de multiplicacao para 2
                    $fator = 1;
                }
                $fator++;
            }

            /* Calculo do modulo 11 */
            if ($r == 0) {
                $soma *= 10;
                $digito = $soma % 11;
                return $digito;
            } elseif ($r == 1) {
                // esta rotina sofrer algumas altera��es para ajustar no layout do SICREDI
                $r_div = (int) ($soma / 11);
                $digito = ($soma - ($r_div * 11));
                return $digito;
            }
        }

        function monta_linha_digitavel($codigo) {

            // COMPOSICAO DO CODIGO
            // Posi��o | Larg | Conte�do
            // --------+------+---------------
            // 1 a 3   |  03  | Identca��o do banco
            // 4       |  01  | C�digo da Moeda - 9 para R$
            // 5       |  01  | Digito verificador geral do C�digo de Barras
            // 6 a 9   |  04  | Fator de Vencimento
            // 10 a 19 |  10  | Valor (8 inteiros e 2 decimais)
            // 20 a 44 |  25  | Campo Livre definido por cada banco (25 caracteres)
            //COMPOSICAO DA LINHA DIGITAVEL
            // 1. Campo - composto pelo c�digo do banco, c�digo da mo�da, as cinco primeiras posi��es
            // do campo livre e DV (modulo10) deste campo
            $p1 = substr($codigo, 0, 4);
            $p2 = substr($codigo, 19, 5);
            $p3 = modulo_10("$p1$p2");
            $p4 = "$p1$p2$p3";
            $p5 = substr($p4, 0, 5);
            $p6 = substr($p4, 5);
            $campo1 = "$p5.$p6";

            // 2. Campo - composto pelas posi�oes 6 a 15 do campo livre
            // e livre e DV (modulo10) deste campo
            $p1 = substr($codigo, 24, 10);
            $p2 = modulo_10($p1);
            $p3 = "$p1$p2";
            $p4 = substr($p3, 0, 5);
            $p5 = substr($p3, 5);
            $campo2 = "$p4.$p5";

            // 3. Campo composto pelas posicoes 16 a 25 do campo livre
            // e livre e DV (modulo10) deste campo
            $p1 = substr($codigo, 34, 10);
            $p2 = modulo_10($p1);
            $p3 = "$p1$p2";
            $p4 = substr($p3, 0, 5);
            $p5 = substr($p3, 5);
            $campo3 = "$p4.$p5";

            // 4. Campo - digito verificador do codigo de barras
            $campo4 = substr($codigo, 4, 1);

            // 5. Campo composto pelo fator vencimento e valor nominal do documento, sem
            // indicacao de zeros a esquerda e sem edicao (sem ponto e virgula). Quando se
            // tratar de valor zerado, a representacao deve ser 000 (tres zeros).
            $p1 = substr($codigo, 5, 4);
            $p2 = substr($codigo, 9, 10);
            $campo5 = "$p1$p2";

            return "$campo1 $campo2 $campo3 $campo4 $campo5";
        }

        function geraCodigoBanco($numero) {
            $parte1 = substr($numero, 0, 3);
//    $parte2 = modulo_11($parte1);
            return $parte1 . "-X";
        }

    }

    public function boletoAction() {
        $ContasReceberModel = new ContasReceber_Model();
        $boleto = $ContasReceberModel->buscarParaBoleto($this->id);
        if (!empty($boleto['id'])):
            $boletos[] = $boleto;
            include("BoletoSicredi/layout.php");
        endif;
    }

    public function remessaAction() {
        $ContasReceberModel = new ContasReceber_Model();
        $contas = $ContasReceberModel->buscarPorRemessa($this->id);
        foreach ($contas AS $conta):
            $boleto = $ContasReceberModel->buscarParaBoleto($conta['id']);
            if (!empty($boleto['id'])):
                $boletos[] = $boleto;
            endif;
        endforeach;
        include("BoletoSicredi/layout.php");
    }

    public function imprimirAction() {
        $ContasReceberModel = new ContasReceber_Model();
        $boleto_id = $this->_getParam('boleto_id');
        foreach ($boleto_id AS $id):
            $boleto = $ContasReceberModel->buscarParaBoleto($id);
            if (!empty($boleto['id'])):
                $boletos[] = $boleto;
            endif;
        endforeach;
        include("BoletoSicredi/layout.php");
    }

}