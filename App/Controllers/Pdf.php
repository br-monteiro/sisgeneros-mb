<?php
namespace App\Controllers;

use Knp\Snappy\Pdf as Snappy;

class Pdf
{

    public $url = '';
    private $urlBase = 'http://' . DOMAIN;

    public function gerar()
    {
        $url = $this->urlBase . $this->url;
        // deve ser alterado para o Path do binário correspondente na máquina
        $snappy = new Snappy('/usr/bin/wkhtmltopdf-i386');
        $snappy->setOptions([
            'orientation' => 'Landscape',
            //'default-header' => true,
            //'user-style-sheet' => true
        ]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="solicitacao_' . time() . '.pdf"');
        echo $snappy->getOutput($url);
    }
}
